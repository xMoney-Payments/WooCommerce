<?php
/**
 * Xmoney Payments Helpers
 *
 * Decodes and validates notifications sent by the Xmoney server.
 *
 * @package  Xmoney/Front
 * @category Front
 * @author   Xmoney Payments
 */

/* Exit if the file is accessed directly. */
if ( ! defined( 'ABSPATH' ) ) {
	exit; }

/* Require the "Xmoney_Payments_Logger" class. */
require_once XMONEY_PAYMENTS_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'class-xmoney-payments-logger.php';

/* Security class check */
if ( ! class_exists( 'Xmoney_Payments_Helper_Response' ) ) :
	/**
	 * Xmoney_Payments Helper Class
	 *
	 * Class that implements methods to decrypt
	 * Xmoney_Payments server responses.
	 */
	class Xmoney_Payments_Helper_Response {
		/**
		 * Decrypt the response from Twispay server.
		 *
		 * @param string $xmoney_payments_encrypted_message - The encrypted server message.
		 * @param string $xmoney_payments_secret_key - The secret key (from Twispay).
		 *
		 * @return bool|array ([key => value,]) - If everything is ok array containing the decrypted data else bool(FALSE) if decription fails.
		 */
		public static function xmoney_payments_decrypt_message( string $xmoney_payments_encrypted_message, string $xmoney_payments_secret_key ) {
			$encrypted = (string) $xmoney_payments_encrypted_message;

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
			$decrypted_response = openssl_decrypt( $encrypted_data, /*method*/'aes-256-cbc', $xmoney_payments_secret_key, /*options*/OPENSSL_RAW_DATA, $iv );
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
						Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( 'The maximum stack depth has been exceeded.', 'xmoney-payments' ) );
						break;

					case JSON_ERROR_STATE_MISMATCH:
						Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( 'Invalid or malformed JSON.', 'xmoney-payments' ) );
						break;

					case JSON_ERROR_CTRL_CHAR:
						Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( 'Control character error, possibly incorrectly encoded.', 'xmoney-payments' ) );
						break;

					case JSON_ERROR_SYNTAX:
						Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( 'Syntax error.', 'xmoney-payments' ) );
						break;

					case JSON_ERROR_UTF8:
						Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( 'Malformed UTF-8 characters, possibly incorrectly encoded.', 'xmoney-payments' ) );
						break;

					case JSON_ERROR_RECURSION:
						Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( 'One or more recursive references in the value to be encoded.', 'xmoney-payments' ) );
						break;

					case JSON_ERROR_INF_OR_NAN:
						Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( 'One or more NAN or INF values in the value to be encoded.', 'xmoney-payments' ) );
						break;

					case JSON_ERROR_UNSUPPORTED_TYPE:
						Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( 'A value of a type that cannot be encoded was given.', 'xmoney-payments' ) );
						break;

					case JSON_ERROR_INVALID_PROPERTY_NAME:
						Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( 'A property name that cannot be encoded was given.', 'xmoney-payments' ) );
						break;

					case JSON_ERROR_UTF16:
						Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( 'Malformed UTF-16 characters, possibly incorrectly encoded.', 'xmoney-payments' ) );
						break;

					default:
						Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( 'Unknown error.', 'xmoney-payments' ) );
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
		 * @param array $xmoney_payments_response The decrypted and JSON-decoded gateway response.
		 *
		 * @return bool True if validation passes, false otherwise.
		 */
		public static function xmoney_payments_check_validation( $xmoney_payments_response ): bool {
			$xmoney_payments_errors = array();

			if ( ! $xmoney_payments_response ) {
				return false;
			}

			if ( empty( $xmoney_payments_response['status'] ) && empty( $xmoney_payments_response['transactionStatus'] ) ) {
				$xmoney_payments_errors[] = esc_html__( '[RESPONSE-ERROR]: Empty status', 'xmoney-payments' );
			}

			if ( empty( $xmoney_payments_response['identifier'] ) ) {
				$xmoney_payments_errors[] = esc_html__( '[RESPONSE-ERROR]: Empty identifier', 'xmoney-payments' );
			}

			if ( empty( $xmoney_payments_response['externalOrderId'] ) ) {
				$xmoney_payments_errors[] = esc_html__( '[RESPONSE-ERROR]: Empty externalOrderId', 'xmoney-payments' );
			}

			if ( empty( $xmoney_payments_response['transactionId'] ) ) {
				$xmoney_payments_errors[] = esc_html__( '[RESPONSE-ERROR]: Empty transactionId', 'xmoney-payments' );
			}

			if ( count( $xmoney_payments_errors ) > 0 ) {
				foreach ( $xmoney_payments_errors as $err ) {
					Xmoney_Payments_Logger::xmoney_payments_log( $err );
				}

				return false;
			} else {
				$data = array(
					'id_cart'       => sanitize_text_field( explode( '_', $xmoney_payments_response['externalOrderId'] )[0] ),
					'status'        => sanitize_text_field( ( empty( $xmoney_payments_response['status'] ) ) ? ( $xmoney_payments_response['transactionStatus'] ) : ( $xmoney_payments_response['status'] ) ),
					'identifier'    => sanitize_text_field( $xmoney_payments_response['identifier'] ),
					'orderId'       => (int) $xmoney_payments_response['orderId'],
					'transactionId' => (int) $xmoney_payments_response['transactionId'],
					'customerId'    => (int) $xmoney_payments_response['customerId'],
					'cardId'        => ( ! empty( $xmoney_payments_response['cardId'] ) ) ? ( (int) $xmoney_payments_response['cardId'] ) : ( 0 ),
				);

				Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( '[RESPONSE]: Data: ', 'xmoney-payments' ) . json_encode( $data ) );

				if ( ! in_array( $data['status'], Xmoney_Payments_Status_Updater::$result_statuses, true ) ) {
					Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( '[RESPONSE-ERROR]: Wrong status: ', 'xmoney-payments' ) . $data['status'] );
					Xmoney_Payments_Logger::xmoney_payments_log_transaction( $data );

					return false;
				}

				Xmoney_Payments_Logger::xmoney_payments_log_transaction( $data );
				Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( '[RESPONSE]: Validating completed for order ID: ', 'xmoney-payments' ) . $data['id_cart'] );

				return true;
			}
		}
	}
endif; /* End if class_exists. */
