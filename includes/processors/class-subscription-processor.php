<?php

class Twispay_Subscription_Processor {
    private $order_id;
    private $language;
    private $nonce_action = 'twispay_sub_process';

    public function __construct() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Checkout page GET parameter, used to identify subscription order; read-only and sanitized.
        $this->order_id = !empty($_GET['order_id']) ? (int)sanitize_key($_GET['order_id']) : null;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Checkout page GET parameter, used to conditionally hook subscription process; safe for display-only usage.
        if ($this->order_id && strpos(sanitize_text_field(wp_unslash($_GET['order_id'])), '_sub') !== false) {
            add_action('woocommerce_after_checkout_form', [$this, 'process']);
        }
    }

    public function process() {
        // Verify nonce to protect this action from CSRF. The nonce must be generated at the link/form that starts this flow.
        // If there is no nonce present, treat it as invalid and redirect.
        $raw_nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';

        if (empty($raw_nonce) || !wp_verify_nonce(sanitize_text_field($raw_nonce), $this->nonce_action)) {
            wc_add_notice(esc_html__('Invalid request. Please try again.', 'xmoney-payments'), 'error');
            wp_safe_redirect(wc_get_cart_url());
            exit;
        }

        require_once TWISPAY_PLUGIN_DIR . 'helpers/Twispay_TW_Helper_Notify.php';
        require_once TWISPAY_PLUGIN_DIR . 'helpers/Twispay_TW_Helper_Processor.php';
        $this->language = Twispay_TW_Helper_Processor::get_current_language();

        $config = Twispay_TW_Helper_Processor::get_configuration();

        // Use inline checkout if enabled.
        if (function_exists('twispay_tw_is_inline_enabled') && twispay_tw_is_inline_enabled()) {
            $order = wc_get_order($this->order_id);
            if (!$order) {
                return;
            }

            $is_live = !empty($config['is_live']);
            $publicKey = $config['site_id'];
            $secretKey = $config['secret_key'];

            try {
                $request = $this->prepare_request_data();
                $session_token = Twispay_TW_Helper_Processor::get_session_token($is_live, $secretKey);
            } catch (Exception $e) {
                wc_add_notice($e->getMessage(), 'error');
                wp_safe_redirect(wc_get_cart_url());
                return;
            }

            $sdk_url = $is_live
                ? (Twispay_TW_Helper_Processor::LIVE_URL_JS . '/sdk/0.0.9/xmoney.js')
                : (Twispay_TW_Helper_Processor::STAGE_URL_JS . '/sdk/0.0.9/xmoney.js');

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
                <div id="xmoney-checkout-container" style="min-height:280px;"></div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    try {
                        const form = new XMoneyPaymentForm({
                            container: 'xmoney-checkout-container',
                            payload: <?php echo wp_json_encode($request['data']); ?>,
                            checksum: <?php echo wp_json_encode($request['checksum']); ?>,
                            publicKey: <?php echo wp_json_encode($publicKey); ?>,
                            sessionToken: <?php echo wp_json_encode($session_token); ?>,
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
                                        result: result
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
            $message = $e->getMessage();
            wc_add_notice($e->getMessage(), 'error');
	        wp_safe_redirect( wc_get_cart_url() );
            return;
        }
        ?>
        <style>
          body {
            height: 100%;
            overflow: hidden !important;
          }

          .wrapper-loader {
            background-color: #fff;
            height: 100%;
            left: 0;
            position: absolute;
            width: 100%;
            top: 0;
            z-index: 1000;
          }

          .loader {
            margin: 15% auto 0;
            border: 14px solid #f3f3f3;
            border-top: 14px solid #3498db;
            border-radius: 50%;
            width: 110px;
            height: 110px;
            animation: spin 1.1s linear infinite;
          }

          @keyframes spin {
            0% {
              transform: rotate(0deg);
            }
            100% {
              transform: rotate(360deg);
            }
          }
        </style>

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

        <script>document.getElementById("twispay_payment_form").submit();</script>
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

        if (!class_exists(WC_Subscription::class)) {
            throw new Exception(esc_html__( 'You are not allowed to access this file.','xmoney-payments' ));
        }

        if (empty($this->order_id) || $order === false) {
            throw new Exception(esc_html__( 'You are not allowed to access this file.','xmoney-payments' ));
        }

        if (!wcs_order_contains_subscription($this->order_id)) {
            throw new Exception(esc_html__( 'The order has no items.','xmoney-payments' ));
        }

        if (1 < count($order->get_items())) {
            throw new Exception(esc_html__( 'Orders with subscriptions cannot have other products too.','xmoney-payments' ));
        }

        $configuration = Twispay_TW_Helper_Processor::get_configuration();

        if (empty($configuration)) {
            throw new Exception(esc_html__('Missing configuration for plugin.','xmoney-payments'));
        }

        $subscription = wcs_get_subscriptions_for_order($order);
        $subscription = reset($subscription);

        $data = $subscription->get_data();

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

        $item = $subscription->get_items();
        $item = reset($item);

        // Build back URL and add a nonce so the callback endpoint can optionally verify it
        $back_url = get_permalink(get_page_by_path('xmoney-payments-confirmation'));
        $back_url = add_query_arg(
            [
                'secure_key' => $order->get_data()['cart_hash'],
                '_wpnonce' => wp_create_nonce($this->nonce_action),
            ],
            $back_url
        );

        /* !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */
        /* !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! IMPORTANT !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */
        /* READ:  We presume that there will be ONLY ONE subscription product inside the order. */
        /* !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */
        /* !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */

        /* Extract the subscription details. */
        $trial_amount = WC_Subscriptions_Product::get_sign_up_fee($item['product_id']);
        $first_billing_date = explode(' ', WC_Subscriptions_Product::get_trial_expiration_date($item['product_id']))[0];

        /* Calculate the subscription's interval type and value. */
        $subscription_interval = $this->maybe_convert_trial_interval(
            $subscription->get_billing_period(),
            $subscription->get_billing_interval()
        );

        $description = sprintf('%s %s subscription %s',
            $subscription_interval['interval_value'],
            $subscription_interval['interval_type'],
            $item['name']
        );

        $orderId = NULL;

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameter identifies subscription order; sanitized and verified against WooCommerce order, safe for request data.
        if (isset($_GET['order_id'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Same GET parameter used to prepare request data; sanitized and verified, safe to use.
            $orderId = sanitize_key($_GET['order_id']);
        }

        $order_data = [
            'siteId' => $configuration['site_id'],
            'customer' => $customer,
            'order' => [
                'orderId' => $orderId,
                'type' => 'recurring',
                'amount' => $data['total'],
                'currency' => $data['currency'],
                'intervalType' => $subscription_interval['interval_type'],
                'intervalValue' => $subscription_interval['interval_value'],
                'description' => $description,
            ],
            'cardTransactionMode' => 'authAndCapture',
            'invoiceEmail' => '',
            'backUrl' => $back_url,
        ];

        if ('0' !== $trial_amount) {
            $order_data['order']['trialAmount'] = $trial_amount;
            $order_data['order']['firstBillDate'] = $first_billing_date;
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

    private function maybe_convert_trial_interval($interval_type, $interval_value) {
        if ($interval_type === 'week') {
            $interval_type = 'day';
            $interval_value = 7 * $interval_value;
        }

        if ($interval_type === 'year') {
            $interval_type = 'month';
            $interval_value = 12 * $interval_value;
        }

        return [
            'interval_type' => $interval_type,
            'interval_value' => $interval_value,
        ];
    }
}
