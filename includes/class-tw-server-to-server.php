<?php
/* Exit if the file is accessed directly. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Twispay_Server_To_Server {
	private string $language;

	public function __construct() {
		require_once TWISPAY_PLUGIN_DIR . 'helpers/Twispay_TW_Logger.php';
		require_once TWISPAY_PLUGIN_DIR . 'helpers/Twispay_TW_Helper_Response.php';
		require_once TWISPAY_PLUGIN_DIR . 'helpers/Twispay_TW_Status_Updater.php';
		require_once TWISPAY_PLUGIN_DIR . 'helpers/Twispay_TW_Helper_Processor.php';

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
	 * via Twispay_TW_Helper_Response::twispay_tw_decrypt_message and ::twispay_tw_checkValidation.
	 */
	public function handle() {
		// FIXME: Change this i18n logic with the idiomatic one.
		if ( file_exists( TWISPAY_PLUGIN_DIR . 'lang/' . $this->language . '/lang.php' ) ) {
			require TWISPAY_PLUGIN_DIR . 'lang/' . $this->language . '/lang.php';
		} else {
			require TWISPAY_PLUGIN_DIR . 'lang/en/lang.php';
		}

		/** @var array $tw_lang */

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

		if ( $decrypted === false ) {
			Twispay_TW_Logger::twispay_tw_log( esc_html__( '[RESPONSE-ERROR]: Decryption failed.', 'xmoney-payments' ) );
			Twispay_TW_Logger::twispay_tw_log( esc_html__( '[RESPONSE-ERROR]: opensslResult: ', 'xmoney-payments' ) . $result );

			die( esc_html__( '[RESPONSE-ERROR]: Decryption failed.', 'xmoney-payments' ) );
		}

		Twispay_TW_Logger::twispay_tw_log( esc_html__( '[RESPONSE]: Decryption successfully performed.', 'xmoney-payments' ) );

		$is_order_valid = Twispay_TW_Helper_Response::twispay_tw_checkValidation( $decrypted );

		if ( $is_order_valid !== true ) {
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
		if ( $order_id <= 0 ) {
			Twispay_TW_Logger::twispay_tw_log( esc_html__( '[RESPONSE-ERROR]: Invalid externalOrderId format.', 'xmoney-payments' ) );
			die( esc_html__( '[RESPONSE-ERROR]: Invalid externalOrderId format.', 'xmoney-payments' ) );
		}
		$tw_order = wc_get_order( $order_id );

		if ( $tw_order === false ) {
			Twispay_TW_Logger::twispay_tw_log( esc_html__( '[RESPONSE-ERROR]: Order does not exist.', 'xmoney-payments' ) );
			die( esc_html__( '[RESPONSE-ERROR]: Order does not exist.', 'xmoney-payments' ) );
		}

		// Extract the transaction status.
		$status = empty( $decrypted['status'] ) ? $decrypted['transactionStatus'] : $decrypted['status'];

		// Set the status of the WooCommerce order according to the received status.
		Twispay_TW_Status_Updater::updateStatus_IPN( $order_id, $status );

		// Send the 200 OK response back to the xMoney Payments server.
		die( 'OK' );
	}
}
