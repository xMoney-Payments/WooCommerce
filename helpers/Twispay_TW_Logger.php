<?php
/**
 * Twispay Helpers
 *
 * Logs messages and transactions.
 *
 * @package  Twispay/Front
 * @category Front
 * @author   Twispay
 */

/* Exit if the file is accessed directly. */
if ( !defined('ABSPATH') ) { exit; }

/* Security class check */
if ( ! class_exists( 'Twispay_TW_Logger' ) ) :
    /**
     * Twispay Helper Class
     *
     * Class that implements methods to log
     * messages and transactions.
     */
    class Twispay_TW_Logger{
        /**
         * Function that logs a transaction to the DB.
         *
         * @param $data Array containing the transaction data.
         *
         * @return void
         */
        public static function twispay_tw_logTransaction(array $data ) {
            global $wpdb;

            /* Extract the WooCommerce order. */
            $order = wc_get_order($data['id_cart']);

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $already = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . "twispay_tw_transactions WHERE transactionId = %s", $data['transactionId']) );
            if ( $already ) {
                /* Update the DB with the transaction data. */
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->query( $wpdb->prepare( "UPDATE " . $wpdb->prefix . "twispay_tw_transactions SET status = %s WHERE transactionId = %d", $data['status'], $data['transactionId'] ) );
            } else {

                $checkout_url = ((false !== $order) && (true !== $order)) ? ( esc_url( wc_get_checkout_url() . 'order-pay/' . explode('_', $data['id_cart'])[0] . '/?pay_for_order=true&key=' . $order->get_data()['order_key']) ) : ("");

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->get_results( $wpdb->prepare( "INSERT INTO `" . $wpdb->prefix . "twispay_tw_transactions` (`status`, `id_cart`, `identifier`, `orderId`, `transactionId`, `customerId`, `cardId`, `checkout_url`) VALUES (%s, %s, %s, %d, %d, %d, %d, %s);", $data['status'], $data['id_cart'], $data['identifier'], $data['orderId'], $data['transactionId'], $data['customerId'], $data['cardId'], $checkout_url ) );
            }
        }


        /**
         * Function that updates a transaction's status in the DB.
         *
         * @param $id - The ID of the parent order.
         * @param $status - The new status of the transaction.
         *
         * @return void
         */
        public static function twispay_tw_updateTransactionStatus( $id, $status ) {
            global $wpdb;

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $already = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . "twispay_tw_transactions WHERE id_cart = %d", $id) );

            if ( $already ) {
                /* Update the DB with the transaction data. */
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->query( $wpdb->prepare( "UPDATE " . $wpdb->prefix . "twispay_tw_transactions SET status = %s WHERE id_cart = %d", $status, $id ) );
            }
        }


        /**
         * Function that logs a message to the log file.
         *
         * @param string - Message to log to file.
         *
         * @return Void
         */
        public static function twispay_tw_log( $message = FALSE ) {
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

            $log_file = trailingslashit( $log_dir ) . 'twispay-log.txt';

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
