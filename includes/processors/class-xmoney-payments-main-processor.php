<?php
/**
 * Main processor for one-off (non-subscription) xMoney Payments orders.
 *
 * @package Xmoney/Front
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main processor for one-off (non-subscription) xMoney Payments orders.
 *
 * Responsibilities:
 * - Parse and validate the incoming order_id from the checkout redirect.
 * - Verify the nonce (deferred to process()).
 * - Assemble the request payload sent to the xMoney Payments gateway.
 * - Render the auto-submitted payment form.
 */
class Xmoney_Payments_Main_Processor {

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
		if ( $this->order_id && strpos( sanitize_text_field( wp_unslash( $_GET['order_id'] ) ), '_sub' ) === false ) {
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

		// Verify request integrity.
		$raw_nonce = '';
		if ( isset( $_POST['_wpnonce'] ) ) {
			$raw_nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) );
		} elseif ( isset( $_GET['_wpnonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only retrieval.
			$raw_nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );
		}
		if ( empty( $raw_nonce ) || ! wp_verify_nonce( $raw_nonce, $this->nonce_action ) ) {
			wc_add_notice( esc_html__( 'Invalid request. Please try again.', 'xmoney-payments' ), 'error' );
			wp_safe_redirect( wc_get_cart_url() );
			exit;
		}
		require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'helpers/class-xmoney-payments-helper-notify.php';
		require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'helpers/class-xmoney-payments-helper-processor.php';
		$this->language = Xmoney_Payments_Helper_Processor::get_current_language();

		// Load process css & js files
		wp_enqueue_style( 'ma-process-css', XMONEY_PAYMENTS_PLUGIN_URL . 'assets/css/process.css', array(), XMONEY_PAYMENTS_VERSION, true );

		// Use inline checkout if enabled.
		if ( function_exists( 'xmoney_payments_is_inline_enabled' ) && xmoney_payments_is_inline_enabled() ) {
			$order = wc_get_order( $this->order_id );
			if ( ! $order ) {
				return;
			}

			$config     = Xmoney_Payments_Helper_Processor::get_configuration();
			$is_live    = ! empty( $config['is_live'] );
			$public_key = $config['site_id'];
			$secret_key = $config['secret_key'];

			$request = $this->prepare_request_data();

			$session_token = Xmoney_Payments_Helper_Processor::get_session_token( $is_live, $secret_key );
			if ( ! $session_token && function_exists( 'wc_get_logger' ) ) {
				$logger = wc_get_logger();
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$logger->warning( '[xMoney] Inline: session token not retrieved for environment ' . ( $is_live ? 'live' : 'stage' ), array( 'source' => 'xmoney-payments' ) );
			}

			$sdk_url = $is_live ? ( Xmoney_Payments_Helper_Processor::LIVE_URL_JS . '/sdk/v1/xmoney.js' )
				: ( Xmoney_Payments_Helper_Processor::STAGE_URL_JS . '/sdk/v1/xmoney.js' );

			// Enqueue the xMoney SDK script properly.
			wp_enqueue_script(
				'xmoney-inline-sdk',
				$sdk_url,
				array(),
				XMONEY_PAYMENTS_VERSION,
				true
			);

			wp_register_script(
				'xmoney-inline-js',
				XMONEY_PAYMENTS_PLUGIN_URL . 'assets/js/inline.js',
				array( 'jquery', 'xmoney-inline-sdk' ),
				XMONEY_PAYMENTS_VERSION,
				true
			);

			$user_id = get_current_user_id();

			$saved_card = $user_id ? get_user_meta( $user_id, '_xmoney_saved_card', true ) : null;

			$params = array(
				'payload'    => $request['data'],
				'checksum'   => $request['checksum'],
				'publicKey'  => $public_key,
				'orderId'    => $this->order_id,
				'confirmUrl' => esc_url_raw( rest_url( 'xmoney/v1/inline/confirm' ) ),
				'restNonce'  => wp_create_nonce( 'wp_rest' ),
				'options'    => array(),
			);

			// Check if saved cards are enabled in config
			$saved_cards_enabled = function_exists( 'xmoney_payments_is_saved_cards_enabled' ) && xmoney_payments_is_saved_cards_enabled();

			// Get checkout theme from config
			$checkout_theme = function_exists( 'xmoney_payments_get_checkout_theme' ) ? xmoney_payments_get_checkout_theme() : 'light';

			$params['options']['displaySaveCardOption'] = $saved_cards_enabled && $user_id ? true : false;
			$params['options']['displayCardHolderName'] = true;
			$params['options']['enableSavedCards']      = $saved_cards_enabled;
			$params['options']['appearance']            = array(
				'theme' => $checkout_theme,
			);

			if ( $saved_cards_enabled && $session_token && $saved_card ) {
				$params['userId'] = $saved_card['customer_id'];
			}

			wp_localize_script( 'xmoney-inline-js', 'xmoneyData', $params );
			wp_enqueue_script( 'xmoney-inline-js' );

			// Also output data inline as fallback
			?>
			<script type="text/javascript">
				window.xmoneyData = <?php echo wp_json_encode( $params ); ?>;
			</script>

			<div id="xmoney-checkout-container" style="min-height: 400px;"></div>

			<div class="wrapper-loader">
				<div class="loader"></div>
			</div>

			<?php
			return;
		}

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

		<form action="<?php echo esc_url( $request_data['host_name'] ); ?>" method="POST" accept-charset="UTF-8"
			id="xmoney_payments_payment_form">
			<input type="hidden" name="jsonRequest" value="<?php echo esc_attr( $request_data['data'] ); ?>">
			<input type="hidden" name="checksum" value="<?php echo esc_attr( $request_data['checksum'] ); ?>">
		</form>

		<?php
		wp_enqueue_script( 'ma-process-js', XMONEY_PAYMENTS_PLUGIN_URL . 'assets/js/process.js', array(), XMONEY_PAYMENTS_VERSION, true );
	}

	/**
	 * Build the structured data array required by the gateway.
	 *
	 * @return array{host_name:string,data:string,checksum:string} Sanitized host URL and encoded request payload + checksum.
	 * @throws Exception When order or configuration data is missing/invalid.
	 */
	private function prepare_request_data() {
		// FIXME: Change this i18n logic with the idiomatic one.
		if ( file_exists( XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/' . $this->language . '/lang.php' ) ) {
			require XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/' . $this->language . '/lang.php';
		} else {
			require XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/en/lang.php';
		}

		$xmoney_payments_order = wc_get_order( $this->order_id );

		if ( empty( $this->order_id ) || false === $xmoney_payments_order ) {
			throw new Exception( esc_html__( 'You are not allowed to access this file.', 'xmoney-payments' ) );
		}

		$configuration = Xmoney_Payments_Helper_Processor::get_configuration();

		if ( empty( $configuration ) ) {
			throw new Exception( esc_html__( 'Missing configuration for plugin.', 'xmoney-payments' ) );
		}

		$data  = $xmoney_payments_order->get_data();
		$items = array();

		$site_hash       = substr( md5( get_site_url() ), 0, 8 );
		$current_user_id = get_current_user_id();

		if ( $current_user_id ) {
			$customer_identifier = sprintf( 'site%s_user_%d', $site_hash, $current_user_id );
		} else {
			$customer_identifier = sprintf( 'site%s_guest_%s', $site_hash, uniqid() );
		}

		$customer = array(
			'identifier' => $customer_identifier,
			'firstName'  => ! empty( $data['billing']['first_name'] ) ? $data['billing']['first_name'] : '',
			'lastName'   => ! empty( $data['billing']['last_name'] ) ? $data['billing']['last_name'] : '',
			'country'    => ! empty( $data['billing']['country'] ) ? $data['billing']['country'] : '',
			'city'       => ! empty( $data['billing']['city'] ) ? $data['billing']['city'] : $data['shipping']['city'],
			'address'    => ! empty( $data['billing']['address_1'] ) ? $data['billing']['address_1'] : '',
			'zipCode'    => ! empty( $data['billing']['postcode'] ) ? $data['billing']['postcode'] : $data['shipping']['postcode'],
			'phone'      => Xmoney_Payments_Helper_Processor::format_phone( $data['billing']['phone'] ),
			'email'      => $data['billing']['email'],
		);

		foreach ( $xmoney_payments_order->get_items() as $item ) {
			$items[] = array(
				'item'      => $item['name'],
				'units'     => $item['quantity'],
				'unitPrice' => $this->format_price( $item['subtotal'], $item['quantity'] ),
			);
		}

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
				'orderId'  => $order_id,
				'type'     => 'purchase',
				'amount'   => $data['total'],
				'currency' => $data['currency'],
				'items'    => $items,
			),
			'cardTransactionMode' => 'authAndCapture',
			'invoiceEmail'        => '',
			'backUrl'             => $back_url,
		);

		$live_url  = Xmoney_Payments_Helper_Processor::LIVE_URL;
		$stage_url = Xmoney_Payments_Helper_Processor::STAGE_URL;

		if ( function_exists( 'xmoney_payments_is_inline_enabled' ) && xmoney_payments_is_inline_enabled() ) {
			$order_data['publicKey'] = $configuration['site_id'];

			$live_url  = Xmoney_Payments_Helper_Processor::INLINE_LIVE_URL;
			$stage_url = Xmoney_Payments_Helper_Processor::INLINE_STAGE_URL;
		}

		$request_data = Xmoney_Payments_Helper_Notify::get_base64_json_request( $order_data );
		$checksum     = Xmoney_Payments_Helper_Notify::get_base64_checksum( $order_data, $configuration['secret_key'] );

		$host_name = add_query_arg(
			array( 'lang' => $this->language ),
			$configuration['is_live'] ? $live_url : $stage_url
		);

		return array(
			'host_name' => esc_url( $host_name ),
			'data'      => esc_attr( $request_data ),
			'checksum'  => esc_attr( $checksum ),
		);
	}

	/**
	 * Derive unit price with 2-decimal precision from subtotal and quantity.
	 *
	 * @param float|int|string $subtotal Raw line subtotal.
	 * @param float|int|string $quantity Item quantity.
	 * @return string 2-decimal unit price.
	 */
	private function format_price( $subtotal, $quantity ) {
		$subtotal = number_format( (float) $subtotal, 2 );
		$quantity = number_format( (float) $quantity, 2 );

		return number_format( $subtotal / $quantity, 2 );
	}
}
