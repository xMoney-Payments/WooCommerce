<?php
/**
 * Twispay Install
 *
 * Installing Twispay user pages, tables, and options.
 *
 * @package  Twispay/Install
 * @category Core
 * @author   Twispay
 */

function twispay_wp_check_install() {
	if( ! get_option( 'twispay_tw_installed' ) ) {
		twispay_tw_install();
	}
}
add_action( 'admin_init', 'twispay_wp_check_install' );

function twispay_tw_install() {
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	update_option( 'twispay_tw_installed', '1' );

	// Create new pages from Twispay Confirmation with shortcodes included
	wp_insert_post(
		array(
			'post_title'     => esc_html__( 'xMoney Payments confirmation', 'xmoney-payments' ),
			'post_content'   => '[tw_payment_confirmation]',
			'post_status'    => 'publish',
			'post_author'    => get_current_user_id(),
			'post_type'      => 'page',
			'comment_status' => 'closed'
		)
	);

	// Create All tables
	global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $twispay_tw_configuration = $wpdb->prefix . 'twispay_tw_configuration';

    $sql_configuration = "CREATE TABLE $twispay_tw_configuration (
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

    dbDelta($sql_configuration);

    $twispay_tw_transactions = $wpdb->prefix . 'twispay_tw_transactions';

    $sql_transactions = "CREATE TABLE $twispay_tw_transactions (
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

    dbDelta($sql_transactions);

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->get_results( "INSERT INTO `" . $wpdb->prefix . "twispay_tw_configuration` (`live_mode`) VALUES (0);" );
}
register_activation_hook( TWISPAY_PLUGIN_DIR, 'twispay_tw_install' );

