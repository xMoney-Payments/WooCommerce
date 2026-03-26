<?php
/**
 * Xmoney Payments Helpers
 *
 * Logs messages and transactions.
 *
 * @package  Xmoney/Front
 * @category Front
 * @author   Xmoney Payments
 */

/* Exit if the file is accessed directly. */
if ( ! defined( 'ABSPATH' ) ) {
	exit; }

/* Security class check */
if ( ! class_exists( 'Xmoney_Payments_Logger' ) ) :
	/**
	 * Xmoney Payments Helper Class
	 *
	 * Class that implements methods to log
	 * messages and transactions.
	 */
	class Xmoney_Payments_Logger {
		/**
		 * Log a transaction to the database.
		 *
		 * @param array $data Array containing the transaction data.
		 * @return void
		 */
		public static function xmoney_payments_log_transaction( array $data ) {
			global $wpdb;

			/* Extract the WooCommerce order. */
			$xmoney_payments_order = wc_get_order( $data['id_cart'] );

			$table = $wpdb->prefix . 'xmoney_payments_transactions';

			// Use id_cart (WooCommerce order ID) for deduplication to avoid
			// collisions when multiple transactions arrive with transactionId = 0.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$already = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $table . ' WHERE id_cart = %s', $data['id_cart'] ) );
			if ( $already ) {
				$update_fields = array( 'status' => $data['status'] );
				$update_format = array( '%s' );

				if ( ! empty( $data['transactionId'] ) ) {
					$update_fields['transactionId'] = (int) $data['transactionId'];
					$update_format[]                = '%d';
				}
				if ( ! empty( $data['orderId'] ) ) {
					$update_fields['orderId'] = (int) $data['orderId'];
					$update_format[]          = '%d';
				}
				if ( ! empty( $data['identifier'] ) ) {
					$update_fields['identifier'] = sanitize_text_field( $data['identifier'] );
					$update_format[]             = '%s';
				}
				if ( ! empty( $data['customerId'] ) ) {
					$update_fields['customerId'] = (int) $data['customerId'];
					$update_format[]             = '%d';
				}
				if ( ! empty( $data['cardId'] ) ) {
					$update_fields['cardId'] = (int) $data['cardId'];
					$update_format[]         = '%d';
				}

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update( $table, $update_fields, array( 'id_cart' => $data['id_cart'] ), $update_format, array( '%s' ) );
			} else {

				$checkout_url = ( ( false !== $xmoney_payments_order ) && ( true !== $xmoney_payments_order ) ) ? ( esc_url( wc_get_checkout_url() . 'order-pay/' . explode( '_', $data['id_cart'] )[0] . '/?pay_for_order=true&key=' . $xmoney_payments_order->get_data()['order_key'] ) ) : ( '' );

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( $wpdb->prepare( 'INSERT INTO `' . $table . '` (`status`, `id_cart`, `identifier`, `orderId`, `transactionId`, `customerId`, `cardId`, `checkout_url`) VALUES (%s, %s, %s, %d, %d, %d, %d, %s)', $data['status'], $data['id_cart'], $data['identifier'], $data['orderId'], $data['transactionId'], $data['customerId'], $data['cardId'], $checkout_url ) );
			}
		}


		/**
		 * Update a transaction's status in the database.
		 *
		 * @param int    $id The parent WooCommerce order ID.
		 * @param string $status The new transaction status.
		 * @return void
		 */
		public static function xmoney_payments_update_transaction_status( $id, $status ) {
			global $wpdb;

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$already = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'xmoney_payments_transactions WHERE id_cart = %d', $id ) );

			if ( $already ) {
				/*
				Update the DB with the transaction data. */
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( $wpdb->prepare( 'UPDATE ' . $wpdb->prefix . 'xmoney_payments_transactions SET status = %s WHERE id_cart = %d', $status, $id ) );
			}
		}

		/**
		 * Update a transaction's transactionId in the database.
		 *
		 * @param int $id The parent WooCommerce order ID.
		 * @param int $transaction_id The new transactionId.
		 * @return void
		 */
		public static function xmoney_payments_update_transaction_id( $id, $transaction_id ) {
			global $wpdb;

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$already = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'xmoney_payments_transactions WHERE orderId = %d', $id ) );

			if ( $already ) {
				/*
				Update the DB with the transaction data. */
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( $wpdb->prepare( 'UPDATE ' . $wpdb->prefix . 'xmoney_payments_transactions SET transactionId = %d WHERE orderId = %d', $transaction_id, $id ) );
			}
		}


		/**
		 * Log a message to the Xmoney Payments log file.
		 *
		 * @param string|array|bool $message The message to log. Arrays will be JSON encoded.
		 * @return void
		 */
		public static function xmoney_payments_log( $message = false ) {
			// Resolve uploads directory and ensure plugin subfolder exists: /uploads/xmoney-payments/logs/
			$uploads = wp_upload_dir();
			if ( ! empty( $uploads['error'] ) ) {
				// If uploads dir isn't available, bail silently to avoid breaking site behavior.
				return;
			}

			$base_dir = trailingslashit( $uploads['basedir'] ) . 'xmoney-payments';
			$log_dir  = trailingslashit( $base_dir ) . 'logs';

			// Create directories
			wp_mkdir_p( $log_dir );

			// Initialize WP_Filesystem for safe file operations.
			if ( ! function_exists( 'WP_Filesystem' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}
			$fs_connected = WP_Filesystem();
			if ( ! $fs_connected ) {
				return; // Fail silently if FS API not available
			}
			global $wp_filesystem;

			// Add index.php (mitigate directory browsing); ignore PHPCS alt functions via WP API only.
			$index_file = trailingslashit( $log_dir ) . 'index.php';
			if ( ! $wp_filesystem->exists( $index_file ) ) {
				$wp_filesystem->put_contents( $index_file, "<?php\n// Silence is golden.\n", FS_CHMOD_FILE );
			}

			$log_file = trailingslashit( $log_dir ) . 'xmoney-payments-log.txt';

			// Build log line; no browser output, so no esc_* needed.
			$line = ( ! $message )
				? ( PHP_EOL . PHP_EOL )
				: ( '[' . gmdate( 'Y-m-d H:i:s' ) . '] ' . ( is_string( $message ) ? $message : wp_json_encode( $message ) ) );

			// Append: read existing then write new combined contents.
			$existing = $wp_filesystem->exists( $log_file ) ? (string) $wp_filesystem->get_contents( $log_file ) : '';
			$wp_filesystem->put_contents( $log_file, $existing . $line . PHP_EOL, FS_CHMOD_FILE );
		}
	}
endif; /* End if class_exists. */
