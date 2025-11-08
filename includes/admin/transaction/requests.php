<?php
/**
 * Twispay Payment Transaction Request Page
 *
 * Here is processed all payment transaction actions( refund )
 *
 * @package  Twispay/Admin
 * @category Admin
 * @author   Twispay
 */


/* Exit if the file is accessed directly. */
if ( ! defined( 'ABSPATH' ) ) {
	exit; }

/* Require the "Twispay_TW_Logger" class. */
require_once TWISPAY_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'Twispay_TW_Logger.php';
/* Require the "Twispay_TW_Status_Updater" class. */
require_once TWISPAY_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'Twispay_TW_Status_Updater.php';


/**
 * Twispay Refund Transaction
 *
 * Process the Refund Transaction to database.
 *
 * @public
 * @return void
 */
function tw_twispay_p_refund_payment_transaction() {
	if ( ! isset( $_POST['twispay_general_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['twispay_general_nonce'] ) ), 'twispay_general_action' ) ) {

		wp_die(
			esc_html__( 'Security check failed. Invalid nonce.', 'xmoney-payments' ),
			esc_html__( 'Error', 'xmoney-payments' ),
			array( 'response' => 403 )
		);
	}

	if ( ! current_user_can( 'manage_woocommerce' ) ) {
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
			wp_safe_redirect( admin_url( 'admin.php?page=tw-transaction&notice=error_refund' ) );
			return;
		}
		$transaction_id = $transaction_id_raw;

		/* Get configuration from database. */
		global $wpdb;
		$apiKey = '';

		$table_name = esc_sql( $wpdb->prefix . 'twispay_tw_configuration' );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are escaped manually and safe.
		$configuration = $wpdb->get_row( "SELECT * FROM {$table_name}", $table_name );

		if ( $configuration ) {
			if ( $configuration->live_mode === '1' ) {
				$apiKey = sanitize_text_field( $configuration->live_key );
				$url    = 'https://api.xmoney.com/transaction/' . $transaction_id;
			} elseif ( $configuration->live_mode === '0' ) {
				$apiKey = sanitize_text_field( $configuration->staging_key );
				$url    = 'https://api-stage.xmoney.com/transaction/' . $transaction_id;
			}
		}

		$args     = array(
			'method'  => 'DELETE',
			'headers' => array(
				'accept'        => 'application/json',
				'Authorization' => $apiKey,
			),
		);
		$response = wp_remote_request( $url, $args );

		if ( $response['response']['message'] === 'OK' ) {
			/* Redirect to the Transaction list Page with success. */
			wp_safe_redirect( admin_url( 'admin.php?page=tw-transaction&notice=success_refund' ) );
		} else {
			/* Redirect to the Transaction list Page with error. */
			wp_safe_redirect( admin_url( 'admin.php?page=tw-transaction&notice=errorp_refund&emessage=' . rawurlencode( $response['body'] ) ) );
		}
	} else {
		/* Redirect to the Transaction list Page with error. */
		wp_safe_redirect( admin_url( 'admin.php?page=tw-transaction&notice=error_refund' ) );
	}
}
add_action( 'tw_refund_payment_transaction', 'tw_twispay_p_refund_payment_transaction' );


/**
 * Twispay Recurring Order
 *
 * Process the Recurring Order to database.
 *
 * @public
 * @return void
 */
function tw_twispay_p_recurring_order( $request ) {
	if ( ! isset( $_POST['twispay_general_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['twispay_general_nonce'] ) ), 'twispay_general_action' ) ) {

		wp_die(
			esc_html__( 'Security check failed. Invalid nonce.', 'xmoney-payments' ),
			esc_html__( 'Error', 'xmoney-payments' ),
			array( 'response' => 403 )
		);
	}

	if ( ! current_user_can( 'manage_woocommerce' ) ) {
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
			wp_safe_redirect( admin_url( 'admin.php?page=tw-transaction&notice=error_recurring' ) );
			return;
		}
		$order_ad = (int) $order_ad_raw;

		/* Get configuration from database. */
		global $wpdb;
		$apiKey = '';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$configuration = $wpdb->get_row( 'SELECT * FROM ' . $wpdb->prefix . 'twispay_tw_configuration' );

		if ( $configuration ) {
			if ( $configuration->live_mode === '1' ) {
				$apiKey = sanitize_text_field( $configuration->live_key );
				$url    = 'https://api.xmoney.com/order/' . $order_ad;
			} elseif ( $configuration->live_mode === '0' ) {
				$apiKey = sanitize_text_field( $configuration->staging_key );
				$url    = 'https://api-stage.xmoney.com/order/' . $order_ad;
			}
		}

		$args     = array(
			'method'  => 'DELETE',
			'headers' => array(
				'accept'        => 'application/json',
				'Authorization' => $apiKey,
			),
		);
		$response = wp_remote_request( $url, $args );

		if ( $response['response']['message'] === 'OK' ) {
			/* Redirect to the Transaction list Page with success. */
			wp_safe_redirect( admin_url( 'admin.php?page=tw-transaction&notice=success_recurring' ) );
		} else {
			/* Redirect to the Transaction list Page with error. */
			wp_safe_redirect( admin_url( 'admin.php?page=tw-transaction&notice=errorp_refund&emessage=' . rawurlencode( $response['body'] ) ) );
		}
	} else {
		/* Redirect to the Transaction list Page with error. */
		wp_safe_redirect( admin_url( 'admin.php?page=tw-transaction&notice=error_recurring' ) );
	}
}
add_action( 'tw_recurring_order', 'tw_twispay_p_recurring_order' );


/**
 * Twispay Recurring Order
 *
 * Synchronize the subscription statuses.
 *
 * @public
 * @return void
 */
function tw_twispay_p_synchronize_subscriptions( $request ) {
	/* Get configuration from database. */
	global $wpdb;
	$apiKey = '';
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$configuration = $wpdb->get_row( 'SELECT * FROM ' . $wpdb->prefix . 'twispay_tw_configuration' );

	if ( $configuration ) {
		if ( $configuration->live_mode === '1' ) {
			$apiKey  = sanitize_text_field( $configuration->live_key );
			$baseUrl = 'https://api.xmoney.com/order?externalOrderId=__EXTERNAL_ORDER_ID__&orderType=recurring&page=1&perPage=1&reverseSorting=0';
		} elseif ( $configuration->live_mode === '0' ) {
			$apiKey  = sanitize_text_field( $configuration->staging_key );
			$baseUrl = 'https://api-stage.xmoney.com/order?externalOrderId=__EXTERNAL_ORDER_ID__&orderType=recurring&page=1&perPage=1&reverseSorting=0';
		}
	}

	/* Load languages. */
	$lang = explode( '-', get_bloginfo( 'language' ) )[0];
	if ( file_exists( TWISPAY_PLUGIN_DIR . 'lang/' . $lang . '/lang.php' ) ) {
		require TWISPAY_PLUGIN_DIR . 'lang/' . $lang . '/lang.php';
	} else {
		require TWISPAY_PLUGIN_DIR . 'lang/en/lang.php';
	}

	/* Extract all the subscriptions. */
	$subscriptions = wcs_get_subscriptions( array( 'subscriptions_per_page' => -1 ) );
	$skip          = false;

	foreach ( $subscriptions as $key => $subscription ) {
		/* Reset skip flag. */
		$skip = false;

		/* Construct the URL. */
		$url = str_replace( '__EXTERNAL_ORDER_ID__', esc_html( $subscription->get_parent_id() ), $baseUrl );

		/* Execute the request. This means to perform a "GET"/"PUT" request at the specified URL. */
		$args     = array(
			'method'  => 'GET',
			'headers' => array(
				'accept'        => 'application/json',
				'Authorization' => $apiKey,
			),
		);
		$response = wp_remote_request( $url, $args );

		/* Check if the CURL call failed. */
		if ( false === $response ) {
			Twispay_TW_Logger::twispay_tw_log( esc_html__( '[RESPONSE-ERROR]: Failed to call server: ', 'xmoney-payments' ) . WP_Error::get_error_message() );
			$skip = true;
		}

		if ( ( false === $skip ) && ( 200 != wp_remote_retrieve_response_code( $response ) ) ) {
			Twispay_TW_Logger::twispay_tw_log( esc_html__( '[RESPONSE-ERROR]: Unexpected HTTP response code: ', 'xmoney-payments' ) . wp_remote_retrieve_response_code( $response ) );
			$skip = true;
		}

		if ( false === $skip ) {
			$response = json_decode( $response['body'] );

			if ( 'Success' === $response->message ) {

				/* Check if any order was found on the server. */
				if ( $response->pagination->currentItemCount ) {
					/* Synchronize the statuses. */
					Twispay_TW_Status_Updater::updateSubscriptionStatus( $subscription->get_parent_id(), $response->data[0]->orderStatus );
				} else {
					/* Cancel the local subscription as no order was found on the server. */
					Twispay_TW_Status_Updater::updateSubscriptionStatus( $subscription->get_parent_id(), Twispay_TW_Status_Updater::$RESULT_STATUSES['CANCEL_OK'] );
				}

				/* Redirect to the Transaction list Page with success. */
				wp_safe_redirect( admin_url( 'admin.php?page=tw-transaction&notice=success_recurring' ) );
			} else {
				Twispay_TW_Logger::twispay_tw_log( esc_html__( '[RESPONSE-ERROR]: Failed to set server status for order ID: ', 'xmoney-payments' ) . $subscription->get_parent_id() );
			}
		}
	}
	/* Redirect to the Transaction list Page with message. */
	wp_safe_redirect( admin_url( 'admin.php?page=tw-transaction&notice=sync_finished' ) );
}
add_action( 'tw_synchronize_subscriptions', 'tw_twispay_p_synchronize_subscriptions' );
