<?php
/**
 * Xmoney Payments Views
 *
 * Render specific views template
 *
 * @package  Xmoney/Front
 * @category Front
 * @author   Xmoney Payments
 */

// Exit if the file is accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Security class check
if ( ! class_exists( 'Xmoney_Payments_Views' ) ) :

	/**
	 * Xmoney Payments Views Class
	 */
	class Xmoney_Payments_Views {
		/**
		 * Xmoney_Payments_Views Constructor
		 *
		 * @public
		 * @return void
		 */
		public function __construct() {
		}

		/**
		 * Render the Front Xmoney Payments View
		 *
		 * @param string $slug The view slug filename (without ".php").
		 * @return void
		 */
		public function xmoney_payments_render_view( $slug ) {
				include XMONEY_PAYMENTS_PLUGIN_DIR . 'views/' . $slug . '.php';
		}
	}

endif; // End if class_exists
