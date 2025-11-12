<?php
/**
 * Xmoney Payments Shortcodes
 *
 * Here is created and processed all shortcodes for Administrator Pages
 *
 * @package  Xmoney/Admin
 * @category Admin
 * @author   Xmoney Payments
 */

// Exit if the file is accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Security class check
if ( ! class_exists( 'Xmoney_Payments_Shortcodes' ) ) :

	/**
	 * Xmoney Payments Shorcodes Class
	 */
	class Xmoney_Payments_Shortcodes {
		/**
		 * Xmoney_Payments_Shortcodes Constructor
		 *
		 * @public
		 * @return void
		 */
		public function __construct() {
			add_shortcode( 'xmoney_payments_payment_confirmation', array( $this, 'xmoney_payments_payment_confirmation_handler' ) );
		}

		/**
		 * Renders the Xmoney Payments Payment Confirmation Form
		 *
		 * @public
		 * @return string Payment Confirmation Form
		 */
		public function xmoney_payments_payment_confirmation_handler() {
			return xmoney_payments_instance()->payment_confirmation->xmoney_payments_payment_confirmation_form();
		}
	}

endif; // End if class_exists

/**
 * The main instance of Xmoney_Payments_Shortcodes
 *
 * @return Xmoney_Payments_Shortcodes
 */
new Xmoney_Payments_Shortcodes();
