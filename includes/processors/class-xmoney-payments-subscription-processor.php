<?php
/**
 * Main processor for subscription xMoney Payments orders.
 *
 * @package Xmoney/Front
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles subscription-based xMoney Payments checkout processing.
 *
 * Builds request payloads, validates order context, and renders the redirect form.
 */
class Xmoney_Payments_Subscription_Processor {
	/**
	 * Order ID extracted from request, or null if not provided.
	 *
	 * @var int|null
	 */
	private ?int $order_id;
	/**
	 * Current language code used for localization.
	 *
	 * @var string
	 */
	private string $language;
	/**
	 * Nonce action name used for request integrity verification.
	 *
	 * @var string
	 */
	private string $nonce_action = 'xmoney_payments_process';

	/**
	 * Constructor.
	 *
	 * Extracts a numeric order id (if present) and hooks the processing callback.
	 * Nonce verification is intentionally deferred until process() because pluggable
	 * functions may not be loaded at this stage of plugin initialization.
	 */
	public function __construct() {
		// Defer nonce verification until process() (pluggable may not be loaded yet here).
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only retrieval and sanitization.
		$this->order_id = ! empty( $_GET['order_id'] ) ? (int) sanitize_key( $_GET['order_id'] ) : null;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe: no state change occurs here.
		if ( $this->order_id && strpos( sanitize_text_field( wp_unslash( $_GET['order_id'] ) ), '_sub' ) !== false ) {
			add_action( 'woocommerce_after_checkout_form', array( $this, 'process' ) );
		}
	}

	/**
	 * Execute the payment form rendering flow.
	 *
	 * Verifies nonce, loads helpers, builds request data and prints the payment
	 * form that auto-submits to the gateway.
	 */
	public function process() {
		// Verify nonce to protect this action from CSRF. The nonce must be generated at the link/form that starts this flow.
		// If there is no nonce present, treat it as invalid and redirect.
		$raw_nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';

		if ( empty( $raw_nonce ) || ! wp_verify_nonce( sanitize_text_field( $raw_nonce ), $this->nonce_action ) ) {
			wc_add_notice( esc_html__( 'Invalid request. Please try again.', 'xmoney-payments' ), 'error' );
			wp_safe_redirect( wc_get_cart_url() );
			exit;
		}

		require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'helpers/class-xmoney-payments-helper-notify.php';
		require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'helpers/Xmoney_Payments_Helper_Processor.php';
		$this->language = Xmoney_Payments_Helper_Processor::get_current_language();

		// Load process css & js files
		wp_enqueue_style( 'ma-process-css', XMONEY_PAYMENTS_PLUGIN_URL . 'assets/css/process.css', array(), XMONEY_PAYMENTS_VERSION, true );
		wp_enqueue_script( 'ma-process-js', XMONEY_PAYMENTS_PLUGIN_URL . 'assets/js/process.js', array(), XMONEY_PAYMENTS_VERSION, true );

		try {
			$request_data = $this->prepare_request_data();
		} catch ( Exception $e ) {
			wc_add_notice( $e->getMessage(), 'error' );
			wp_safe_redirect( wc_get_cart_url() );
			exit;
		}
		?>

		<div class="wrapper-loader">
			<div class="loader"></div>
		</div>

		<form action="<?php echo esc_url( $request_data['host_name'] ); ?>"
				method="POST"
				accept-charset="UTF-8"
				id="xmoney_payments_payment_form">
			<input type="hidden" name="jsonRequest" value="<?php echo esc_attr( $request_data['data'] ); ?>">
			<input type="hidden" name="checksum" value="<?php echo esc_attr( $request_data['checksum'] ); ?>">
		</form>

		<?php
	}

