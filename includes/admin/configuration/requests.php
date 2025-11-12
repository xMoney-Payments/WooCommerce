<?php
/**
 * Xmoney Payments Configuration Request Page
 *
 * Here is processed all configuration actions( edit )
 *
 * @package Xmoney_Payments_Payment_Gateway
 * @category Admin
 * @author   Xmoney Payments
 */

/* Exit if the file is accessed directly. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Xmoney Payments Edit Configuration.
 *
 * Process the edit configuration request and store values into the database.
 *
 * @param array $request {
 *     Array with all arguments required for editing configuration in the database.
 *
 * @type string $live_mode Value '1' for Production Mode, or '0' for Staging Mode.
 * @type string $staging_site_id The Site ID for Staging Mode.
 * @type string $staging_private_key The Private Key for Staging Mode.
 * @type string $live_site_id The Site ID for Live Mode.
 * @type string $live_private_key The Private Key for Live Mode.
 * @type string $thankyou_page The path for the custom Thank You page, or '0' to use the default page.
 * @type string $suppress_email Whether to suppress WooCommerce payment emails ('1' or '0').
 * @type string $contact_email_o Optional contact email shown on failed payment pages.
 * }
 *
 * @return void
 */
function xmoney_payments_edit_general_configuration( $request ) {
	$live_mode           = sanitize_text_field( wp_unslash( $request['live_mode'] ) );
	$staging_site_id     = sanitize_text_field( wp_unslash( $request['staging_site_id'] ) );
	$staging_private_key = sanitize_text_field( wp_unslash( $request['staging_private_key'] ) );
	$live_site_id        = sanitize_text_field( wp_unslash( $request['live_site_id'] ) );
	$live_private_key    = sanitize_text_field( wp_unslash( $request['live_private_key'] ) );
	$thankyou_page       = sanitize_text_field( wp_unslash( $request['wp_pages'] ) );
	$suppress_email      = sanitize_text_field( wp_unslash( $request['suppress_email'] ) );
	$contact_email_o     = sanitize_email( wp_unslash( $request['contact_email_o'] ) );

	if ( '' === $contact_email_o ) {
		$contact_email_o = 0;
	}

	// WordPress database refference
	global $wpdb;
	$table_name = esc_sql( $wpdb->prefix . 'xmoney_payments_configuration' );

	// Check if the Configuration row exist into Database
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are escaped manually and safe.
	$configuration = $wpdb->get_results( "SELECT * FROM {$table_name}" );

	if ( $configuration ) {
		// Edit the Configuration into Database ( xmoney_payments_configuration table )
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table_name,
			array(
				'live_mode'      => $live_mode,
				'staging_id'     => $staging_site_id,
				'staging_key'    => $staging_private_key,
				'live_id'        => $live_site_id,
				'live_key'       => $live_private_key,
				'thankyou_page'  => $thankyou_page,
				'suppress_email' => $suppress_email,
				'contact_email'  => $contact_email_o,
			),
			array(
				'id_tw_configuration' => $configuration[0]->id_tw_configuration,
			)
		);
	} else {
		// If by any chance the configuration row does not exist, add default one immediately. ( xmoney_payments_configuration table )
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$table_name,
			array(
				'live_mode' => 0,
			)
		);

		// Edit the Configuration into Database ( xmoney_payments_configuration table )
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table_name,
			array(
				'live_mode'      => $live_mode,
				'staging_id'     => $staging_site_id,
				'staging_key'    => $staging_private_key,
				'live_id'        => $live_site_id,
				'live_key'       => $live_private_key,
				'thankyou_page'  => $thankyou_page,
				'suppress_email' => $suppress_email,
				'contact_email'  => $contact_email_o,
			),
			array(
				'id_tw_configuration' => $wpdb->insert_id,
			)
		);
	}

	// Redirect to the Configuration Page
	$redirect_url = add_query_arg(
		array(
			'notice'                 => 'edit_configuration',
			'xmoney_payments_notice_nonce' => wp_create_nonce( 'xmoney_payments_notice_action' ),
		),
		admin_url( 'admin.php?page=xmoney-payments' )
	);

	wp_safe_redirect( $redirect_url );
	exit;
}
add_action( 'xmoney_payments_edit_general_configuration', 'xmoney_payments_edit_general_configuration' );
