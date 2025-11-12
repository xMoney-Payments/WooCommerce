<?php
/**
 * Server-to-server (IPN) handler for xMoney Payments.
 * Receives encrypted callbacks and updates WooCommerce orders.
 *
 * @package xmoney-payments
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles IPN/server callbacks from xMoney Payments.
 */
class Xmoney_Payments_Server_To_Server {
	/**
	 * Current language code used for loading translation strings.
	 *
	 * @var string
	 */
	private string $language;

	/**
	 * Constructor. Loads required helper classes and registers IPN handler.
	 */
	public function __construct() {
		require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'helpers/class-xmoney-payments-logger.php';
		require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'helpers/class-xmoney-payments-helper-response.php';
		require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'helpers/class-xmoney-payments-status-updater.php';
		require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'helpers/class-xmoney-payments-helper-processor.php';

		$this->language = Xmoney_Payments_Helper_Processor::get_current_language();

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- S2S/IPN endpoint trigger uses a query flag; authenticity is enforced in handle() via decryption/signature.
		if ( isset( $_GET['twispay-ipn'] ) ) {
			add_action( 'init', array( $this, 'handle' ) );
		}
	}

	/**
	 * Handle server-to-server (IPN) callback from xMoney Payments.
	 *
	 * Note: Nonce verification does not apply to third-party callbacks.
	 * Authenticity is validated using the encrypted payload and secret key
	 * via Xmoney_Payments_Helper_Response::xmoney_payments_decrypt_message and ::xmoney_payments_check_validation.
	 */
	public function handle() {
		// FIXME: Change this i18n logic with the idiomatic one.
		if ( file_exists( XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/' . $this->language . '/lang.php' ) ) {
			require XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/' . $this->language . '/lang.php';
		} else {
			require XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/en/lang.php';
		}

		/**
		 * Language translation array.
		 *
		 * @var array $xmoney_payments_lang
		 */

		// Check if the POST is corrupted: doesn't contain the 'opensslResult' and the 'result' fields.
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Webhook callback; verified cryptographically below.
		if ( isset( $_POST['opensslResult'] ) === false && isset( $_POST['result'] ) === false ) {
			Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( '[RESPONSE-ERROR]: Received empty response.', 'xmoney-payments' ) );
			die( esc_html__( '[RESPONSE-ERROR]: Received empty response.', 'xmoney-payments' ) );
		}

		$configuration = Xmoney_Payments_Helper_Processor::get_configuration();

		if ( empty( $configuration ) ) {
			Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( '[RESPONSE-ERROR]: Private key is not valid.', 'xmoney-payments' ) );
			die( esc_html__( '[RESPONSE-ERROR]: Private key is not valid.', 'xmoney-payments' ) );
		}

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Webhook callback; verified cryptographically below.
		$result    = isset( $_POST['opensslResult'] ) ? sanitize_text_field( wp_unslash( $_POST['opensslResult'] ) ) : sanitize_text_field( wp_unslash( $_POST['result'] ) );
		$decrypted = Xmoney_Payments_Helper_Response::xmoney_payments_decrypt_message(
			$result,
			$configuration['secret_key']
		);

		if ( false === $decrypted ) {
			Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( '[RESPONSE-ERROR]: Decryption failed.', 'xmoney-payments' ) );
			Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( '[RESPONSE-ERROR]: opensslResult: ', 'xmoney-payments' ) . $result );

			die( esc_html__( '[RESPONSE-ERROR]: Decryption failed.', 'xmoney-payments' ) );
		}

		Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( '[RESPONSE]: Decryption successfully performed.', 'xmoney-payments' ) );

		$is_order_valid = Xmoney_Payments_Helper_Response::xmoney_payments_check_validation( $decrypted );

		if ( true !== $is_order_valid ) {
			Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( '[RESPONSE-ERROR]: Validation failed.', 'xmoney-payments' ) );
			die( esc_html__( '[RESPONSE-ERROR]: Validation failed.', 'xmoney-payments' ) );
		}

		// Extract numeric order ID prefix from externalOrderId (format: "<orderId>[_suffix]").
		$order_id = 0;
		if ( isset( $decrypted['externalOrderId'] ) && is_string( $decrypted['externalOrderId'] ) ) {
			$ext = $decrypted['externalOrderId'];
			if ( preg_match( '/^(\d+)/', $ext, $m ) ) {
				$order_id = (int) $m[1];
			}
		}
		if ( 0 === $order_id ) {
			Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( '[RESPONSE-ERROR]: Invalid externalOrderId format.', 'xmoney-payments' ) );
			die( esc_html__( '[RESPONSE-ERROR]: Invalid externalOrderId format.', 'xmoney-payments' ) );
		}
		$xmoney_payments_order = wc_get_order( $order_id );

		if ( false === $xmoney_payments_order ) {
			Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( '[RESPONSE-ERROR]: Order does not exist.', 'xmoney-payments' ) );
			die( esc_html__( '[RESPONSE-ERROR]: Order does not exist.', 'xmoney-payments' ) );
		}

		// Extract the transaction status.
		$status = empty( $decrypted['status'] ) ? $decrypted['transactionStatus'] : $decrypted['status'];

		// Set the status of the WooCommerce order according to the received status.
		Xmoney_Payments_Status_Updater::update_status_i_p_n( $order_id, $status );

		// Send the 200 OK response back to the xMoney Payments server.
		die( 'OK' );
	}
}