	/**
	 * Build the structured subscription payment request to send to the gateway.
	 *
	 * @return array{host_name:string,data:string,checksum:string} Encoded payload and endpoint URL.
	 * @throws Exception If order, subscription, or configuration data is missing or invalid.
	 */
	private function prepare_request_data() {
		// FIXME: Change this i18n logic with the idiomatic one.
		if ( file_exists( XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/' . $this->language . '/lang.php' ) ) {
			require XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/' . $this->language . '/lang.php';
		} else {
			require XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/en/lang.php';
		}

		$xmoney_payments_order = wc_get_order( $this->order_id );

		if ( ! class_exists( WC_Subscription::class ) ) {
			throw new Exception( esc_html__( 'You are not allowed to access this file.', 'xmoney-payments' ) );
		}

		if ( empty( $this->order_id ) || false === $xmoney_payments_order ) {
			throw new Exception( esc_html__( 'You are not allowed to access this file.', 'xmoney-payments' ) );
		}

		if ( ! wcs_order_contains_subscription( $this->order_id ) ) {
			throw new Exception( esc_html__( 'The order has no items.', 'xmoney-payments' ) );
		}

		if ( 1 < count( $xmoney_payments_order->get_items() ) ) {
			throw new Exception( esc_html__( 'Orders with subscriptions cannot have other products too.', 'xmoney-payments' ) );
		}

		$configuration = Xmoney_Payments_Helper_Processor::get_configuration();

		if ( empty( $configuration ) ) {
			throw new Exception( esc_html__( 'Missing configuration for plugin.', 'xmoney-payments' ) );
		}

		$subscription = wcs_get_subscriptions_for_order( $xmoney_payments_order );
		$subscription = reset( $subscription );

		$data = $subscription->get_data();

		$customer = array(
			'identifier' => 0 === $data['customer_id'] ? $this->order_id : $data['customer_id'],
			'firstName'  => ! empty( $data['billing']['first_name'] ) ? $data['billing']['first_name'] : '',
			'lastName'   => ! empty( $data['billing']['last_name'] ) ? $data['billing']['last_name'] : '',
			'country'    => ! empty( $data['billing']['country'] ) ? $data['billing']['country'] : '',
			'city'       => ! empty( $data['billing']['city'] ) ? $data['billing']['city'] : $data['shipping']['city'],
			'address'    => ! empty( $data['billing']['address_1'] ) ? $data['billing']['address_1'] : '',
			'zipCode'    => ! empty( $data['billing']['postcode'] ) ? $data['billing']['postcode'] : $data['shipping']['postcode'],
			'phone'      => Xmoney_Payments_Helper_Processor::format_phone( $data['billing']['phone'] ),
			'email'      => $data['billing']['email'],
		);

		$item = $subscription->get_items();
		$item = reset( $item );

		// Build back URL and add a nonce so the callback endpoint can optionally verify it
		$back_url = get_permalink( get_page_by_path( 'xmoney-payments-confirmation' ) );
		$back_url = wp_nonce_url(
			add_query_arg(
				array(
					'secure_key' => $xmoney_payments_order->get_data()['cart_hash'],
					'_wpnonce'   => wp_create_nonce( $this->nonce_action ),
				),
				$back_url
			),
			'xmoney_payments_process'
		);

		// Assumption: A subscription order contains exactly one subscription product.

		/* Extract the subscription details. */
		$trial_amount       = WC_Subscriptions_Product::get_sign_up_fee( $item['product_id'] );
		$first_billing_date = explode( ' ', WC_Subscriptions_Product::get_trial_expiration_date( $item['product_id'] ) )[0];

		/* Calculate the subscription's interval type and value. */
		$subscription_interval = $this->maybe_convert_trial_interval(
			$subscription->get_billing_period(),
			$subscription->get_billing_interval()
		);

		$description = sprintf(
			'%s %s subscription %s',
			$subscription_interval['interval_value'],
			$subscription_interval['interval_type'],
			$item['name']
		);

		$order_id = null;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Already verified in process; sanitized below for safe echo.
		if ( isset( $_GET['order_id'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Already verified in process; sanitized below for safe echo.
			$order_id = sanitize_text_field( wp_unslash( $_GET['order_id'] ) );
		}

		$order_data = array(
			'siteId'              => $configuration['site_id'],
			'customer'            => $customer,
			'order'               => array(
				'orderId'       => $order_id,
				'type'          => 'recurring',
				'amount'        => $data['total'],
				'currency'      => $data['currency'],
				'intervalType'  => $subscription_interval['interval_type'],
				'intervalValue' => $subscription_interval['interval_value'],
				'description'   => $description,
			),
			'cardTransactionMode' => 'authAndCapture',
			'invoiceEmail'        => '',
			'backUrl'             => $back_url,
		);

		if ( '0' !== $trial_amount ) {
			$order_data['order']['trialAmount']   = $trial_amount;
			$order_data['order']['firstBillDate'] = $first_billing_date;
		}

		$request_data = Xmoney_Payments_Helper_Notify::get_base64_json_request( $order_data );
		$checksum     = Xmoney_Payments_Helper_Notify::get_base64_checksum( $order_data, $configuration['secret_key'] );
		$host_name    = add_query_arg(
			array( 'lang' => $this->language ),
			$configuration['is_live'] ? Xmoney_Payments_Helper_Processor::LIVE_URL : Xmoney_Payments_Helper_Processor::STAGE_URL
		);

		return array(
			'host_name' => esc_url( $host_name ),
			'data'      => esc_attr( $request_data ),
			'checksum'  => esc_attr( $checksum ),
		);
	}

	/**
	 * Normalize subscription interval units so the gateway only receives day/month cycles.
	 *
	 * @param string     $interval_type Original interval type (day|week|month|year).
	 * @param int|string $interval_value Interval count.
	 * @return array{interval_type:string,interval_value:int} Converted interval unit and value.
	 */
	private function maybe_convert_trial_interval( $interval_type, $interval_value ) {
		if ( 'week' === $interval_type ) {
			$interval_type  = 'day';
			$interval_value = 7 * $interval_value;
		}

		if ( 'year' === $interval_type ) {
			$interval_type  = 'month';
			$interval_value = 12 * $interval_value;
		}

		return array(
			'interval_type'  => $interval_type,
			'interval_value' => $interval_value,
		);
	}
}
