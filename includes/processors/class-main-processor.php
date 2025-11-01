<?php
/* Exit if the file is accessed directly. */
if (!defined('ABSPATH')) {
    exit;
}

class Twispay_Main_Processor {
    private ?int $order_id;
    private string $language;
    private string $nonce_action = 'twispay_process';

    public function __construct() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe: only reading GET to display checkout flow.
        $this->order_id = !empty($_GET['order_id']) ? (int)sanitize_key($_GET['order_id']) : null;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe: no state change occurs here.
        if ($this->order_id && strpos(sanitize_text_field(wp_unslash($_GET['order_id'])), '_sub') === false) {
            add_action('woocommerce_after_checkout_form', [$this, 'process']);
        }
    }

    public function process() {
        require_once TWISPAY_PLUGIN_DIR . 'helpers/Twispay_TW_Helper_Notify.php';
        require_once TWISPAY_PLUGIN_DIR . 'helpers/Twispay_TW_Helper_Processor.php';
        $this->language = Twispay_TW_Helper_Processor::get_current_language();

        // Load process css & js files
        wp_enqueue_style('ma-process-css', TWISPAY_PLUGIN_URL . 'assets/css/process.css', [], TWISPAY_VERSION, true);
        wp_enqueue_script('ma-process-js', TWISPAY_PLUGIN_URL . 'assets/js/process.js', array(), TWISPAY_VERSION, true);

        if ( function_exists('twispay_tw_is_inline_enabled') && twispay_tw_is_inline_enabled() ) {
            $order = wc_get_order( $this->order_id );
            if ( ! $order ) { return; }
            $config = Twispay_TW_Helper_Processor::get_configuration();
            $is_live = ! empty( $config['is_live'] );
            $publicKey = $config['site_id'];
            $secretKey = $config['secret_key'];

            $request = $this->prepare_request_data();

            $sessionToken = Twispay_TW_Helper_Processor::get_session_token( $is_live, $secretKey );

            $sdk_url = $is_live ? ( Twispay_TW_Helper_Processor::LIVE_URL_JS . '/sdk/0.0.9/xmoney.js' )
                                : ( Twispay_TW_Helper_Processor::STAGE_URL_JS . '/sdk/0.0.9/xmoney.js' );

            // Enqueue the xMoney SDK script properly.
            wp_enqueue_script(
                'xmoney-inline-sdk',
                $sdk_url,
                array(), // no dependencies
                TWISPAY_VERSION,
                true     // load in footer
            );
            ?>
            <div id="tw-xmoney-inline-wrap" style="margin:16px 0;">
              <div id="xmoney-checkout-container" style="min-height: 280px;"></div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function () {

                    jQuery(function ($) {

                        // Always unbind first to avoid duplicates
                        $(document.body).off('checkout_place_order_xmoney-payments');

                        // Also block plain form submit as backup
                        $('form.checkout').on('submit', function (e) {
                            if ($('#payment-form-widget').length && $('#payment_method_xmoney-payments').is(':checked')) {
                                e.preventDefault();
                                e.stopImmediatePropagation();
                                return false;
                            }
                        });
                    });

                    try {
                        const form = new XMoneyPaymentForm({
                            container: 'xmoney-checkout-container',
                            payload: <?php echo wp_json_encode($request['data']); ?>,
                            checksum: <?php echo wp_json_encode($request['checksum']); ?>,
                            publicKey: <?php echo wp_json_encode($publicKey); ?>,
                            sessionToken: <?php echo wp_json_encode($sessionToken); ?>,
                            saveCard: true,
                            onPaymentComplete: function (result) {
                                fetch("<?php echo esc_url(rest_url('xmoney/v1/inline/confirm')); ?>", {
                                    method: "POST",
                                    headers: {
                                        "Content-Type": "application/json",
                                        "X-WP-Nonce": "<?php echo esc_js(wp_create_nonce('wp_rest')); ?>"
                                    },
                                    body: JSON.stringify({
                                        order_id: "<?php echo esc_js($this->order_id); ?>",
                                        result: result,
                                        customer_id: result.customerId || null,
                                        payment_method_id: result.paymentMethodId || null
                                    })
                                }).then(r => r.json()).then(resp => {
                                    if (resp && resp.success) {
                                        window.location.href = resp.redirect;
                                    } else {
                                        alert(resp && resp.message ? resp.message : "Payment failed.");
                                    }
                                }).catch(function () {
                                    alert("Network error while confirming payment.");
                                });
                            },
                            onError: function (err) {
                                console.error('xMoney error', err);
                            }
                        });
                        // Expose a trigger for "Place Order" button
                        window.__xmoneyTrigger = function () {
                            form.pay(); // This triggers the inline card form submission
                        };
                    } catch (e) {
                        console.error(e);
                    }
                });
            </script>
            <?php
            return;
        }

        try {
            $request_data = $this->prepare_request_data();
        } catch (Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
	        wp_safe_redirect( wc_get_cart_url() );
	        return;
        }

        ?>

        <div class="wrapper-loader">
            <div class="loader"></div>
        </div>
        
        <form action="<?php echo esc_url($request_data['host_name']); ?>"
              method="POST"
              accept-charset="UTF-8"
              id="twispay_payment_form">
            <input type="hidden" name="jsonRequest" value="<?php echo esc_attr($request_data['data']); ?>">
            <input type="hidden" name="checksum" value="<?php echo esc_attr($request_data['checksum']); ?>">
        </form>

        <?php
    }

    private function prepare_request_data() {
        // FIXME: Change this i18n logic with the idiomatic one.
        if (file_exists(TWISPAY_PLUGIN_DIR . 'lang/' . $this->language . '/lang.php')) {
            require(TWISPAY_PLUGIN_DIR . 'lang/' . $this->language . '/lang.php');
        } else {
            require(TWISPAY_PLUGIN_DIR . 'lang/en/lang.php');
        }

        $order = wc_get_order($this->order_id);

        if (empty($this->order_id) || $order === false) {
            throw new Exception(esc_html__( 'You are not allowed to access this file.','xmoney-payments' ));
        }

        $configuration = Twispay_TW_Helper_Processor::get_configuration();

        if (empty($configuration)) {
            throw new Exception(esc_html__( 'Missing configuration for plugin.','xmoney-payments' ));
        }

        $data = $order->get_data();
        $items = [];

        $site_hash = substr(md5(get_site_url()), 0, 8);
        $current_user_id = get_current_user_id();

        if ($current_user_id) {
            $customer_identifier = sprintf('site%s_user_%d', $site_hash, $current_user_id);
        } else {
            $customer_identifier = sprintf('site%s_guest_%s', $site_hash, uniqid());
        }

        $customer = [
            'identifier' => $customer_identifier,
            'firstName' => $data['billing']['first_name'] ?: '',
            'lastName' => $data['billing']['last_name'] ?: '',
            'country' => $data['billing']['country'] ?: '',
            'city' => $data['billing']['city'] ?: $data['shipping']['city'],
            'address' => $data['billing']['address_1'] ?: '',
            'zipCode' => $data['billing']['postcode'] ?: $data['shipping']['postcode'],
            'phone' => Twispay_TW_Helper_Processor::format_phone($data['billing']['phone']),
            'email' => $data['billing']['email'],
        ];

        foreach ($order->get_items() as $item) {
            $items[] = [
                'item' => $item['name'],
                'units' => $item['quantity'],
                'unitPrice' => $this->format_price($item['subtotal'], $item['quantity']),
            ];
        }

        $back_url = get_permalink(get_page_by_path('xmoney-payments-confirmation'));
        $back_url = wp_nonce_url(add_query_arg(
            [
                'secure_key' => $order->get_data()['cart_hash'],
                '_wpnonce' => wp_create_nonce($this->nonce_action),
            ],
            $back_url
        ), 'twispay_process');

        $orderId = NULL;

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe: reading GET only to build payment request, no state change.
        if(isset($_GET['order_id'])){
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe: reading GET only to build payment request, no state change.
            $orderId = sanitize_key($_GET['order_id']);
        }

        $order_data = [
            'publicKey' => $configuration['site_id'],
            'siteId' => $configuration['site_id'],
            'customer' => $customer,
            'order' => [
                'orderId' => $orderId,
                'type' => 'purchase',
                'amount' => $data['total'],
                'currency' => $data['currency'],
                'items' => $items
            ],
            'cardTransactionMode' => 'authAndCapture',
            'invoiceEmail' => '',
            'backUrl' => $back_url,
        ];

        $user_id = get_current_user_id();

        $saved_card = $user_id ? get_user_meta($user_id, '_xmoney_saved_card', true) : null;

        if (!empty($saved_card['payment_method_id'])) {
            $order_data['customerPaymentMethodId'] = $saved_card['payment_method_id'];
        }

        $request_data = Twispay_TW_Helper_Notify::getBase64JsonRequest($order_data);
        $checksum = Twispay_TW_Helper_Notify::getBase64Checksum($order_data, $configuration['secret_key']);
        $host_name = add_query_arg(
            [ 'lang' => $this->language ],
            $configuration['is_live'] ? Twispay_TW_Helper_Processor::LIVE_URL : Twispay_TW_Helper_Processor::STAGE_URL
        );

        return [
            'host_name' => esc_url($host_name),
            'data' => esc_attr($request_data),
            'checksum' => esc_attr($checksum),
        ];
    }

    private function format_price($subtotal, $quantity) {
        $subtotal = number_format((float) $subtotal, 2);
        $quantity = number_format((float) $quantity, 2);

        return number_format($subtotal / $quantity, 2);
    }
}
