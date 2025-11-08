<?php
/**
 * Twispay Helpers
 *
 * Decodes and validates notifications sent by the Twispay server.
 *
 * @package  Twispay/Front
 * @category Front
 * @author   Twispay
 */

/* Exit if the file is accessed directly. */
if ( ! defined( 'ABSPATH' ) ) {
	exit; }

/* Require the "Twispay_TW_Logger" class. */
require_once TWISPAY_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'class-twispay-tw-logger.php';

/* Security class check */
if ( ! class_exists( 'Twispay_TW_Helper_Response' ) ) :
	/**
	 * Twispay Helper Class
	 *
	 * Class that implements methods to decrypt
	 * Twispay server responses.
	 */
	class Twispay_TW_Helper_Response {
		/**
		 * Decrypt the response from Twispay server.
		 *
		 * @param string $tw_encrypted_message - The encrypted server message.
		 * @param string $tw_secret_key - The secret key (from Twispay).
		 *
		 * @return bool|array ([key => value,]) - If everything is ok array containing the decrypted data else bool(FALSE) if decription fails.
		 */
		public static function twispay_tw_decrypt_message( string $tw_encrypted_message, string $tw_secret_key ) {
			$encrypted = (string) $tw_encrypted_message;

			if ( ! strlen( $encrypted ) || ( false === strpos( $encrypted, ',' ) ) ) {
				return false;
			}

			/* Get the IV and the encrypted data */
			$encrypted_parts = explode( /*delimiter*/',', $encrypted, /*limit*/2 );
			$iv              = base64_decode( $encrypted_parts[0] );
			if ( false === $iv ) {
				return false;
			}

			$encrypted_data = base64_decode( $encrypted_parts[1] );
			if ( false === $encrypted_data ) {
				return false;
			}

			/* Decrypt the encrypted data */
			$decrypted_response = openssl_decrypt( $encrypted_data, /*method*/'aes-256-cbc', $tw_secret_key, /*options*/OPENSSL_RAW_DATA, $iv );
			if ( false === $decrypted_response ) {
				return false;
			}

			/** JSON decode the decrypted data. */
			$decoded_response = json_decode( $decrypted_response, /*assoc*/true, /*depth*/4 );

			/** Check if the decryption was successful. */
			if ( null === $decoded_response ) {
				/** Log the last error occurred during the last JSON encoding/decoding. */
				switch ( json_last_error() ) {
					case JSON_ERROR_DEPTH:
						Twispay_TW_Logger::twispay_tw_log( esc_html__( 'The maximum stack depth has been exceeded.', 'xmoney-payments' ) );
						break;

					case JSON_ERROR_STATE_MISMATCH:
						Twispay_TW_Logger::twispay_tw_log( esc_html__( 'Invalid or malformed JSON.', 'xmoney-payments' ) );
						break;

					case JSON_ERROR_CTRL_CHAR:
						Twispay_TW_Logger::twispay_tw_log( esc_html__( 'Control character error, possibly incorrectly encoded.', 'xmoney-payments' ) );
						break;

					case JSON_ERROR_SYNTAX:
						Twispay_TW_Logger::twispay_tw_log( esc_html__( 'Syntax error.', 'xmoney-payments' ) );
						break;

					case JSON_ERROR_UTF8:
						Twispay_TW_Logger::twispay_tw_log( esc_html__( 'Malformed UTF-8 characters, possibly incorrectly encoded.', 'xmoney-payments' ) );
						break;

					case JSON_ERROR_RECURSION:
						Twispay_TW_Logger::twispay_tw_log( esc_html__( 'One or more recursive references in the value to be encoded.', 'xmoney-payments' ) );
						break;

					case JSON_ERROR_INF_OR_NAN:
						Twispay_TW_Logger::twispay_tw_log( esc_html__( 'One or more NAN or INF values in the value to be encoded.', 'xmoney-payments' ) );
						break;

					case JSON_ERROR_UNSUPPORTED_TYPE:
						Twispay_TW_Logger::twispay_tw_log( esc_html__( 'A value of a type that cannot be encoded was given.', 'xmoney-payments' ) );
						break;

					case JSON_ERROR_INVALID_PROPERTY_NAME:
						Twispay_TW_Logger::twispay_tw_log( esc_html__( 'A property name that cannot be encoded was given.', 'xmoney-payments' ) );
						break;

					case JSON_ERROR_UTF16:
						Twispay_TW_Logger::twispay_tw_log( esc_html__( 'Malformed UTF-16 characters, possibly incorrectly encoded.', 'xmoney-payments' ) );
						break;

					default:
						Twispay_TW_Logger::twispay_tw_log( esc_html__( 'Unknown error.', 'xmoney-payments' ) );
						break;
				}

				return false;
			}

			/** Check if externalOrderId uses '_' separator */
			if ( false !== strpos( $decoded_response['externalOrderId'], '_' ) ) {
				$exploded_val = explode( '_', $decoded_response['externalOrderId'] )[0];

				/** Check if externalOrderId contains only digits and is not empty. */
				if ( ! empty( $exploded_val ) && ctype_digit( $exploded_val ) ) {
					$decoded_response['externalOrderId'] = $exploded_val;
				}
			}

			return $decoded_response;
		}


		/**
		 * Validate a decrypted response from the xMoney Payments server.
		 *
		 * @param array $tw_response The decrypted and JSON-decoded gateway response.
		 *
		 * @return bool True if validation passes, false otherwise.
		 */
		public static function twispay_tw_check_validation( $tw_response ): bool {
			$tw_errors = array();

			if ( ! $tw_response ) {
				return false;
			}

			if ( empty( $tw_response['status'] ) && empty( $tw_response['transactionStatus'] ) ) {
				$tw_errors[] = esc_html__( '[RESPONSE-ERROR]: Empty status', 'xmoney-payments' );
			}

			if ( empty( $tw_response['identifier'] ) ) {
				$tw_errors[] = esc_html__( '[RESPONSE-ERROR]: Empty identifier', 'xmoney-payments' );
			}

			if ( empty( $tw_response['externalOrderId'] ) ) {
				$tw_errors[] = esc_html__( '[RESPONSE-ERROR]: Empty externalOrderId', 'xmoney-payments' );
			}

			if ( empty( $tw_response['transactionId'] ) ) {
				$tw_errors[] = esc_html__( '[RESPONSE-ERROR]: Empty transactionId', 'xmoney-payments' );
			}

			if ( count( $tw_errors ) > 0 ) {
				foreach ( $tw_errors as $err ) {
					Twispay_TW_Logger::twispay_tw_log( $err );
				}

				return false;
			} else {
				$data = array(
					'id_cart'       => sanitize_text_field( explode( '_', $tw_response['externalOrderId'] )[0] ),
					'status'        => sanitize_text_field( ( empty( $tw_response['status'] ) ) ? ( $tw_response['transactionStatus'] ) : ( $tw_response['status'] ) ),
					'identifier'    => sanitize_text_field( $tw_response['identifier'] ),
					'orderId'       => (int) $tw_response['orderId'],
					'transactionId' => (int) $tw_response['transactionId'],
					'customerId'    => (int) $tw_response['customerId'],
					'cardId'        => ( ! empty( $tw_response['cardId'] ) ) ? ( (int) $tw_response['cardId'] ) : ( 0 ),
				);

				Twispay_TW_Logger::twispay_tw_log( esc_html__( '[RESPONSE]: Data: ', 'xmoney-payments' ) . json_encode( $data ) );

				if ( ! in_array( $data['status'], Twispay_TW_Status_Updater::$result_statuses, true ) ) {
					Twispay_TW_Logger::twispay_tw_log( esc_html__( '[RESPONSE-ERROR]: Wrong status: ', 'xmoney-payments' ) . $data['status'] );
					Twispay_TW_Logger::twispay_tw_log_transaction( $data );

					return false;
				}

				Twispay_TW_Logger::twispay_tw_log_transaction( $data );
				Twispay_TW_Logger::twispay_tw_log( esc_html__( '[RESPONSE]: Validating completed for order ID: ', 'xmoney-payments' ) . $data['id_cart'] );

				return true;
			}
		}
	}
endif; /* End if class_exists. */
