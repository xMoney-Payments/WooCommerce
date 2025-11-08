<?php
/**
 * Twispay Helpers
 *
 * Updates the statused of orders and subscriptions based
 *  on the status read from the server response.
 *
 * @package  Twispay/Front
 * @category Front
 * @author   Twispay
 */

/* Exit if the file is accessed directly. */
if ( ! defined( 'ABSPATH' ) ) {
	exit; }

/* Security class check */
if ( ! class_exists( 'Twispay_TW_Status_Updater' ) ) :
	/**
	 * Twispay Helper Class
	 *
	 * Class that implements methods to update the statuses
	 * of orders and subscriptions based on the status received
	 * from the server.
	 */
	class Twispay_TW_Status_Updater {
		/* Array containing the possible result statuses. */
		public static array $RESULT_STATUSES = array(
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
		 * Update the status of an Woocommerce order according to the received server status.
		 *
		 * @param $order_id       - The id of the order for which to update the status.
		 * @param $server_status  - The status received from server.
		 * @param $checkout_url  - The url to which to redirect the client in case of error.
		 * @param $configuration - The configuration of the plugin
		 *
		 * @return void
		 */
		public static function updateStatus_backUrl( $order_id, $server_status, $checkout_url, $configuration ) {
			/* Extract the order. */
			$tw_order = wc_get_order( $order_id );

			switch ( $server_status ) {
				case self::$RESULT_STATUSES['COMPLETE_FAIL']:
					/* Mark order as failed. */
					$tw_order->update_status( 'failed', esc_html__( 'xMoney Payments payment failed', 'xmoney-payments' ) );

					if ( class_exists( 'WC_Subscriptions' ) && wcs_order_contains_subscription( $tw_order ) ) {
						WC_Subscriptions_Manager::maybe_process_failed_renewal_for_repair( $order_id );
					}

					Twispay_TW_Logger::twispay_tw_log( esc_html__( '[RESPONSE]: Status failed for order ID: ', 'xmoney-payments' ) . $order_id );
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

				case self::$RESULT_STATUSES['THREE_D_PENDING']:
					/* Mark order as on-hold. */
					$tw_order->update_status( 'on-hold', esc_html__( 'xMoney Payments payment is on hold', 'xmoney-payments' ) );

					Twispay_TW_Logger::twispay_tw_log( esc_html__( '[RESPONSE]: Status on-hold for order ID: ', 'xmoney-payments' ) . $order_id );
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

				case self::$RESULT_STATUSES['IN_PROGRESS']:
					/* Payment still pending at provider – do NOT treat as success. */
					$tw_order->update_status(
						'on-hold',
						esc_html__( 'xMoney Payments transaction is still in progress', 'xmoney-payments' )
					);

					Twispay_TW_Logger::twispay_tw_log(
						esc_html__( '[RESPONSE]: Status in-progress for order ID: ', 'xmoney-payments' ) . $order_id
					);
					break;
				case self::$RESULT_STATUSES['COMPLETE_OK']:
					/* Mark order as completed. */
					$tw_order->update_status( 'processing', esc_html__( 'xMoney Payments payment finalised successfully', 'xmoney-payments' ) );

					if ( class_exists( 'WC_Subscriptions' ) && wcs_order_contains_subscription( $tw_order ) ) {
						$subscription = wcs_get_subscriptions_for_order( $tw_order );
						$subscription = reset( $subscription );

						/* First payment on order, process payment & activate subscription. */
						if ( 0 === $subscription->get_payment_count() ) {
							$tw_order->payment_complete();

							if ( class_exists( 'WC_Subscriptions' ) ) {
								WC_Subscriptions_Manager::activate_subscriptions_for_order( $tw_order );
							}
						} elseif ( class_exists( 'WC_Subscriptions' ) ) {
								WC_Subscriptions_Manager::process_subscription_payments_on_order( $tw_order );
						}
					}

					Twispay_TW_Logger::twispay_tw_log( esc_html__( '[RESPONSE]: Status complete-ok for order ID: ', 'xmoney-payments' ) . $order_id );

					/* Redirect to xMoney Payments "Thank you Page" if it is set, if not, redirect to default "Thank you Page" */
					if ( $configuration->thankyou_page ) {
						wp_safe_redirect( esc_url( $configuration->thankyou_page ) );
					} else {
						new Twispay_TW_Default_Thankyou( $tw_order );
					}
					break;

				default:
					Twispay_TW_Logger::twispay_tw_log( esc_html__( '[RESPONSE-ERROR]: Wrong status: ', 'xmoney-payments' ) . $server_status );
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
		 * Update the status of an Woocommerce subscription according to the received server status.
		 *
		 * @param $order_id      - The ID of the order to be updated.
		 * @param $server_status - The status received from server.
		 *
		 * @return void
		 */
		public static function updateStatus_IPN( $order_id, $server_status ) {
			/* Extract the order. */
			$tw_order = wc_get_order( $order_id );

			switch ( $server_status ) {
				case self::$RESULT_STATUSES['COMPLETE_FAIL']:
					/* Mark order as failed. */
					$tw_order->update_status( 'failed', esc_html__( 'xMoney Payments payment failed', 'xmoney-payments' ) );

					if ( class_exists( 'WC_Subscriptions' ) && wcs_order_contains_subscription( $tw_order ) ) {
						WC_Subscriptions_Manager::maybe_process_failed_renewal_for_repair( $order_id );
					}

					Twispay_TW_Logger::twispay_tw_log( esc_html__( '[RESPONSE]: Status failed for order ID: ', 'xmoney-payments' ) . $order_id );
					break;

				case self::$RESULT_STATUSES['CANCEL_OK']:
				case self::$RESULT_STATUSES['REFUND_OK']:
				case self::$RESULT_STATUSES['VOID_OK']:
				case self::$RESULT_STATUSES['CHARGE_BACK']:
					/* Mark order as refunded. */
					$tw_order->update_status( 'refunded', esc_html__( 'Website manager pressed on refund button successfully', 'xmoney-payments' ) );

					if ( class_exists( 'WC_Subscriptions' ) && wcs_order_contains_subscription( $tw_order ) ) {
						WC_Subscriptions_Manager::cancel_subscriptions_for_order( $tw_order );
					}

					Twispay_TW_Logger::twispay_tw_log( esc_html__( '[RESPONSE]: Status refund-ok for order ID: ', 'xmoney-payments' ) . $order_id );
					break;

				case self::$RESULT_STATUSES['THREE_D_PENDING']:
					/* Mark order as on-hold. */
					$tw_order->update_status( 'on-hold', esc_html__( 'xMoney Payments payment is on hold', 'xmoney-payments' ) );

					Twispay_TW_Logger::twispay_tw_log( esc_html__( '[RESPONSE]: Status on-hold for order ID: ', 'xmoney-payments' ) . $order_id );
					break;

				case self::$RESULT_STATUSES['IN_PROGRESS']:
					/* Payment still pending at provider – do NOT treat as success. */
					$tw_order->update_status(
						'on-hold',
						esc_html__( 'xMoney Payments transaction is still in progress', 'xmoney-payments' )
					);

					Twispay_TW_Logger::twispay_tw_log(
						esc_html__( '[RESPONSE]: Status in-progress for order ID: ', 'xmoney-payments' ) . $order_id
					);
				case self::$RESULT_STATUSES['COMPLETE_OK']:
					/* Mark order as completed. */
					$tw_order->update_status( 'processing', esc_html__( 'xMoney Payments payment finalised successfully', 'xmoney-payments' ) );

					if ( class_exists( 'WC_Subscriptions' ) && wcs_order_contains_subscription( $tw_order ) ) {
						$subscription = wcs_get_subscriptions_for_order( $tw_order );
						$subscription = reset( $subscription );

						/* First payment on order, process payment & activate subscription. */
						if ( 0 === $subscription->get_payment_count() ) {
							$tw_order->payment_complete();

							if ( class_exists( 'WC_Subscriptions' ) ) {
								WC_Subscriptions_Manager::activate_subscriptions_for_order( $tw_order );
							}
						} elseif ( class_exists( 'WC_Subscriptions' ) ) {
								WC_Subscriptions_Manager::process_subscription_payments_on_order( $tw_order );
						}
					}

					Twispay_TW_Logger::twispay_tw_log( esc_html__( '[RESPONSE]: Status complete-ok for order ID: ', 'xmoney-payments' ) . $order_id );
					break;

				default:
					Twispay_TW_Logger::twispay_tw_log( esc_html__( '[RESPONSE-ERROR]: Wrong status: ', 'xmoney-payments' ) . $server_status );
					break;
			}
		}


		/**
		 * Update the status of an Woocommerce subscription according to the received server status.
		 *
		 * @param $order_id: The ID of the order that is the parent of the subscription.
		 * @param $server_status: The status received from server.
		 *
		 * @return void
		 */
		public static function updateSubscriptionStatus( $order_id, $server_status ) {
			/* Check that the subscriptions plugin is installed. */
			if ( ! class_exists( 'WC_Subscriptions' ) ) {
				return;
			}

			/* Extract the order. */
			$tw_order = wc_get_order( $order_id );
			/* Extract the subscription. */
			$subscription = wcs_get_subscriptions_for_order( $tw_order );
			$subscription = reset( $subscription );

			switch ( $server_status ) {
				case self::$RESULT_STATUSES['COMPLETE_FAIL']: /* The subscription has payment failure. */
				case self::$RESULT_STATUSES['THREE_D_PENDING']: /* The subscription has a 3D pending payment. */
					if ( $subscription->can_be_updated_to( 'on-hold' ) ) {
						/* Mark subscription as 'ON-HOLD'. */
						$subscription->update_status( 'on-hold' );
						Twispay_TW_Logger::twispay_tw_updateTransactionStatus( $order_id, $server_status );
					}
					break;

				case self::$RESULT_STATUSES['COMPLETE_OK']: /* The subscription has been completed. */
				case self::$RESULT_STATUSES['CANCEL_OK']: /* The subscription has been canceled. */
				case self::$RESULT_STATUSES['REFUND_OK']: /* The subscription has been refunded. */
				case self::$RESULT_STATUSES['VOID_OK']:
				case self::$RESULT_STATUSES['CHARGE_BACK']: /* The subscription has been forced back. */
					if ( $subscription->can_be_updated_to( 'canceled' ) ) {
						/* Mark subscription as 'CANCELED'. */
						$subscription->update_status( 'canceled' );
						Twispay_TW_Logger::twispay_tw_updateTransactionStatus( $order_id, $server_status );
					}
					break;

				case self::$RESULT_STATUSES['EXPIRING']: /* The subscription will expire soon. */
				case self::$RESULT_STATUSES['IN_PROGRESS']: /* The subscription is in progress. */
					if ( $subscription->can_be_updated_to( 'active' ) ) {
						/* Mark subscription as 'ACTIVE'. */
						$subscription->update_status( 'active' );
						Twispay_TW_Logger::twispay_tw_updateTransactionStatus( $order_id, $server_status );
					}
					break;

				default:
					Twispay_TW_Logger::twispay_tw_log( esc_html__( '[RESPONSE-ERROR]: Wrong status: ', 'xmoney-payments' ) . $server_status );
					break;
			}
		}
	}
endif; /* End if class_exists. */
