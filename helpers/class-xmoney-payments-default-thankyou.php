<?php
/**
 * Xmoney Payments Helpers
 *
 * Redirects user to the order page.
 *
 * @package  Xmoney/Front
 * @category Front
 * @author   Xmoney Payments
 */

/* Exit if the file is accessed directly. */
if ( ! defined( 'ABSPATH' ) ) {
	exit; }

/* Security class check */
if ( ! class_exists( 'Xmoney_Payments_Default_Thankyou' ) ) :
	/**
	 * Xmoney Payments Helper Class
	 *
	 * Class that redirects user to the order page.
	 */
	class Xmoney_Payments_Default_Thankyou extends WC_Payment_Gateway {
		/**
		 * Constructor.
		 *
		 * Redirects user to their order thank you page.
		 *
		 * @param WC_Order $xmoney_payments_order The WooCommerce order object.
		 * @return void
		 */
		public function __construct( $xmoney_payments_order ) {
			wp_safe_redirect( esc_url( $this->get_return_url( $xmoney_payments_order ) ) );
		}
	}
endif; /* End if class_exists. */
