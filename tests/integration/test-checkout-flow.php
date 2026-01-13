<?php
/**
 * Integration tests for checkout flow.
 *
 * @package Xmoney_Payments
 */

/**
 * Test class for checkout flow integration.
 *
 * Note: Tests requiring the gateway class will skip when not available.
 */
class Test_Checkout_Flow extends Xmoney_Payments_Test_Case {

	/**
	 * Test complete checkout flow with redirect payment.
	 */
	public function test_complete_redirect_checkout_flow() {
		if ( ! $this->gateway ) {
			$this->markTestSkipped( 'Gateway not available in test environment' );
		}

		// 1. Create product.
		$product = $this->create_simple_product(
			array(
				'name'          => 'Test Checkout Product',
				'regular_price' => '25.00',
			)
		);

		// 2. Add to cart.
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		$this->assertFalse( WC()->cart->is_empty(), 'Cart should not be empty' );

		// 3. Create order.
		$order = $this->create_test_order(
			array(
				'status'         => 'pending',
				'payment_method' => 'xmoney-payments',
			)
		);

		// 4. Process payment through gateway.
		$result = $this->gateway->process_payment( $order->get_id() );

		$this->assertEquals( 'success', $result['result'], 'Payment process should return success' );
		$this->assertNotEmpty( $result['redirect'], 'Redirect URL should not be empty' );

		// 5. Simulate IPN callback with success.
		Xmoney_Payments_Status_Updater::update_status_i_p_n(
			$order->get_id(),
			Xmoney_Payments_Status_Updater::$result_statuses['COMPLETE_OK']
		);

		// 6. Verify final order status.
		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'processing', $order->get_status(), 'Order should be processing after successful payment' );
	}

	/**
	 * Test checkout flow with failed payment.
	 */
	public function test_checkout_flow_failed_payment() {
		if ( ! $this->gateway ) {
			$this->markTestSkipped( 'Gateway not available in test environment' );
		}

		$product = $this->create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		$order  = $this->create_test_order();
		$result = $this->gateway->process_payment( $order->get_id() );

		// Simulate failed payment IPN.
		Xmoney_Payments_Status_Updater::update_status_i_p_n(
			$order->get_id(),
			Xmoney_Payments_Status_Updater::$result_statuses['COMPLETE_FAIL']
		);

		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'failed', $order->get_status(), 'Order should be failed after failed payment' );
	}

	/**
	 * Test checkout flow with 3D Secure pending.
	 */
	public function test_checkout_flow_3d_pending() {
		if ( ! $this->gateway ) {
			$this->markTestSkipped( 'Gateway not available in test environment' );
		}

		$product = $this->create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		$order = $this->create_test_order();
		$this->gateway->process_payment( $order->get_id() );

		// Simulate 3D pending IPN.
		Xmoney_Payments_Status_Updater::update_status_i_p_n(
			$order->get_id(),
			Xmoney_Payments_Status_Updater::$result_statuses['THREE_D_PENDING']
		);

		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'on-hold', $order->get_status(), 'Order should be on-hold while 3D pending' );

		// Then complete the payment.
		Xmoney_Payments_Status_Updater::update_status_i_p_n(
			$order->get_id(),
			Xmoney_Payments_Status_Updater::$result_statuses['COMPLETE_OK']
		);

		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'processing', $order->get_status(), 'Order should be processing after 3D auth complete' );
	}

	/**
	 * Test checkout with multiple products.
	 */
	public function test_checkout_multiple_products() {
		if ( ! $this->gateway ) {
			$this->markTestSkipped( 'Gateway not available in test environment' );
		}

		$product1 = $this->create_simple_product(
			array(
				'name'          => 'Product 1',
				'regular_price' => '15.00',
			)
		);

		$product2 = new WC_Product_Simple();
		$product2->set_name( 'Product 2' );
		$product2->set_regular_price( '25.00' );
		$product2->set_price( '25.00' );
		$product2->save();

		WC()->cart->add_to_cart( $product1->get_id(), 2 );
		WC()->cart->add_to_cart( $product2->get_id(), 1 );

		$this->assertEquals( 3, WC()->cart->get_cart_contents_count(), 'Cart should have 3 items' );

		$order = $this->create_test_order();
		$order->add_product( $product2, 1 );
		$order->calculate_totals();
		$order->save();

		$result = $this->gateway->process_payment( $order->get_id() );

		$this->assertEquals( 'success', $result['result'] );

		// Cleanup extra product.
		wp_delete_post( $product2->get_id(), true );
	}

	/**
	 * Test checkout with virtual product.
	 */
	public function test_checkout_virtual_product() {
		if ( ! $this->gateway ) {
			$this->markTestSkipped( 'Gateway not available in test environment' );
		}

		$product = $this->create_simple_product(
			array(
				'name'    => 'Virtual Product',
				'virtual' => true,
			)
		);

		WC()->cart->add_to_cart( $product->get_id(), 1 );

		$order = wc_create_order(
			array(
				'status'         => 'pending',
				'payment_method' => 'xmoney-payments',
			)
		);

		$order->add_product( $product, 1 );
		$order->set_billing_email( 'virtual@example.com' );
		$order->calculate_totals();
		$order->save();

		$result = $this->gateway->process_payment( $order->get_id() );

		$this->assertEquals( 'success', $result['result'], 'Virtual product checkout should succeed' );

		// Cleanup.
		wp_delete_post( $order->get_id(), true );
	}

	/**
	 * Test order notes are added during checkout flow.
	 */
	public function test_order_notes_added() {
		if ( ! $this->gateway ) {
			$this->markTestSkipped( 'Gateway not available in test environment' );
		}

		$product = $this->create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		$order = $this->create_test_order();

		// Process payment.
		$this->gateway->process_payment( $order->get_id() );

		// Simulate IPN.
		Xmoney_Payments_Status_Updater::update_status_i_p_n(
			$order->get_id(),
			Xmoney_Payments_Status_Updater::$result_statuses['COMPLETE_OK']
		);

		// Get order notes.
		$notes = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );

		$this->assertNotEmpty( $notes, 'Order should have notes' );

		// Check for xMoney related note.
		$found_xmoney_note = false;
		foreach ( $notes as $note ) {
			if ( strpos( $note->content, 'xMoney' ) !== false ) {
				$found_xmoney_note = true;
				break;
			}
		}

		$this->assertTrue( $found_xmoney_note, 'Order should have xMoney payment note' );
	}

	/**
	 * Test transaction is logged during checkout.
	 */
	public function test_transaction_logged() {
		global $wpdb;

		$order = $this->create_test_order();

		// Simulate logging a transaction (as would happen during IPN).
		$data = array(
			'id_cart'       => $order->get_id(),
			'status'        => 'complete-ok',
			'transactionId' => wp_rand( 100000, 999999 ),
			'identifier'    => 'checkout_test_' . wp_rand( 1000, 9999 ),
			'customerId'    => wp_rand( 10000, 99999 ),
			'orderId'       => wp_rand( 100000, 999999 ),
			'cardId'        => wp_rand( 10000, 99999 ),
		);

		Xmoney_Payments_Logger::xmoney_payments_log_transaction( $data );

		// Verify transaction was logged.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$transaction = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}xmoney_payments_transactions WHERE id_cart = %d",
				$order->get_id()
			)
		);

		$this->assertNotNull( $transaction, 'Transaction should be logged' );
		$this->assertEquals( $data['status'], $transaction->status, 'Transaction status should match' );
	}

	/**
	 * Test refund flow.
	 */
	public function test_refund_flow() {
		$order = $this->create_test_order();

		// First complete the payment.
		Xmoney_Payments_Status_Updater::update_status_i_p_n(
			$order->get_id(),
			Xmoney_Payments_Status_Updater::$result_statuses['COMPLETE_OK']
		);

		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'processing', $order->get_status() );

		// Log transaction for refund.
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$wpdb->prefix . 'xmoney_payments_transactions',
			array(
				'id_cart'       => $order->get_id(),
				'transactionId' => 12345678,
				'status'        => 'complete-ok',
				'identifier'    => 'refund_test',
				'orderId'       => 87654321,
				'customerId'    => 11111,
				'cardId'        => 22222,
				'checkout_url'  => '',
			)
		);

		// Simulate refund IPN.
		Xmoney_Payments_Status_Updater::update_status_i_p_n(
			$order->get_id(),
			Xmoney_Payments_Status_Updater::$result_statuses['REFUND_OK']
		);

		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'refunded', $order->get_status(), 'Order should be refunded' );
	}
}
