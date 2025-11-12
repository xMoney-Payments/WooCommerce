<?php
/**
 * Xmoney Payments Main Request Page
 *
 * Here is processed all actions. They will be sent later to their controllers
 *
 * @package  Xmoney/Admin
 * @category Admin
 * @author   Xmoney Payments
 */
/* Exit if the file is accessed directly. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hooks every Xmoney Payments actions and process them in order to be
 * sent later to their own controllers.
 *
 * @public
 * @return void
 */
function xmoney_payments_main_action() {

	if ( isset( $_POST['xmoney_payments_general_action'] ) ) {

		/* Nonce check */
		if (
			! isset( $_POST['xmoney_payments_general_nonce'] ) ||
			! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['xmoney_payments_general_nonce'] ) ),
				'xmoney_payments_general_action'
			)
		) {
			wp_die(
				esc_html__( 'You do not have permission to access this file.', 'xmoney-payments' ),
				esc_html__( 'Error', 'xmoney-payments' ),
				array( 'response' => 403 )
			);
		}

		/* Sanitize the requested action */
		$action = sanitize_key( wp_unslash( $_POST['xmoney_payments_general_action'] ) );

		/**
		 * Whitelisted admin actions & their input fields.
		 */
		$allowed_fields = array(

			'edit_general_configuration' => array(
				'live_mode'           => 'text',
				'staging_site_id'     => 'text',
				'staging_private_key' => 'text',
				'live_site_id'        => 'text',
				'live_private_key'    => 'text',
				'wp_pages'            => 'text',
				'suppress_email'      => 'text',
				'contact_email_o'     => 'text',
			),

			'refund_payment_transaction' => array(
				'payment_ad' => 'absint',
			),

			'recurring_order'            => array(
				'order_ad' => 'absint',
			),

			'synchronize_subscriptions'  => array(),
		);

		/* Validate the requested action */
		if ( ! isset( $allowed_fields[ $action ] ) ) {
			wp_die(
				esc_html__( 'Invalid request.', 'xmoney-payments' ),
				esc_html__( 'Error', 'xmoney-payments' ),
				array( 'response' => 403 )
			);
		}

		/**
		 * Capability enforcement
		 */
		$required_capability = ( 'edit_general_configuration' === $action )
			? 'manage_options'
			: 'manage_woocommerce';

		if ( ! current_user_can( $required_capability ) ) {
			wp_die(
				esc_html__( 'You do not have permission to perform this action.', 'xmoney-payments' ),
				esc_html__( 'Error', 'xmoney-payments' ),
				array( 'response' => 403 )
			);
		}

		/* Ensure admin context */
		if ( ! is_admin() ) {
			wp_die(
				esc_html__( 'Access is not allowed from this location.', 'xmoney-payments' ),
				esc_html__( 'Error', 'xmoney-payments' ),
				array( 'response' => 403 )
			);
		}

		/* Sanitize arguments for the selected action (PHPCS Safe) */
		$args = array();

		foreach ( $allowed_fields[ $action ] as $field => $type ) {

			if ( isset( $_POST[ $field ] ) ) {

				switch ( $type ) {

					case 'absint':
						$args[ $field ] = absint( wp_unslash( $_POST[ $field ] ) );
						break;

					case 'text':
					default:
						$args[ $field ] = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
						break;
				}
			}
		}

		/**
		 * Direct Routing (No dynamic hook execution)
		 */
		switch ( $action ) {

			case 'edit_general_configuration':
				xmoney_payments_edit_general_configuration( $args );
				break;

			case 'refund_payment_transaction':
				xmoney_payments_refund_payment_transaction();
				break;

			case 'recurring_order':
				xmoney_payments_recurring_order( $args );
				break;

			case 'synchronize_subscriptions':
				xmoney_payments_synchronize_subscriptions( $args );
				break;
		}
	}
}

add_action( 'admin_init', 'xmoney_payments_main_action' );
