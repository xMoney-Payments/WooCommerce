<?php
/**
 * Xmoney Payments Transaction List Admin Page
 *
 * Xmoney Payments transaction list page on the Administrator dashboard
 *
 * @package  Xmoney/Admin
 * @category Admin
 * @author   Xmoney Payments
 */

// Exit if the file is accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Import the table class
require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'includes/admin/transaction/class-xmoney-payments-transaction-table.php';

/**
 * Render the Xmoney Payments transaction admin page in the WordPress dashboard.
 *
 * Loads language strings, verifies WooCommerce is active, handles action routing,
 * and displays the transaction list table.
 *
 * @return void
 */
function xmoney_payments_transaction_administrator() {
	/* Load languages */
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
		return;
	}

	// Check if the view / edit / delete action is detected, otherwise load the campaigns form.
	// Read-only routing: no state mutation; nonce not required. Parameters sanitized before use.
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Using sanitized GET purely for conditional include.
	if ( isset( $_GET['action'] ) && sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Using sanitized GET purely for conditional include.
		$action = sanitize_text_field( wp_unslash( $_GET['action'] ) );

		switch ( $action ) {
			case 'refund_payment':
				include XMONEY_PAYMENTS_PLUGIN_DIR . 'includes/admin/transaction/refund-t.php';
				break;
			case 'recurring_payment':
				include XMONEY_PAYMENTS_PLUGIN_DIR . 'includes/admin/transaction/recurring-t.php';
				break;
		}
	} else {
		?>
			<div class="wrap">
				<h1><?php echo esc_html__( 'Transaction list', 'xmoney-payments' ); ?></h1>

				<?php if ( class_exists( 'WC_Subscriptions' ) ) { ?>
					<form method="post" id="synchronize_subscriptions">
						<table class="form-table">
							<tr class="form-field" id="contact_email_o">
								<th scope="row"><label><?php echo esc_html__( 'Synchronize subscriptions', 'xmoney-payments' ); ?></span></label></th>
								<td>
									<input type="hidden" name="xmoney_payments_general_action" value="synchronize_subscriptions" />
									<?php wp_nonce_field( 'xmoney_payments_general_action', 'xmoney_payments_general_nonce' ); ?>
									<?php submit_button( esc_attr__( 'Synchronize the local status of all subscriptions with the server status.', 'xmoney-payments' ), 'primary', 'createuser', true, array( 'id' => 'synchronizesubscriptions' ) ); ?>
									<p class="description"><?php echo esc_html__( 'Synchronize the local status of all subscriptions with the server status.', 'xmoney-payments' ); ?></p>
								</td>
							</tr>
						</table>
					</form>
				<?php } ?>


				<?php
					// Display-only notice handling; no state change performed.
                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sanitized GET controls conditional messaging only.
				if ( isset( $_GET['notice'] ) && sanitize_text_field( wp_unslash( $_GET['notice'] ) ) ) {
                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sanitized GET controls conditional messaging only.
					$notice = sanitize_text_field( wp_unslash( $_GET['notice'] ) );

					switch ( $notice ) {
						case 'error_refund':
							?>
									<div class="error notice">
										<p><?php echo esc_html__( 'Refund could not been processed.', 'xmoney-payments' ); ?></p>
									</div>
								<?php
							break;
						case 'error_recurring':
							?>
									<div class="error notice">
										<p><?php echo esc_html__( 'Recurring could not been processed.', 'xmoney-payments' ); ?></p>
									</div>
								<?php
							break;
						case 'success_refund':
							?>
									<div class="updated notice">
										<p><?php echo esc_html__( 'Refund processed successfully. Refresh the page in seconds to see the update.', 'xmoney-payments' ); ?></p>
									</div>
								<?php
							break;
						case 'success_recurring':
							?>
									<div class="updated notice">
										<p><?php echo esc_html__( 'Recurring processed successfully.', 'xmoney-payments' ); ?></p>
									</div>
								<?php
							break;
						case 'sync_finished':
							?>
									<div class="updated notice">
										<p><?php echo esc_html__( 'Subscriptions synchronization finished.', 'xmoney-payments' ); ?></p>
									</div>
								<?php
							break;
						case 'errorp_refund':
							?>
									<div class="error notice">
					<p>
							<?php
                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only sanitized error message.
							echo ( isset( $_GET['emessage'] ) ? esc_html( sanitize_text_field( wp_unslash( $_GET['emessage'] ) ) ) : '' );
							?>
						</p>
									</div>
								<?php
							break;
					}
				}

					// Create the Payment Methods object and build the Table
					$transaction_table = new Xmoney_Payments_Transaction_Table();
					$transaction_table->views();
				?>

				<form method="get">
					<input type="hidden" name="page" value="
					<?php
						// Preserve page slug for search/sort form submission.
                        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only context.
						echo ( isset( $_GET['page'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) : '' )
					?>
						" />
					<?php wp_nonce_field( 'xmoney_payments_transactions_action', 'xmoney_payments_transactions_nonce' ); ?>
					<?php $transaction_table->search_box( esc_html__( 'Search Order', 'xmoney-payments' ), 'search-query' ); ?>
				</form>
				<form method="post">
					<?php
						$transaction_table->prepare_items();
						$transaction_table->display();
					?>
					<?php wp_nonce_field( 'xmoney_payments_transactions_action', 'xmoney_payments_transactions_nonce' ); ?>
				</form>
			</div>
		<?php
	}
}
