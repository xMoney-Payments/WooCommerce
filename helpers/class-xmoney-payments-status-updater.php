<?php
/**
 * Xmoney Payments Helpers
 *
 * Updates the statused of orders and subscriptions based
 *  on the status read from the server response.
 *
 * @package  Xmoney/Front
 * @category Front
 * @author   Xmoney Payments
 */

/* Exit if the file is accessed directly. */
if ( ! defined( 'ABSPATH' ) ) {
	exit; }

/* Security class check */
if ( ! class_exists( 'Xmoney_Payments_Status_Updater' ) ) :
	/**
	 * Xmoney Payments Helper Class
	 *
	 * Class that implements methods to update the statuses
	 * of orders and subscriptions based on the status received
	 * from the server.
	 */
	class Xmoney_Payments_Status_Updater {
		/**
		 * Possible result statuses returned by the gateway.
		 *
		 * @var array<string,string>
		 */
		public static array $result_statuses = array(
			'UNCERTAIN'       => 'uncertain', /* No response from provider */

			'IN_PROGRESS'     => 'in-progress', /* Authorized */

			'COMPLETE_OK'     => 'complete-ok', /* Captured */

			'COMPLETE_FAIL'   => 'complete-failed', /* Not authorized */

			'CANCEL_OK'       => 'cancel-ok', /* Capture reversal */

			'REFUND_OK'       => 'refund-ok', /* Settlement reversal */

			'VOID_OK'         => 'void-ok', /* Authorization reversal */

			'CHARGE_BACK'     => 'charge-back', /* Charge-back received */

			'THREE_D_PENDING' => '3d-pending', /* Waiting for 3d authentication */

			'EXPIRING'        => 'expiring', /* The recurring order has expired */
		);

		/**
		 * Update the WooCommerce order according to the received server status.
		 *
		 * @param int    $order_id The ID of the order to update.
		 * @param string $server_status The status received from the server.
		 * @param string $checkout_url The URL to redirect the customer to in case of failure.
		 * @param object $configuration The plugin configuration object.
		 * @return void
		 */
		public static function update_status_back_url( $order_id, $server_status, $checkout_url, $configuration ) {
			/* Extract the order. */
			$xmoney_payments_order = wc_get_order( $order_id );

			switch ( $server_status ) {
				case self::$result_statuses['COMPLETE_FAIL']:
					/* Mark order as failed. */
					$xmoney_payments_order->update_status( 'failed', esc_html__( 'xMoney Payments payment failed', 'xmoney-payments' ) );

					if ( class_exists( 'WC_Subscriptions' ) && wcs_order_contains_subscription( $xmoney_payments_order ) ) {
						WC_Subscriptions_Manager::maybe_process_failed_renewal_for_repair( $order_id );
					}

					Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( '[RESPONSE]: Status failed for order ID: ', 'xmoney-payments' ) . $order_id );
					?>
					<div class="error notice" style="margin-top: 20px;">
						<h3><?php echo esc_html__( 'An error occurred:', 'xmoney-payments' ); ?></h3>

						<p>
							<?php echo esc_html__( 'The payment could not be processed. Please', 'xmoney-payments' ); ?>

							<a href="<?php echo esc_url( $checkout_url ); ?>">
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
					break;

				case self::$result_statuses['THREE_D_PENDING']:
					/* Mark order as on-hold. */
					$xmoney_payments_order->update_status( 'on-hold', esc_html__( 'xMoney Payments payment is on hold', 'xmoney-payments' ) );

					Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( '[RESPONSE]: Status on-hold for order ID: ', 'xmoney-payments' ) . $order_id );
					?>
					<div class="error notice" style="margin-top: 20px;">
						<h3><?php echo esc_html__( 'An error occurred:', 'xmoney-payments' ); ?></h3>

						<span><?php echo esc_html__( ' Payment is on hold.', 'xmoney-payments' ); ?></span>

						<?php if ( '0' === $configuration->contact_email ) { ?>
							<p>
								<?php
								printf(
									'%s %s %s',
									esc_html__( 'The payment could not be processed. Please', 'xmoney-payments' ),
									esc_html__( ' contact', 'xmoney-payments' ),
									esc_html__( ' the website administrator.', 'xmoney-payments' )
								);
								?>
							</p>
						<?php } else { ?>
							<p><?php echo esc_html__( 'The payment could not be processed. Please', 'xmoney-payments' ); ?>
								<a href="<?php echo esc_url( 'mailto:' . sanitize_email( $configuration->contact_email ) ); ?>">
									<?php echo esc_html__( ' contact', 'xmoney-payments' ); ?>
								</a>

								<?php echo esc_html__( ' the website administrator.', 'xmoney-payments' ); ?>
							</p>
						<?php } ?>
					</div>
					<?php
					break;

				case self::$result_statuses['IN_PROGRESS']:
					/* Payment still pending at provider – do NOT treat as success. */
					$xmoney_payments_order->update_status(
						'on-hold',
						esc_html__( 'xMoney Payments transaction is still in progress', 'xmoney-payments' )
					);

					Xmoney_Payments_Logger::xmoney_payments_log(
						esc_html__( '[RESPONSE]: Status in-progress for order ID: ', 'xmoney-payments' ) . $order_id
					);
					break;
				case self::$result_statuses['COMPLETE_OK']:
					/* Mark order as completed. */
					$xmoney_payments_order->update_status( 'processing', esc_html__( 'xMoney Payments payment finalised successfully', 'xmoney-payments' ) );

					if ( class_exists( 'WC_Subscriptions' ) && wcs_order_contains_subscription( $xmoney_payments_order ) ) {
						$subscription = wcs_get_subscriptions_for_order( $xmoney_payments_order );
						$subscription = reset( $subscription );

						/* First payment on order, process payment & activate subscription. */
						if ( 0 === $subscription->get_payment_count() ) {
							$xmoney_payments_order->payment_complete();

							if ( class_exists( 'WC_Subscriptions' ) ) {
								WC_Subscriptions_Manager::activate_subscriptions_for_order( $xmoney_payments_order );
							}
						} elseif ( class_exists( 'WC_Subscriptions' ) ) {
								WC_Subscriptions_Manager::process_subscription_payments_on_order( $xmoney_payments_order );
						}
					}

					Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( '[RESPONSE]: Status complete-ok for order ID: ', 'xmoney-payments' ) . $order_id );

					/* Redirect to xMoney Payments "Thank you Page" if it is set, if not, redirect to default "Thank you Page" */
					if ( $configuration->thankyou_page ) {
						wp_safe_redirect( esc_url( $configuration->thankyou_page ) );
					} else {
						new Xmoney_Payments_Default_Thankyou( $xmoney_payments_order );
					}
					break;

				default:
					Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( '[RESPONSE-ERROR]: Wrong status: ', 'xmoney-payments' ) . $server_status );
					?>
					<div class="error notice" style="margin-top: 20px;">
						<h3><?php echo esc_html__( 'An error occurred:', 'xmoney-payments' ); ?></h3>

						<p>
							<?php echo esc_html__( 'The payment could not be processed. Please', 'xmoney-payments' ); ?>

							<a href="<?php echo esc_url( $checkout_url ); ?>">
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
					break;
			}
		}

		/**
		 * Update the WooCommerce order in IPN mode according to server status.
		 *
		 * @param int    $order_id The ID of the order to update.
		 * @param string $server_status The status received from the server.
		 * @return void
		 */
		public static function update_status_i_p_n( $order_id, $server_status ) {
			/* Extract the order. */
			$xmoney_payments_order = wc_get_order( $order_id );

			switch ( $server_status ) {
				case self::$result_statuses['COMPLETE_FAIL']:
					/* Mark order as failed. */
					$xmoney_payments_order->update_status( 'failed', esc_html__( 'xMoney Payments payment failed', 'xmoney-payments' ) );

					if ( class_exists( 'WC_Subscriptions' ) && wcs_order_contains_subscription( $xmoney_payments_order ) ) {
						WC_Subscriptions_Manager::maybe_process_failed_renewal_for_repair( $order_id );
					}

					Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( '[RESPONSE]: Status failed for order ID: ', 'xmoney-payments' ) . $order_id );
					break;

				case self::$result_statuses['CANCEL_OK']:
				case self::$result_statuses['REFUND_OK']:
				case self::$result_statuses['VOID_OK']:
				case self::$result_statuses['CHARGE_BACK']:
					/* Mark order as refunded. */
					$xmoney_payments_order->update_status( 'refunded', esc_html__( 'Website manager pressed on refund button successfully', 'xmoney-payments' ) );

					if ( class_exists( 'WC_Subscriptions' ) && wcs_order_contains_subscription( $xmoney_payments_order ) ) {
						WC_Subscriptions_Manager::cancel_subscriptions_for_order( $xmoney_payments_order );
					}

					Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( '[RESPONSE]: Status refund-ok for order ID: ', 'xmoney-payments' ) . $order_id );
					break;

				case self::$result_statuses['THREE_D_PENDING']:
					/* Mark order as on-hold. */
					$xmoney_payments_order->update_status( 'on-hold', esc_html__( 'xMoney Payments payment is on hold', 'xmoney-payments' ) );

					Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( '[RESPONSE]: Status on-hold for order ID: ', 'xmoney-payments' ) . $order_id );
					break;

				case self::$result_statuses['IN_PROGRESS']:
					/* Payment still pending at provider – do NOT treat as success. */
					$xmoney_payments_order->update_status(
						'on-hold',
						esc_html__( 'xMoney Payments transaction is still in progress', 'xmoney-payments' )
					);

					Xmoney_Payments_Logger::xmoney_payments_log(
						esc_html__( '[RESPONSE]: Status in-progress for order ID: ', 'xmoney-payments' ) . $order_id
					);
					/* falls through */
				case self::$result_statuses['COMPLETE_OK']:
					/* Mark order as completed. */
					$xmoney_payments_order->update_status( 'processing', esc_html__( 'xMoney Payments payment finalised successfully', 'xmoney-payments' ) );

					if ( class_exists( 'WC_Subscriptions' ) && wcs_order_contains_subscription( $xmoney_payments_order ) ) {
						$subscription = wcs_get_subscriptions_for_order( $xmoney_payments_order );
						$subscription = reset( $subscription );

						/* First payment on order, process payment & activate subscription. */
						if ( 0 === $subscription->get_payment_count() ) {
							$xmoney_payments_order->payment_complete();

							if ( class_exists( 'WC_Subscriptions' ) ) {
								WC_Subscriptions_Manager::activate_subscriptions_for_order( $xmoney_payments_order );
							}
						} elseif ( class_exists( 'WC_Subscriptions' ) ) {
								WC_Subscriptions_Manager::process_subscription_payments_on_order( $xmoney_payments_order );
						}
					}

					Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( '[RESPONSE]: Status complete-ok for order ID: ', 'xmoney-payments' ) . $order_id );
					break;

				default:
					Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( '[RESPONSE-ERROR]: Wrong status: ', 'xmoney-payments' ) . $server_status );
					break;
			}
		}


		/**
		 * Update the WooCommerce subscription according to received server status.
		 *
		 * @param int    $order_id The parent WooCommerce order ID.
		 * @param string $server_status The status received from the server.
		 * @return void
		 */
		public static function update_subscription_status( $order_id, $server_status ) {
			/* Check that the subscriptions plugin is installed. */
			if ( ! class_exists( 'WC_Subscriptions' ) ) {
				return;
			}

			/* Extract the order. */
			$xmoney_payments_order = wc_get_order( $order_id );
			/* Extract the subscription. */
			$subscription = wcs_get_subscriptions_for_order( $xmoney_payments_order );
			$subscription = reset( $subscription );

			switch ( $server_status ) {
				case self::$result_statuses['COMPLETE_FAIL']: /* The subscription has payment failure. */
				case self::$result_statuses['THREE_D_PENDING']: /* The subscription has a 3D pending payment. */
					if ( $subscription->can_be_updated_to( 'on-hold' ) ) {
						/* Mark subscription as 'ON-HOLD'. */
						$subscription->update_status( 'on-hold' );
						Xmoney_Payments_Logger::xmoney_payments_update_transaction_status( $order_id, $server_status );
					}
					break;

				case self::$result_statuses['COMPLETE_OK']: /* The subscription has been completed. */
				case self::$result_statuses['CANCEL_OK']: /* The subscription has been canceled. */
				case self::$result_statuses['REFUND_OK']: /* The subscription has been refunded. */
				case self::$result_statuses['VOID_OK']:
				case self::$result_statuses['CHARGE_BACK']: /* The subscription has been forced back. */
					if ( $subscription->can_be_updated_to( 'canceled' ) ) {
						/* Mark subscription as 'CANCELED'. */
						$subscription->update_status( 'canceled' );
						Xmoney_Payments_Logger::xmoney_payments_update_transaction_status( $order_id, $server_status );
					}
					break;

				case self::$result_statuses['EXPIRING']: /* The subscription will expire soon. */
				case self::$result_statuses['IN_PROGRESS']: /* The subscription is in progress. */
					if ( $subscription->can_be_updated_to( 'active' ) ) {
						/* Mark subscription as 'ACTIVE'. */
						$subscription->update_status( 'active' );
						Xmoney_Payments_Logger::xmoney_payments_update_transaction_status( $order_id, $server_status );
					}
					break;

				default:
					Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( '[RESPONSE-ERROR]: Wrong status: ', 'xmoney-payments' ) . $server_status );
					break;
			}
		}
	}
endif; /* End if class_exists. */

/**
 * Updates order based on Inline response
 */
function xmoney_payments_update_from_inline($order_id, $paymentResponse)
{
    $order = wc_get_order($order_id);
    if (!$order) {
        return new WP_Error('tw_inline_order', 'Order not found');
    }

    $status = strtolower($paymentResponse['status'] ?? 'complete-ok');
    $txid = $paymentResponse['id'] ?? ($paymentResponse['transactionId'] ?? '');

    if (in_array($status, ['completeok', 'complete', 'success', 'paid', 'complete-ok'], true)) {
        $order->payment_complete($txid);
        $order->add_order_note(sprintf('xMoney Inline payment successful. TX: %s', $txid));
        return true;
    }

    if (in_array($status, ['declined', 'failed', 'error', 'cancelled'], true)) {
        $order->update_status('failed', 'xMoney Inline payment failed.');
        return true;
    }

    $order->update_status('on-hold', 'xMoney Inline payment pending.');
    return true;
}