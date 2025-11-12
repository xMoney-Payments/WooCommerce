<?php
/**
 * Xmoney Payments Payment Confirmation View
 *
 * Html Payment Confirmation View
 *
 * @package  Xmoney/Front
 * @category Front
 * @author   Xmoney Payments
 */
/* Exit if the file is accessed directly. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Load languages */
$lang = explode( '-', get_bloginfo( 'language' ) )[0];
if ( file_exists( XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/' . $lang . '/lang.php' ) ) {
	require XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/' . $lang . '/lang.php';
} else {
	require XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/en/lang.php';
}

/* Require the "Xmoney_Payments_Logger" class. */
require_once XMONEY_PAYMENTS_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'class-xmoney-payments-logger.php';
/* Require the "Xmoney_Payments_Helper_Response" class. */
require_once XMONEY_PAYMENTS_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'class-xmoney-payments-helper-response.php';
/* Require the "Xmoney_Payments_Status_Updater" class. */
require_once XMONEY_PAYMENTS_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'class-xmoney-payments-status-updater.php';
/* Require the "Xmoney_Payments_Default_Thankyou" class. */
require_once XMONEY_PAYMENTS_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'class-xmoney-payments-default-thankyou.php';


/* Validate if 'WooCommerce' is NOT installed. */
if ( ! class_exists( 'WooCommerce' ) ) {
	?>
	<div class="error notice" style="margin-top: 20px;">
		<h3><?php echo esc_html__( 'An error occurred:', 'xmoney-payments' ); ?></h3>

		<p>
			<?php echo esc_html__( 'xMoney Payments requires WooCommerce plugin to work normally. Please activate it or install it from', 'xmoney-payments' ); ?>

			<a target="_blank"
				rel="noreferrer noopener"
				href="https://wordpress.org/plugins/woocommerce/">
				<?php echo esc_html__( 'here', 'xmoney-payments' ); ?>
			</a>
		</p>
	</div>

	<?php

	die();
}


/* Get configuration from database. */
global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$configuration = $wpdb->get_row( 'SELECT * FROM ' . $wpdb->prefix . 'xmoney_payments_configuration' );


$secret_key = '';
if ( $configuration ) {
	if ( '1' === $configuration->live_mode ) {
		$secret_key = $configuration->live_key;
	} elseif ( '0' === $configuration->live_mode ) {
		$secret_key = $configuration->staging_key;
	} else {
		// Load process css & js files
		wp_enqueue_style( 'ma-payment-confirmation-css', XMONEY_PAYMENTS_PLUGIN_URL . 'assets/css/payment-confirmation.css', array(), XMONEY_PAYMENTS_VERSION, true );
		die( esc_html__( 'Missing configuration for plugin.', 'xmoney-payments' ) );
	}
}


/*
Check if the POST is corrupted: Doesn't contain the 'opensslResult' and the 'result' fields. */
											/*
											OR */
/*
Check if the 'backUrl' is corrupted: Doesn't contain the 'secure_key' field or nonce. */
// Verify nonce from either POST or GET without relying on $_REQUEST
$received_nonce = '';
if ( isset( $_POST['_wpnonce'] ) ) {
	$received_nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) );
} elseif ( isset( $_GET['_wpnonce'] ) ) {
	$received_nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );
}
if ( empty( $received_nonce ) || ! wp_verify_nonce( $received_nonce, 'xmoney_payments_process' ) ) {
	if ( ( ( false === isset( $_POST['opensslResult'] ) ) && ( false === isset( $_POST['result'] ) ) ) || ( false === isset( $_GET['secure_key'] ) ) ) {
		Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( '[RESPONSE-ERROR]: Received empty response.', 'xmoney-payments' ) );
		?>
		<div class="error notice" style="margin-top: 20px;">
			<h3><?php echo esc_html__( 'An error occurred:', 'xmoney-payments' ); ?></h3>

			<p>
				<?php echo esc_html__( 'The payment could not be processed. Please', 'xmoney-payments' ); ?>

				<a href="<?php echo esc_url( wc_get_cart_url() ); ?>">
					<?php echo esc_html__( ' try again', 'xmoney-payments' ); ?>
				</a>

				<?php if ( '0' === $configuration->contact_email ) { ?>
					<?php
					printf(
						'%s %s %s',
						esc_html__( ' or', 'xmoney-payments' ),
						esc_html__( ' contact', 'xmoney-payments' ),
						esc_html__( ' the website administrator.', 'xmoney-payments' )
					);
					?>
				<?php } else { ?>
					<?php echo esc_html__( ' or', 'xmoney-payments' ); ?>

					<a href="<?php echo esc_url( 'mailto:' . sanitize_email( $configuration->contact_email ) ); ?>">
						<?php echo esc_html__( ' contact', 'xmoney-payments' ); ?>
					</a>

					<?php echo esc_html__( ' the website administrator.', 'xmoney-payments' ); ?>
				<?php } ?>
			</p>
		</div>
		<?php

		die();
	}
}

