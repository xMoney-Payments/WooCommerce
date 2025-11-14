<?php
    if (!defined('ABSPATH')) {
        exit;
    }

    add_action('rest_api_init', function () {
        register_rest_route('xmoney/v1', '/inline/confirm', [
            'methods' => 'POST',
            'callback' => function (WP_REST_Request $req) {
                $order_id = (int)$req->get_param('order_id');
                $result = (array)$req->get_param('result');

                if (empty($order_id) || empty($result)) {
                    return new WP_REST_Response(
                        ['success' => false, 'message' => 'Missing data'],
                        400
                    );
                }

                // If the SDK already returns decrypted data (no 'result' key), use it directly
                if (empty($result['result'])) {
                    $paymentResponse = $result; // already decrypted
                } else {
                    // Fallback for encrypted SDK format
                    $paymentResponse = xmoney_payments_decrypt_inline_payload($result);
                    if (is_wp_error($paymentResponse)) {
                        return new WP_REST_Response(
                            ['success' => false, 'message' => $paymentResponse->get_error_message()],
                            400
                        );
                    }
                }

                $order = wc_get_order($order_id);

                $updated = xmoney_payments_update_from_inline($order_id, $paymentResponse);
                if (is_wp_error($updated)) {
                    return new WP_REST_Response(
                        ['success' => false, 'message' => $updated->get_error_message()],
                        400
                    );
                }

                /* Validate the decrypted response. */
                Xmoney_Payments_Helper_Response::xmoney_payments_check_validation($paymentResponse);


                $config = Xmoney_Payments_Helper_Processor::get_configuration();
                $is_live = !empty($config['is_live']);
                $secret_key = $config['secret_key'];
                $now = new DateTime('now', new DateTimeZone('UTC'));
                $from = (clone $now)->modify('-2 minutes');

                $now = $now->format(DateTime::ATOM);
                $from = $from->format(DateTime::ATOM);

                $url = ($is_live ? Xmoney_Payments_Helper_Processor::INLINE_LIVE_URL : Xmoney_Payments_Helper_Processor::INLINE_STAGE_URL) . "/transaction?orderId=" . $result['id'] . "&customerId=" . $paymentResponse['customerId'] . "&transactionMethod=cardcreatedAtFrom=". $from . "&createdAtTo=". $now;

                $response = wp_remote_get(
                    esc_url($url),
                    [
                        'headers' => [
                            'Authorization' => 'Bearer ' . sanitize_text_field($secret_key),
                            'Content-Type' => 'application/json',
                        ],
                        'timeout' => 30,
                    ]
                );

                if (!is_wp_error($response)) {
                    $data = json_decode(wp_remote_retrieve_body($response), true);

                    if (!empty($data['data'][0]['id'])) {
                        $transaction_id = $data['data'][0]['id'];
                        $order_id = $data['data'][0]['orderId'];

                        // Update transactionId column
                        Xmoney_Payments_Logger::xmoney_payments_update_transaction_id($order_id, $transaction_id);
                    }
                }


                // Save card token to user meta if user is logged in and chose to save.
                if (isset($paymentResponse['customerId']) && $order->get_user_id() && isset($paymentResponse['saveCard']) && $paymentResponse['saveCard'] == true) {

                    update_user_meta(
                        $order->get_user_id(),
                        '_xmoney_saved_card',
                        [
                            'customer_id' => $paymentResponse['customerId']
                        ]
                    );

//                    $response = wp_remote_get(
//                        esc_url($url),
//                        [
//                            'headers' => [
//                                'Authorization' => 'Bearer ' . sanitize_text_field($secret_key),
//                                'Content-Type' => 'application/json',
//                            ],
//                            'timeout' => 30,
//                        ]
//                    );
//
//
//
//                    if (!is_wp_error($response)) {
//                        $data = json_decode(wp_remote_retrieve_body($response), true);
//
//                        if (!empty($data['data'][0]['id'])) {
//                            $payment_method_id = $data['data'][0]['id'];
//                            // Store it as the reusable card token
//                            update_user_meta(
//                                $order->get_user_id(),
//                                '_xmoney_saved_card',
//                                [
//                                    'customer_id' => $paymentResponse['customerId'],
//                                    'payment_method_id' => $payment_method_id,
//                                ]
//                            );
//                        }
//                    }
                }

                return new WP_REST_Response([
                    'success' => true,
                    'redirect' => $order ? $order->get_checkout_order_received_url() : wc_get_checkout_url()
                ], 200);
            },
            'permission_callback' => '__return_true',
        ]);
    });
