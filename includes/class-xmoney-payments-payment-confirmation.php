<?php
/**
 * Xmoney Payments Payment Confirmation
 *
 * Xmoney Payments Payment Confirmation process ( setup_form )
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
if ( ! class_exists( 'Xmoney_Payments_Payment_Confirmation' ) ) :

	/**
	 * Xmoney Payments Payment Confirmation Class
	 */
	class Xmoney_Payments_Payment_Confirmation {
		/**
		 * Xmoney_Payments_Payment_Confirmation Constructor
		 *
		 * @public
		 * @return void
		 */
		public function __construct() {
		}

		/**
		 * Call and render the Xmoney Payments Payment Confirmation Form
		 *
		 * @public
		 * @return string Payment Confirmation Form
		 */
		public function xmoney_payments_payment_confirmation_form() {
			return xmoney_payments_instance()->views->xmoney_payments_render_view( 'payment-confirmation' );
		}
	}

endif; // End if class_exists