/* Check if there is NO secret key. */
if ( '' === $secret_key ) {
	Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( '[RESPONSE-ERROR]: Private key is not valid.', 'xmoney-payments' ) );
	?>
	<div class="error notice" style="margin-top: 20px;">
		<h3><?php echo esc_html__( 'An error occurred:', 'xmoney-payments' ); ?></h3>

		<span><?php echo esc_html__( ' Private key is not valid.', 'xmoney-payments' ); ?></span>

		<p>
			<?php echo esc_html__( 'The payment could not be processed. Please', 'xmoney-payments' ); ?>

			<a href="<?php echo esc_url( wc_get_cart_url() ); ?>">
				<?php echo esc_html__( ' try again', 'xmoney-payments' ); ?>
			</a>

			<?php if ( '0' === $configuration->contact_email ) { ?>
				<?php
				printf(
					'%s %s %s',
					esc_html__( ' or', 'xmoney-payments' ),
					esc_html__( ' contact', 'xmoney-payments' ),
					esc_html__( ' the website administrator.', 'xmoney-payments' )
				);
				?>
			<?php } else { ?>
				<?php echo esc_html__( ' or', 'xmoney-payments' ); ?>

				<a href="<?php echo esc_url( 'mailto:' . sanitize_email( $configuration->contact_email ) ); ?>">
					<?php echo esc_html__( ' contact', 'xmoney-payments' ); ?>
				</a>

				<?php echo esc_html__( ' the website administrator.', 'xmoney-payments' ); ?>
			<?php } ?>
		</p>
	</div>
	<?php

	die();
}


/* Extract the server response and decrypt it. */
$decrypted = Xmoney_Payments_Helper_Response::xmoney_payments_decrypt_message( /*tw_encryptedResponse*/( isset( $_POST['opensslResult'] ) ) ? ( esc_html( sanitize_text_field( wp_unslash( $_POST['opensslResult'] ) ) ) ) : ( esc_html( sanitize_text_field( wp_unslash( $_POST['result'] ) ) ) ), $secret_key );

/* Check if decryption failed.  */
if ( false === $decrypted ) {
	Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( '[RESPONSE-ERROR]: Decryption failed.', 'xmoney-payments' ) );
	?>
	<div class="error notice" style="margin-top: 20px;">
		<h3><?php echo esc_html__( 'An error occurred:', 'xmoney-payments' ); ?></h3>

		<p>
			<?php echo esc_html__( 'The payment could not be processed. Please', 'xmoney-payments' ); ?>

			<a href="<?php echo esc_url( wc_get_cart_url() ); ?>">
				<?php echo esc_html__( ' try again', 'xmoney-payments' ); ?>
			</a>

			<?php if ( '0' === $configuration->contact_email ) { ?>
				<?php
				printf(
					'%s %s %s',
					esc_html__( ' or', 'xmoney-payments' ),
					esc_html__( ' contact', 'xmoney-payments' ),
					esc_html__( ' the website administrator.', 'xmoney-payments' )
				);
				?>
			<?php } else { ?>
				<?php echo esc_html__( ' or', 'xmoney-payments' ); ?>

				<a href="<?php echo esc_url( 'mailto:' . sanitize_email( $configuration->contact_email ) ); ?>">
					<?php echo esc_html__( ' contact', 'xmoney-payments' ); ?>
				</a>

				<?php echo esc_html__( ' the website administrator.', 'xmoney-payments' ); ?>
			<?php } ?>
		</p>
	</div>
	<?php

	die();
} else {
	Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( '[RESPONSE]: Decryption successfully performed.', 'xmoney-payments' ) );
}

/* Validate the decrypted response. */
$order_validation = Xmoney_Payments_Helper_Response::xmoney_payments_check_validation( $decrypted );

/* Check if server response validation failed.  */
if ( true !== $order_validation ) {
	Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( '[RESPONSE-ERROR]: Validation failed.', 'xmoney-payments' ) );
	?>
	<div class="error notice" style="margin-top: 20px;">
		<h3><?php echo esc_html__( 'An error occurred:', 'xmoney-payments' ); ?></h3>

		<p>
			<?php echo esc_html__( 'The payment could not be processed. Please', 'xmoney-payments' ); ?>

			<a href="<?php echo esc_url( wc_get_cart_url() ); ?>">
				<?php echo esc_html__( ' try again', 'xmoney-payments' ); ?>
			</a>

			<?php if ( '0' === $configuration->contact_email ) { ?>
				<?php
				printf(
					'%s %s %s',
					esc_html__( ' or', 'xmoney-payments' ),
					esc_html__( ' contact', 'xmoney-payments' ),
					esc_html__( ' the website administrator.', 'xmoney-payments' )
				);
				?>
			<?php } else { ?>
				<?php echo esc_html__( ' or', 'xmoney-payments' ); ?>

				<a href="<?php echo esc_url( 'mailto:' . sanitize_email( $configuration->contact_email ) ); ?>">
					<?php echo esc_html__( ' contact', 'xmoney-payments' ); ?>
				</a>

				<?php echo esc_html__( ' the website administrator.', 'xmoney-payments' ); ?>
			<?php } ?>
		</p>
	</div>
	<?php

	die();
}


