<?php
/**
 * Xmoney Payments Transaction Log Admin Page
 *
 * Xmoney Payments transaction log page on the Administrator dashboard
 *
 * @package  Xmoney/Admin
 * @category Admin
 * @author   Xmoney Payments
 */

// Exit if the file is accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the Xmoney Payments transaction log administrator page.
 *
 * @return void
 */
function xmoney_payments_transaction_log_administrator() {
	// Load languages
	$lang = explode( '-', get_bloginfo( 'language' ) );
	$lang = $lang[0];
	if ( file_exists( XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/' . $lang . '/lang.php' ) ) {
		require XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/' . $lang . '/lang.php';
	} else {
		require XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/en/lang.php';
	}

	if ( ! class_exists( 'WooCommerce' ) ) {
		?>
			<div class="error notice" style="margin-top: 20px;">
				<p><?php echo esc_html__( 'xMoney Payments requires WooCommerce plugin to work normally. Please activate it or install it from', 'xmoney-payments' ); ?> <a target="_blank" href="https://wordpress.org/plugins/woocommerce/"><?php echo esc_html__( 'here', 'xmoney-payments' ); ?></a>.</p>
				<div class="clearfix"></div>
			</div>
		<?php
	} else {
		?>
			<div class="wrap">
				<h1><?php echo esc_html__( 'Transaction log', 'xmoney-payments' ); ?></h1>
				<p><?php echo esc_html__( 'Transaction log in raw form.', 'xmoney-payments' ); ?></p>
				<?php
					$uploads  = wp_upload_dir();
					$log_file = trailingslashit( $uploads['basedir'] ) . 'xmoney-payments/logs/xmoney-payments-log.txt';

				if ( file_exists( $log_file ) ) {
					$content = '';
					if ( is_readable( $log_file ) ) {
						$file_contents = file_get_contents( $log_file );
						if ( false !== $file_contents ) {
							$content = $file_contents;
						}
					}
					echo '<textarea readonly style="width: 900px; height: 386px; margin-top: 10px;">' . esc_textarea( $content ) . '</textarea>';
				} else {
					echo '<p>' . esc_html__( 'No log recorded yet.', 'xmoney-payments' ) . '</p>';
				}
				?>
			</div>
		<?php
	}
}
