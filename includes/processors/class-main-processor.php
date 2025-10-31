<?php
/* Exit if the file is accessed directly. */
if (!defined('ABSPATH')) {
    exit;
}

class Twispay_Main_Processor {
    private $order_id;
    private $language;
    private $nonce_action = 'twispay_process';

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

        try {
            $request_data = $this->prepare_request_data();
        } catch (Exception $e) {
            $message = $e->getMessage();
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

        $customer = [
            'identifier' => $data['customer_id'] === 0 ? $this->order_id : $data['customer_id'],
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