/* Extract the WooCommerce order. */
$order_id        = explode( '_', $decrypted['externalOrderId'] )[0];
$xmoney_payments_order = wc_get_order( $order_id );

/* Check if the WooCommerce order extraction failed. */
if ( false === $xmoney_payments_order ) {
	Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( '[RESPONSE-ERROR]: Order does not exist.', 'xmoney-payments' ) );
	?>
	<div class="error notice" style="margin-top: 20px;">
		<h3><?php echo esc_html__( 'An error occurred:', 'xmoney-payments' ); ?></h3>

		<span><?php echo esc_html__( ' Order does not exist.', 'xmoney-payments' ); ?></span>

		<p>
			<?php echo esc_html__( 'The payment could not be processed. Please', 'xmoney-payments' ); ?>

			<a href="<?php echo esc_url( wc_get_cart_url() ); ?>">
				<?php echo esc_html__( ' try again', 'xmoney-payments' ); ?>
			</a>

			<?php if ( '0' === $configuration->contact_email ) { ?>
				<?php
				printf(
					'%s %s %s',
					esc_html__( ' or', 'xmoney-payments' ),
					esc_html__( ' contact', 'xmoney-payments' ),
					esc_html__( ' the website administrator.', 'xmoney-payments' )
				);
				?>
			<?php } else { ?>
				<?php echo esc_html__( ' or', 'xmoney-payments' ); ?>

				<a href="<?php echo esc_url( 'mailto:' . sanitize_email( $configuration->contact_email ) ); ?>">
					<?php echo esc_html__( ' contact', 'xmoney-payments' ); ?>
				</a>

				<?php echo esc_html__( ' the website administrator.', 'xmoney-payments' ); ?>
			<?php } ?>
		</p>
	</div>
	<?php

	die();
}

/* Check if the WooCommerce order cart hash does NOT MATCH the one sent to the server. */
$secure_key_raw = isset( $_GET['secure_key'] ) ? sanitize_text_field( wp_unslash( $_GET['secure_key'] ) ) : '';
// Accept only hex characters and reasonable length to avoid passing arbitrary values onward.
if ( empty( $secure_key_raw ) || ! preg_match( '/^[A-Fa-f0-9]{16,64}$/', $secure_key_raw ) || $secure_key_raw !== $xmoney_payments_order->get_data()['cart_hash'] ) {
	Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( '[RESPONSE-ERROR]: Invalid order identification key.', 'xmoney-payments' ) );
	?>
	<div class="error notice" style="margin-top: 20px;">
		<h3><?php echo esc_html__( 'An error occurred:', 'xmoney-payments' ); ?></h3>

		<span><?php echo esc_html__( ' Invalid secure key.', 'xmoney-payments' ); ?></span>

		<p>
			<?php echo esc_html__( 'The payment could not be processed. Please', 'xmoney-payments' ); ?>

			<a href="<?php echo esc_url( wc_get_cart_url() ); ?>">
				<?php echo esc_html__( ' try again', 'xmoney-payments' ); ?>
			</a>

			<?php if ( '0' === $configuration->contact_email ) { ?>
				<?php
				printf(
					'%s %s %s',
					esc_html__( ' or', 'xmoney-payments' ),
					esc_html__( ' contact', 'xmoney-payments' ),
					esc_html__( ' the website administrator.', 'xmoney-payments' )
				);
				?>
			<?php } else { ?>
				<?php echo esc_html__( ' or', 'xmoney-payments' ); ?>

				<a href="<?php echo esc_url( 'mailto:' . sanitize_email( $configuration->contact_email ) ); ?>">
					<?php echo esc_html__( ' contact', 'xmoney-payments' ); ?>
				</a>

				<?php echo esc_html__( ' the website administrator.', 'xmoney-payments' ); ?>
			<?php } ?>
		</p>
	</div>
	<?php

	die();
}

/* Reconstruct the checkout URL to use it to allow client to try again in case of error. */
$checkout_url = esc_url( wc_get_checkout_url() . 'order-pay/' . $order_id . '/?pay_for_order=true&key=' . $xmoney_payments_order->get_data()['order_key'] );

Xmoney_Payments_Status_Updater::update_status_back_url( $order_id, $decrypted['transactionStatus'], $checkout_url, $configuration );
