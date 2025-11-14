<?php
/**
 * Xmoney Payments Payment Transaction Request Page
 *
 * Here is processed all payment transaction actions( refund )
 *
 * @package  Xmoney/Admin
 * @category Admin
 * @author   Xmoney Payments
 */


/* Exit if the file is accessed directly. */
if ( ! defined( 'ABSPATH' ) ) {
	exit; }

/* Require the "Xmoney_Payments_Logger" class. */
require_once XMONEY_PAYMENTS_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'class-xmoney-payments-logger.php';
/* Require the "Xmoney_Payments_Status_Updater" class. */
require_once XMONEY_PAYMENTS_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'class-xmoney-payments-status-updater.php';


/**
 * Xmoney Payments Refund Transaction
 *
 * Process the Refund Transaction to database.
 *
 * @public
 * @return void
 */
function xmoney_payments_refund_payment_transaction() {
	if ( ! isset( $_POST['xmoney_payments_general_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['xmoney_payments_general_nonce'] ) ), 'xmoney_payments_general_action' ) ) {

		wp_die(
			esc_html__( 'Security check failed. Invalid nonce.', 'xmoney-payments' ),
			esc_html__( 'Error', 'xmoney-payments' ),
			array( 'response' => 403 )
		);
	}

	if ( ! edit_posts( 'manage_woocommerce' ) ) {
		wp_die(
			esc_html__( 'You do not have permission to perform this action.', 'xmoney-payments' ),
			esc_html__( 'Error', 'xmoney-payments' ),
			array( 'response' => 403 )
		);
	}

	if ( isset( $_GET['payment_ad'] ) && sanitize_text_field( wp_unslash( $_GET['payment_ad'] ) ) ) {
		$transaction_id_raw = sanitize_text_field( wp_unslash( $_GET['payment_ad'] ) );
		// Enforce strict numeric transaction id to prevent path manipulation/credential leakage.
		if ( ! preg_match( '/^[0-9]+$/', $transaction_id_raw ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=xmoney-payments-transaction&notice=error_refund' ) );
			return;
		}
		$transaction_id = $transaction_id_raw;

		/* Get configuration from database. */
		global $wpdb;
		$api_key = '';

		$table_name = esc_sql( $wpdb->prefix . 'xmoney_payments_configuration' );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are escaped manually and safe.
		$configuration = $wpdb->get_row( "SELECT * FROM {$table_name}", $table_name );

		if ( $configuration ) {
			if ( '1' === $configuration->live_mode ) {
				$api_key = sanitize_text_field( $configuration->live_key );
				$url     = 'https://api.xmoney.com/transaction/' . $transaction_id;
			} elseif ( '0' === $configuration->live_mode ) {
				$api_key = sanitize_text_field( $configuration->staging_key );
				$url     = 'https://api-stage.xmoney.com/transaction/' . $transaction_id;
			}
		}

		$args     = array(
			'method'  => 'DELETE',
			'headers' => array(
				'accept'        => 'application/json',
				'Authorization' => $api_key,
			),
		);
		$response = wp_remote_request( $url, $args );

		if ( 'OK' === $response['response']['message'] ) {
			/* Redirect to the Transaction list Page with success. */
			wp_safe_redirect( admin_url( 'admin.php?page=xmoney-payments-transaction&notice=success_refund' ) );
		} else {
			/* Redirect to the Transaction list Page with error. */
			wp_safe_redirect( admin_url( 'admin.php?page=xmoney-payments-transaction&notice=errorp_refund&emessage=' . rawurlencode( $response['body'] ) ) );
		}
	} else {
		/* Redirect to the Transaction list Page with error. */
		wp_safe_redirect( admin_url( 'admin.php?page=xmoney-payments-transaction&notice=error_refund' ) );
	}
}
add_action( 'xmoney_payments_refund_payment_transaction', 'xmoney_payments_refund_payment_transaction' );


/**
 * Xmoney Payments Recurring Order
 *
 * Process the Recurring Order to database.
 *
 * @public
 * @return void
 */
function xmoney_payments_recurring_order() {
	if ( ! isset( $_POST['xmoney_payments_general_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['xmoney_payments_general_nonce'] ) ), 'xmoney_payments_general_action' ) ) {

		wp_die(
			esc_html__( 'Security check failed. Invalid nonce.', 'xmoney-payments' ),
			esc_html__( 'Error', 'xmoney-payments' ),
			array( 'response' => 403 )
		);
	}

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die(
			esc_html__( 'You do not have permission to perform this action.', 'xmoney-payments' ),
			esc_html__( 'Error', 'xmoney-payments' ),
			array( 'response' => 403 )
		);
	}

	if ( isset( $_GET['order_ad'] ) && sanitize_key( $_GET['order_ad'] ) ) {
		$order_ad_raw = sanitize_key( $_GET['order_ad'] );
		// Order IDs are numeric; enforce to avoid unintended API target manipulation.
		if ( ! preg_match( '/^[0-9]+$/', $order_ad_raw ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=xmoney-payments-transaction&notice=error_recurring' ) );
			return;
		}
		$order_ad = (int) $order_ad_raw;

		/* Get configuration from database. */
		global $wpdb;
		$api_key = '';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$configuration = $wpdb->get_row( 'SELECT * FROM ' . $wpdb->prefix . 'xmoney_payments_configuration' );

		if ( $configuration ) {
			if ( '1' === $configuration->live_mode ) {
				$api_key = sanitize_text_field( $configuration->live_key );
				$url     = 'https://api.xmoney.com/order/' . $order_ad;
			} elseif ( '0' === $configuration->live_mode ) {
				$api_key = sanitize_text_field( $configuration->staging_key );
				$url     = 'https://api-stage.xmoney.com/order/' . $order_ad;
			}
		}

		$args     = array(
			'method'  => 'DELETE',
			'headers' => array(
				'accept'        => 'application/json',
				'Authorization' => $api_key,
			),
		);
		$response = wp_remote_request( $url, $args );

		if ( 'OK' === $response['response']['message'] ) {
			/* Redirect to the Transaction list Page with success. */
			wp_safe_redirect( admin_url( 'admin.php?page=xmoney-payments-transaction&notice=success_recurring' ) );
		} else {
			/* Redirect to the Transaction list Page with error. */
			wp_safe_redirect( admin_url( 'admin.php?page=xmoney-payments-transaction&notice=errorp_refund&emessage=' . rawurlencode( $response['body'] ) ) );
		}
	} else {
		/* Redirect to the Transaction list Page with error. */
		wp_safe_redirect( admin_url( 'admin.php?page=xmoney-payments-transaction&notice=error_recurring' ) );
	}
}
add_action( 'xmoney_payments_recurring_order', 'xmoney_payments_recurring_order' );


/**
 * Xmoney Payments Recurring Order
 *
 * Synchronize the subscription statuses.
 *
 * @public
 * @return void
 */
function xmoney_payments_synchronize_subscriptions() {
	/* Get configuration from database. */
	global $wpdb;
	$api_key = '';
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$configuration = $wpdb->get_row( 'SELECT * FROM ' . $wpdb->prefix . 'xmoney_payments_configuration' );

	if ( $configuration ) {
		if ( '1' === $configuration->live_mode ) {
			$api_key  = sanitize_text_field( $configuration->live_key );
			$base_url = 'https://api.xmoney.com/order?externalOrderId=__EXTERNAL_ORDER_ID__&orderType=recurring&page=1&perPage=1&reverseSorting=0';
		} elseif ( '0' === $configuration->live_mode ) {
			$api_key  = sanitize_text_field( $configuration->staging_key );
			$base_url = 'https://api-stage.xmoney.com/order?externalOrderId=__EXTERNAL_ORDER_ID__&orderType=recurring&page=1&perPage=1&reverseSorting=0';
		}
	}

	/* Load languages. */
	$lang = explode( '-', get_bloginfo( 'language' ) )[0];
	if ( file_exists( XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/' . $lang . '/lang.php' ) ) {
		require XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/' . $lang . '/lang.php';
	} else {
		require XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/en/lang.php';
	}

	/* Extract all the subscriptions. */
	$subscriptions = wcs_get_subscriptions( array( 'subscriptions_per_page' => -1 ) );
	$skip          = false;

	foreach ( $subscriptions as $key => $subscription ) {
		/* Reset skip flag. */
		$skip = false;

		/* Construct the URL. */
		$url = str_replace( '__EXTERNAL_ORDER_ID__', esc_html( $subscription->get_parent_id() ), $base_url );

		/* Execute the request. This means to perform a "GET"/"PUT" request at the specified URL. */
		$args     = array(
			'method'  => 'GET',
			'headers' => array(
				'accept'        => 'application/json',
				'Authorization' => $api_key,
			),
		);
		$response = wp_remote_request( $url, $args );

		/* Check if the CURL call failed. */
		if ( false === $response ) {
			Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( '[RESPONSE-ERROR]: Failed to call server: ', 'xmoney-payments' ) . WP_Error::get_error_message() );
			$skip = true;
		}

		if ( ( false === $skip ) && ( 200 !== wp_remote_retrieve_response_code( $response ) ) ) {
			Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( '[RESPONSE-ERROR]: Unexpected HTTP response code: ', 'xmoney-payments' ) . wp_remote_retrieve_response_code( $response ) );
			$skip = true;
		}

		if ( false === $skip ) {
			$response = json_decode( $response['body'] );

			if ( 'Success' === $response->message ) {

				/* Check if any order was found on the server. */
				if ( $response->pagination->currentItemCount ) {
					/* Synchronize the statuses. */
					Xmoney_Payments_Status_Updater::update_subscription_status( $subscription->get_parent_id(), $response->data[0]->orderStatus );
				} else {
					/* Cancel the local subscription as no order was found on the server. */
					Xmoney_Payments_Status_Updater::update_subscription_status( $subscription->get_parent_id(), Xmoney_Payments_Status_Updater::$result_statuses['CANCEL_OK'] );
				}

				/* Redirect to the Transaction list Page with success. */
				wp_safe_redirect( admin_url( 'admin.php?page=xmoney-payments-transaction&notice=success_recurring' ) );
			} else {
				Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( '[RESPONSE-ERROR]: Failed to set server status for order ID: ', 'xmoney-payments' ) . $subscription->get_parent_id() );
			}
		}
	}
	/* Redirect to the Transaction list Page with message. */
	wp_safe_redirect( admin_url( 'admin.php?page=xmoney-payments-transaction&notice=sync_finished' ) );
}
add_action( 'xmoney_payments_synchronize_subscriptions', 'xmoney_payments_synchronize_subscriptions' );
