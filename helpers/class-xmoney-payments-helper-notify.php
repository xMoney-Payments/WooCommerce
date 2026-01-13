<?php
/**
 * Xmoney Payments Helpers
 *
 * Encodes notifications sent to the xmoney platform.
 *
 * @package  Xmoney/Front
 * @category Front
 * @author   Xmoney Payments
 */

/* Exit if the file is accessed directly. */
if ( ! defined( 'ABSPATH' ) ) {
	exit; }

/* Security class check */
if ( ! class_exists( 'Xmoney_Payments_Helper_Notify' ) ) :
	/**
	 * Xmoney Payments Helper Class
	 *
	 * Class that implements methods to get the value
	 * of `jsonRequest` and `checksum` that need to be
	 * sent by POST when making a Xmoney Payments order.
	 */
	class Xmoney_Payments_Helper_Notify {
		/**
		 * Get the `jsonRequest` parameter (order parameters as JSON and base64 encoded).
		 *
		 * @param array $order_data The order parameters.
		 *
		 * @return string
		 */
		public static function get_base64_json_request( array $order_data ): string {
			return base64_encode( json_encode( $order_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) );
		}


		/**
		 * Get the `checksum` parameter (the checksum computed over the `jsonRequest` and base64 encoded).
		 *
		 * @param array  $order_data The order parameters.
		 * @param string $secret_key The secret key (from Xmoney).
		 *
		 * @return string
		 */
		public static function get_base64_checksum( array $order_data, $secret_key ): string {
			$hmac_sha512 = hash_hmac( /*algo*/'sha512', json_encode( $order_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $secret_key, /*raw_output*/true );
			return base64_encode( $hmac_sha512 );
		}
	}
endif; /* End if class_exists. */
