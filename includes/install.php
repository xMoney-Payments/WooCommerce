<?php
/**
 * Xmoney Payments Install
 *
 * Installing Xmoney Payments user pages, tables, and options.
 *
 * @package  Xmoney/Install
 * @category Core
 * @author   Xmoney Payments
 */
/* Exit if the file is accessed directly. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check whether the Xmoney Payments plugin is installed and run installation if not.
 *
 * @return void
 */
function xmoney_payments_wp_check_install() {
	if ( ! get_option( 'xmoney_payments_installed' ) ) {
		xmoney_payments_install();
	}
	update_xmoney_payments_configuration_columns();
}
add_action( 'admin_init', 'xmoney_payments_wp_check_install' );

/**
 * Perform Xmoney Payments plugin installation:
 * - Create plugin database tables.
 * - Insert default configuration row.
 * - Create confirmation page.
 *
 * @return void
 */
function xmoney_payments_install() {
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	update_option( 'xmoney_payments_installed', '1' );

	// Create new pages from Xmoney Payments Confirmation with shortcodes included
	wp_insert_post(
		array(
			'post_title'     => esc_html__( 'xMoney Payments confirmation', 'xmoney-payments' ),
			'post_content'   => '[xmoney_payments_payment_confirmation]',
			'post_status'    => 'publish',
			'post_author'    => get_current_user_id(),
			'post_type'      => 'page',
			'comment_status' => 'closed',
		)
	);

	// Create All tables
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();

	$xmoney_payments_configuration = $wpdb->prefix . 'xmoney_payments_configuration';

	$sql_configuration = "CREATE TABLE $xmoney_payments_configuration (
    id_tw_configuration int(10) NOT NULL AUTO_INCREMENT,
    live_mode int(10) NOT NULL,
    staging_id varchar(255) NOT NULL,
    staging_key varchar(255) NOT NULL,
    live_id varchar(255) NOT NULL,
    live_key varchar(255) NOT NULL,
    thankyou_page varchar(255) NOT NULL DEFAULT '0',
    suppress_email int(10) NOT NULL DEFAULT '0',
    contact_email varchar(50) NOT NULL DEFAULT '0',
    PRIMARY KEY  (id_tw_configuration)
) $charset_collate;";

	$charset_collate = $wpdb->get_charset_collate();

	dbDelta( $sql_configuration );
	update_xmoney_payments_configuration_columns();

	$xmoney_payments_transactions = $wpdb->prefix . 'xmoney_payments_transactions';

	$sql_transactions = "CREATE TABLE $xmoney_payments_transactions (
    id_tw_transactions int(10) NOT NULL AUTO_INCREMENT,
    status varchar(50) NOT NULL,
    checkout_url varchar(255) NOT NULL,
    id_cart int(10) NOT NULL,
    identifier varchar(50) NOT NULL,
    orderId int(10) NOT NULL,
    transactionId int(10) NOT NULL,
    customerId int(10) NOT NULL,
    cardId int(10) NOT NULL,
    PRIMARY KEY  (id_tw_transactions)
) $charset_collate;";

	dbDelta( $sql_transactions );

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->get_results( 'INSERT INTO `' . $wpdb->prefix . 'xmoney_payments_configuration` (`live_mode`) VALUES (0);' );
}

function update_xmoney_payments_configuration_columns() {
	global $wpdb;
	// Ensure inline_checkout column exists
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$col = $wpdb->get_results( 'SHOW COLUMNS FROM `' . $wpdb->prefix . "xmoney_payments_configuration` LIKE 'inline_checkout'" );
	if ( empty( $col ) ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( 'ALTER TABLE `' . $wpdb->prefix . 'xmoney_payments_configuration` ADD COLUMN inline_checkout TINYINT(1) NOT NULL DEFAULT 0' );
	}
}
register_activation_hook( XMONEY_PAYMENTS_PLUGIN_DIR, 'xmoney_payments_install' );
