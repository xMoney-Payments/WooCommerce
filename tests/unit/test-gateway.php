<?php
/**
 * Tests for xMoney Payments Gateway class.
 *
 * @package Xmoney_Payments
 */

/**
 * Test class for the payment gateway.
 */
class Test_Gateway extends Xmoney_Payments_Test_Case {

	/**
	 * Test gateway class exists.
	 * Note: Gateway class is defined inside plugins_loaded hook, may not be available in test env.
	 */
	public function test_gateway_class_exists() {
		if ( ! class_exists( 'Xmoney_Payments_Gateway' ) ) {
			$this->markTestSkipped( 'Gateway class not available - defined inside plugins_loaded hook' );
		}
		$this->assertTrue( class_exists( 'Xmoney_Payments_Gateway' ), 'Gateway class should exist' );
	}

	/**
	 * Test gateway initialization.
	 */
	public function test_gateway_initialization() {
		$this->assert_gateway_initialized();

		$this->assertEquals( 'xmoney-payments', $this->gateway->id, 'Gateway ID should be xmoney-payments' );
		$this->assertEquals( 'xMoney Payments', $this->gateway->method_title, 'Method title should be xMoney Payments' );
	}

	/**
	 * Test gateway supports products and refunds.
	 */
	public function test_gateway_supports() {
		$this->assert_gateway_initialized();

		$this->assertTrue( $this->gateway->supports( 'products' ), 'Gateway should support products' );
		$this->assertTrue( $this->gateway->supports( 'refunds' ), 'Gateway should support refunds' );
	}

	/**
	 * Test gateway supports subscriptions when WC Subscriptions is active.
	 */
	public function test_gateway_subscription_support() {
		$this->assert_gateway_initialized();

		if ( class_exists( 'WC_Subscriptions' ) ) {
			$this->assertTrue( $this->gateway->supports( 'subscriptions' ) );
			$this->assertTrue( $this->gateway->supports( 'subscription_cancellation' ) );
			$this->assertTrue( $this->gateway->supports( 'subscription_suspension' ) );
			$this->assertTrue( $this->gateway->supports( 'subscription_reactivation' ) );
		} else {
			$this->assertNotContains( 'subscriptions', $this->gateway->supports );
		}
	}

	/**
	 * Test gateway has correct icon URL.
	 */
	public function test_gateway_icon() {
		$this->assert_gateway_initialized();

		$this->assertStringContainsString( 'logo.png', $this->gateway->icon, 'Gateway should have logo.png icon' );
	}

	/**
	 * Test gateway has form fields (has_fields is true for inline checkout).
	 */
	public function test_gateway_has_fields() {
		$this->assert_gateway_initialized();

		$this->assertTrue( $this->gateway->has_fields, 'Gateway should have form fields' );
	}

	/**
	 * Test gateway form fields configuration.
	 */
	public function test_gateway_form_fields() {
		$this->assert_gateway_initialized();

		$this->assertArrayHasKey( 'enabled', $this->gateway->form_fields, 'Should have enabled field' );
		$this->assertArrayHasKey( 'title', $this->gateway->form_fields, 'Should have title field' );
		$this->assertArrayHasKey( 'description', $this->gateway->form_fields, 'Should have description field' );
		$this->assertArrayHasKey( 'enable_for_methods', $this->gateway->form_fields, 'Should have enable_for_methods field' );
		$this->assertArrayHasKey( 'enable_for_virtual', $this->gateway->form_fields, 'Should have enable_for_virtual field' );
	}

	/**
	 * Test gateway availability - basic check.
	 */
	public function test_gateway_is_available() {
		$this->assert_gateway_initialized();

		// Gateway should be available by default.
		$available = $this->gateway->is_available();

		$this->assertIsBool( $available, 'is_available should return boolean' );
	}

	/**
	 * Test process_payment returns correct structure.
	 */
	public function test_process_payment_returns_array() {
		$this->assert_gateway_initialized();

		// Create test order.
		$order = $this->create_test_order();

		// Process payment.
		$result = $this->gateway->process_payment( $order->get_id() );

		$this->assertIsArray( $result, 'process_payment should return array' );
		$this->assertArrayHasKey( 'result', $result, 'Result should have result key' );
		$this->assertArrayHasKey( 'redirect', $result, 'Result should have redirect key' );
	}

	/**
	 * Test process_payment success result.
	 */
	public function test_process_payment_success() {
		$this->assert_gateway_initialized();

		$order  = $this->create_test_order();
		$result = $this->gateway->process_payment( $order->get_id() );

		$this->assertEquals( 'success', $result['result'], 'Result should be success' );
	}

	/**
	 * Test process_payment redirect URL contains order_id.
	 */
	public function test_process_payment_redirect_url() {
		$this->assert_gateway_initialized();

		$order  = $this->create_test_order();
		$result = $this->gateway->process_payment( $order->get_id() );

		// For redirect flow (non-inline), URL should contain order_id.
		if ( ! function_exists( 'xmoney_payments_is_inline_enabled' ) || ! xmoney_payments_is_inline_enabled() ) {
			$this->assertStringContainsString( 'order_id=' . $order->get_id(), $result['redirect'], 'Redirect URL should contain order_id' );
		}
	}

	/**
	 * Test process_refund with invalid order.
	 */
	public function test_process_refund_invalid_order() {
		$this->assert_gateway_initialized();

		$result = $this->gateway->process_refund( 99999999, 10.00, 'Test refund' );

		$this->assertInstanceOf( 'WP_Error', $result, 'Should return WP_Error for invalid order' );
	}

	/**
	 * Test process_refund with zero amount.
	 */
	public function test_process_refund_zero_amount() {
		$this->assert_gateway_initialized();

		$order = $this->create_test_order();

		// Insert a transaction for this order.
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'xmoney_payments_transactions',
			array(
				'id_cart'       => $order->get_id(),
				'transactionId' => 123456,
				'status'        => 'complete-ok',
				'identifier'    => 'test_identifier',
				'orderId'       => 78910,
				'customerId'    => 11111,
				'cardId'        => 22222,
				'checkout_url'  => '',
			)
		);

		$result = $this->gateway->process_refund( $order->get_id(), 0, 'Test refund' );

		$this->assertInstanceOf( 'WP_Error', $result, 'Should return WP_Error for zero amount' );
	}

	/**
	 * Test gateway is added to WooCommerce payment gateways.
	 */
	public function test_gateway_added_to_woocommerce() {
		if ( ! class_exists( 'Xmoney_Payments_Gateway' ) ) {
			$this->markTestSkipped( 'Gateway class not available in test environment' );
		}

		$gateways = WC()->payment_gateways()->payment_gateways();

		$this->assertArrayHasKey( 'xmoney-payments', $gateways, 'xMoney Payments gateway should be registered' );
	}
}

