<?php
/**
 * Xmoney Payments Uninstall
 *
 * Uninstalling Xmoney Payments deletes user pages, tables, and options.
 *
 * @package  Xmoney/Uninstall
 * @category Core
 * @author   Xmoney Payments
 */
/* Exit if the file is accessed directly. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Exit if the file is accessed directly
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'xmoney_payments_installed' );

// Delete All Xmoney Payments Pages
$xmoney_payments_page = get_page_by_path( 'xmoney-payments-confirmation' );
if ( $xmoney_payments_page ) {
	wp_delete_post( $xmoney_payments_page->ID, true );
}

// Remove All Tables
global $wpdb;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'xmoney_payments_configuration' );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'xmoney_payments_configuration' );
