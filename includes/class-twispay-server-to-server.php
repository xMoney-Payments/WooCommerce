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
class Twispay_Server_To_Server {
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
		require_once TWISPAY_PLUGIN_DIR . 'helpers/class-twispay-tw-logger.php';
		require_once TWISPAY_PLUGIN_DIR . 'helpers/class-twispay-tw-helper-response.php';
		require_once TWISPAY_PLUGIN_DIR . 'helpers/class-twispay-tw-status-updater.php';
		require_once TWISPAY_PLUGIN_DIR . 'helpers/class-twispay-tw-helper-processor.php';

		$this->language = Twispay_TW_Helper_Processor::get_current_language();

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
	 * via Twispay_TW_Helper_Response::twispay_tw_decrypt_message and ::twispay_tw_check_validation.
	 */
	public function handle() {
		// FIXME: Change this i18n logic with the idiomatic one.
		if ( file_exists( TWISPAY_PLUGIN_DIR . 'lang/' . $this->language . '/lang.php' ) ) {
			require TWISPAY_PLUGIN_DIR . 'lang/' . $this->language . '/lang.php';
		} else {
			require TWISPAY_PLUGIN_DIR . 'lang/en/lang.php';
		}

		/**
		 * Language translation array.
		 *
		 * @var array $tw_lang
		 */

		// Check if the POST is corrupted: doesn't contain the 'opensslResult' and the 'result' fields.
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Webhook callback; verified cryptographically below.
		if ( isset( $_POST['opensslResult'] ) === false && isset( $_POST['result'] ) === false ) {
			Twispay_TW_Logger::twispay_tw_log( esc_html__( '[RESPONSE-ERROR]: Received empty response.', 'xmoney-payments' ) );
			die( esc_html__( '[RESPONSE-ERROR]: Received empty response.', 'xmoney-payments' ) );
		}

		$configuration = Twispay_TW_Helper_Processor::get_configuration();

		if ( empty( $configuration ) ) {
			Twispay_TW_Logger::twispay_tw_log( esc_html__( '[RESPONSE-ERROR]: Private key is not valid.', 'xmoney-payments' ) );
			die( esc_html__( '[RESPONSE-ERROR]: Private key is not valid.', 'xmoney-payments' ) );
		}

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Webhook callback; verified cryptographically below.
		$result    = isset( $_POST['opensslResult'] ) ? sanitize_text_field( wp_unslash( $_POST['opensslResult'] ) ) : sanitize_text_field( wp_unslash( $_POST['result'] ) );
		$decrypted = Twispay_TW_Helper_Response::twispay_tw_decrypt_message(
			$result,
			$configuration['secret_key']
		);

		if ( false === $decrypted ) {
			Twispay_TW_Logger::twispay_tw_log( esc_html__( '[RESPONSE-ERROR]: Decryption failed.', 'xmoney-payments' ) );
			Twispay_TW_Logger::twispay_tw_log( esc_html__( '[RESPONSE-ERROR]: opensslResult: ', 'xmoney-payments' ) . $result );

			die( esc_html__( '[RESPONSE-ERROR]: Decryption failed.', 'xmoney-payments' ) );
		}

		Twispay_TW_Logger::twispay_tw_log( esc_html__( '[RESPONSE]: Decryption successfully performed.', 'xmoney-payments' ) );

		$is_order_valid = Twispay_TW_Helper_Response::twispay_tw_check_validation( $decrypted );

		if ( true !== $is_order_valid ) {
			Twispay_TW_Logger::twispay_tw_log( esc_html__( '[RESPONSE-ERROR]: Validation failed.', 'xmoney-payments' ) );
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
			Twispay_TW_Logger::twispay_tw_log( esc_html__( '[RESPONSE-ERROR]: Invalid externalOrderId format.', 'xmoney-payments' ) );
			die( esc_html__( '[RESPONSE-ERROR]: Invalid externalOrderId format.', 'xmoney-payments' ) );
		}
		$tw_order = wc_get_order( $order_id );

		if ( false === $tw_order ) {
			Twispay_TW_Logger::twispay_tw_log( esc_html__( '[RESPONSE-ERROR]: Order does not exist.', 'xmoney-payments' ) );
			die( esc_html__( '[RESPONSE-ERROR]: Order does not exist.', 'xmoney-payments' ) );
		}

		// Extract the transaction status.
		$status = empty( $decrypted['status'] ) ? $decrypted['transactionStatus'] : $decrypted['status'];

		// Set the status of the WooCommerce order according to the received status.
		Twispay_TW_Status_Updater::update_status_i_p_n( $order_id, $status );

		// Send the 200 OK response back to the xMoney Payments server.
		die( 'OK' );
	}
}
