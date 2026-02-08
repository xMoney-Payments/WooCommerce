<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Require the Logger class for transaction logging. */
require_once XMONEY_PAYMENTS_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'class-xmoney-payments-logger.php';

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'xmoney/v1',
			'/inline/confirm',
			array(
				'methods'             => 'POST',
				'callback'            => function ( WP_REST_Request $req ) {

					$wc_order_id = (int) $req->get_param( 'order_id' );
					$result      = (array) $req->get_param( 'result' );

					// Log incoming request for debugging.
					if ( function_exists( 'wc_get_logger' ) ) {
						wc_get_logger()->info( sprintf( 'xMoney Inline confirm: order_id=%d, result=%s', $wc_order_id, wp_json_encode( $result ) ), array( 'source' => 'xmoney-payments' ) );
					}

					if ( empty( $wc_order_id ) || empty( $result ) ) {
						return new WP_REST_Response(
							array(
								'success' => false,
								'message' => 'Missing data',
							),
							400
						);
					}

					// If the SDK already returns decrypted data (no 'result' key), use it directly.
					if ( empty( $result['result'] ) ) {
						$payment_response = $result; // already decrypted.
					} else {
						// Fallback for encrypted SDK format.
						$payment_response = xmoney_payments_decrypt_inline_payload( $result );
						if ( is_wp_error( $payment_response ) ) {
							return new WP_REST_Response(
								array(
									'success' => false,
									'message' => $payment_response->get_error_message(),
								),
								400
							);
						}
					}

					$order = wc_get_order( $wc_order_id );
					if ( ! $order ) {
						return new WP_REST_Response(
							array(
								'success' => false,
								'message' => 'Order not found.',
							),
							404
						);
					}

					$updated = xmoney_payments_update_from_inline( $wc_order_id, $payment_response );
					if ( is_wp_error( $updated ) ) {
						return new WP_REST_Response(
							array(
								'success' => false,
								'message' => $updated->get_error_message(),
							),
							400
						);
					}

					// The JS normalizeTransactionResult() now also sends a _rawTransaction
					// object with the full SDK transaction data. Check it for IDs too.
					$raw_tx = isset( $payment_response['_rawTransaction'] ) ? $payment_response['_rawTransaction'] : array();

					// Extract transaction ID - check all possible sources.
					// Priority: explicit transactionId > _rawTransaction fields > id (which is xMoney order ID).
					$transaction_id = 0;
					// 1. Direct transactionId field from the normalized result.
					if ( ! empty( $payment_response['transactionId'] ) ) {
						$transaction_id = (int) $payment_response['transactionId'];
					}
					// 2. transactionId from the raw transaction object.
					if ( ! $transaction_id && ! empty( $raw_tx['transactionId'] ) ) {
						$transaction_id = (int) $raw_tx['transactionId'];
					}
					// 3. transactionId on the top-level result (before decryption normalization).
					if ( ! $transaction_id && ! empty( $result['transactionId'] ) ) {
						$transaction_id = (int) $result['transactionId'];
					}

					// Extract xMoney order ID (distinct from WooCommerce order ID).
					// The SDK 'id' field typically refers to the xMoney order, not the transaction.
					$xmoney_order_id = 0;
					if ( ! empty( $payment_response['orderId'] ) ) {
						$xmoney_order_id = (int) $payment_response['orderId'];
					} elseif ( ! empty( $raw_tx['orderId'] ) ) {
						$xmoney_order_id = (int) $raw_tx['orderId'];
					} elseif ( ! empty( $result['orderId'] ) ) {
						$xmoney_order_id = (int) $result['orderId'];
					}
					// 'id' is xMoney order ID if we don't already have one.
					if ( ! $xmoney_order_id && ! empty( $payment_response['id'] ) ) {
						$xmoney_order_id = (int) $payment_response['id'];
					}

					// Extract customer ID from response.
					$customer_id = 0;
					if ( ! empty( $payment_response['customerId'] ) ) {
						$customer_id = (int) $payment_response['customerId'];
					} elseif ( ! empty( $payment_response['customerData']['id'] ) ) {
						$customer_id = (int) $payment_response['customerData']['id'];
					} elseif ( ! empty( $raw_tx['customerData']['id'] ) ) {
						$customer_id = (int) $raw_tx['customerData']['id'];
					}

					// Extract customer identifier from response.
					$identifier = '';
					if ( ! empty( $payment_response['identifier'] ) ) {
						$identifier = sanitize_text_field( $payment_response['identifier'] );
					} elseif ( ! empty( $payment_response['customerData']['identifier'] ) ) {
						$identifier = sanitize_text_field( $payment_response['customerData']['identifier'] );
					} elseif ( ! empty( $raw_tx['customerData']['identifier'] ) ) {
						$identifier = sanitize_text_field( $raw_tx['customerData']['identifier'] );
					}

					// Extract status from response.
					$status = 'complete-ok';
					if ( ! empty( $payment_response['orderStatus'] ) ) {
						$status = sanitize_text_field( $payment_response['orderStatus'] );
					} elseif ( ! empty( $payment_response['status'] ) ) {
						$status = sanitize_text_field( $payment_response['status'] );
					} elseif ( ! empty( $payment_response['transactionStatus'] ) ) {
						$status = sanitize_text_field( $payment_response['transactionStatus'] );
					}

					// Log the transaction to the database for refund support.
					$transaction_data = array(
						'id_cart'       => $wc_order_id,
						'status'        => $status,
						'identifier'    => $identifier,
						'orderId'       => $xmoney_order_id,
						'transactionId' => $transaction_id,
						'customerId'    => $customer_id,
						'cardId'        => ! empty( $payment_response['cardId'] ) ? (int) $payment_response['cardId'] : ( ! empty( $raw_tx['transactionMethodId'] ) ? (int) $raw_tx['transactionMethodId'] : 0 ),
					);

					// Log transaction - always log even if transactionId is 0 so we have a record.
					Xmoney_Payments_Logger::xmoney_payments_log_transaction( $transaction_data );
					Xmoney_Payments_Logger::xmoney_payments_log(
						sprintf(
							'[INLINE]: Transaction logged for WC order %d, transactionId: %d, orderId: %d',
							$wc_order_id,
							$transaction_id,
							$xmoney_order_id
						)
					);

					if ( ! $transaction_id ) {
						// If no transaction ID in response, try to fetch it from API.
						$config     = Xmoney_Payments_Helper_Processor::get_configuration();
						$is_live    = ! empty( $config['is_live'] );
						$secret_key = $config['secret_key'];
						$now        = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
						$from       = ( clone $now )->modify( '-2 minutes' );

						$now_str  = $now->format( DateTime::ATOM );
						$from_str = $from->format( DateTime::ATOM );

						// Build URL with proper parameter separation.
						$api_order_id = ! empty( $result['id'] ) ? $result['id'] : $xmoney_order_id;
						if ( $api_order_id && $customer_id ) {
							$url = ( $is_live ? Xmoney_Payments_Helper_Processor::INLINE_LIVE_URL : Xmoney_Payments_Helper_Processor::INLINE_STAGE_URL )
								. '/transaction?orderId=' . $api_order_id
								. '&customerId=' . $customer_id
								. '&transactionMethod=card'
								. '&createdAtFrom=' . rawurlencode( $from_str )
								. '&createdAtTo=' . rawurlencode( $now_str );

							$response = wp_remote_get(
								esc_url_raw( $url ),
								array(
									'headers' => array(
										'Authorization' => 'Bearer ' . sanitize_text_field( $secret_key ),
										'Content-Type'  => 'application/json',
									),
									'timeout' => 30,
								)
							);

							if ( ! is_wp_error( $response ) ) {
								$data = json_decode( wp_remote_retrieve_body( $response ), true );

								if ( ! empty( $data['data'][0]['id'] ) ) {
									$transaction_data['transactionId'] = (int) $data['data'][0]['id'];
									if ( ! empty( $data['data'][0]['orderId'] ) ) {
										$transaction_data['orderId'] = (int) $data['data'][0]['orderId'];
									}

									// Log the transaction with the fetched transaction ID.
									Xmoney_Payments_Logger::xmoney_payments_log_transaction( $transaction_data );
									Xmoney_Payments_Logger::xmoney_payments_log( '[INLINE]: Transaction logged (from API) for WC order ' . $wc_order_id . ', transaction ID: ' . $transaction_data['transactionId'] );
								}
							}
						}
					}

					// Save card token to user meta if user is logged in and chose to save.
					if ( isset( $payment_response['customerId'] ) && $order->get_user_id() && isset( $payment_response['saveCard'] ) && true === $payment_response['saveCard'] ) {
						update_user_meta(
							$order->get_user_id(),
							'_xmoney_saved_card',
							array(
								'customer_id' => $payment_response['customerId'],
							)
						);
					}

				// Note: Cart emptying is NOT done here because the WP REST API
				// context does not have access to the WC session/cart. Instead,
				// the cart is cleared via three mechanisms:
				// 1. JS fires an AJAX call to xmoney_empty_cart (AJAX has WC session).
				// 2. woocommerce_thankyou_xmoney-payments hook empties cart on thank-you page.
				// 3. order_awaiting_payment session var triggers WC's built-in clearing.

				// If the order failed, tell the JS so the customer stays on the
				// checkout page and can retry instead of seeing the thank-you page.
				if ( $order && $order->has_status( 'failed' ) ) {
					return new WP_REST_Response(
						array(
							'success'  => false,
							'message'  => esc_html__( 'The payment could not be processed. Please try again.', 'xmoney-payments' ),
							'redirect' => wc_get_checkout_url(),
						),
						200
					);
				}

				$redirect_url = $order ? $order->get_checkout_order_received_url() : wc_get_checkout_url();

				return new WP_REST_Response(
					array(
						'success'  => true,
						'redirect' => $redirect_url,
					),
					200
				);
				},
				'permission_callback' => '__return_true',
			)
		);
	}
);
