<?php
/**
 * Tests for AJAX handlers.
 *
 * @package Xmoney_Payments
 */

/**
 * Test class for AJAX handlers.
 *
 * Note: Many tests require the Gateway class which is defined inside plugins_loaded hook.
 * Tests will skip gracefully when the class is not available.
 */
class Test_Ajax_Handlers extends Xmoney_Payments_Test_Case {

	/**
	 * Test AJAX actions are registered.
	 */
	public function test_ajax_actions_registered() {
		if ( ! class_exists( 'Xmoney_Payments_Gateway' ) ) {
			$this->markTestSkipped( 'Gateway class not available - AJAX handlers depend on it' );
		}

		// Check that AJAX actions are registered.
		$this->assertTrue(
			has_action( 'wp_ajax_xmoney_create_draft_order' ),
			'xmoney_create_draft_order AJAX action should be registered'
		);

		$this->assertTrue(
			has_action( 'wp_ajax_nopriv_xmoney_create_draft_order' ),
			'xmoney_create_draft_order nopriv AJAX action should be registered'
		);

		$this->assertTrue(
			has_action( 'wp_ajax_xmoney_update_draft_order' ),
			'xmoney_update_draft_order AJAX action should be registered'
		);

		$this->assertTrue(
			has_action( 'wp_ajax_xmoney_prepare_payment' ),
			'xmoney_prepare_payment AJAX action should be registered'
		);

		$this->assertTrue(
			has_action( 'wp_ajax_xmoney_create_order' ),
			'xmoney_create_order AJAX action should be registered'
		);
	}

	/**
	 * Test gateway instance getter function.
	 */
	public function test_get_gateway_instance() {
		if ( ! function_exists( 'xmoney_payments_get_gateway_instance' ) ) {
			$this->markTestSkipped( 'Gateway singleton function not available in test environment' );
		}

		$this->assertTrue(
			function_exists( 'xmoney_payments_get_gateway_instance' ),
			'xmoney_payments_get_gateway_instance function should exist'
		);

		$gateway = xmoney_payments_get_gateway_instance();

		$this->assertInstanceOf(
			'Xmoney_Payments_Gateway',
			$gateway,
			'Function should return gateway instance'
		);
	}

	/**
	 * Test gateway instance is singleton.
	 */
	public function test_gateway_instance_singleton() {
		if ( ! function_exists( 'xmoney_payments_get_gateway_instance' ) ) {
			$this->markTestSkipped( 'Gateway singleton function not available in test environment' );
		}

		$instance1 = xmoney_payments_get_gateway_instance();
		$instance2 = xmoney_payments_get_gateway_instance();

		$this->assertSame( $instance1, $instance2, 'Gateway instance should be singleton' );
	}

	/**
	 * Test ajax_prepare_payment method exists on gateway.
	 */
	public function test_prepare_payment_method_exists() {
		if ( ! class_exists( 'Xmoney_Payments_Gateway' ) ) {
			$this->markTestSkipped( 'Gateway class not available in test environment' );
		}

		$this->assertTrue(
			method_exists( 'Xmoney_Payments_Gateway', 'ajax_prepare_payment' ),
			'ajax_prepare_payment method should exist on gateway'
		);
	}

	/**
	 * Test ajax_create_order method exists on gateway.
	 */
	public function test_create_order_method_exists() {
		if ( ! class_exists( 'Xmoney_Payments_Gateway' ) ) {
			$this->markTestSkipped( 'Gateway class not available in test environment' );
		}

		$this->assertTrue(
			method_exists( 'Xmoney_Payments_Gateway', 'ajax_create_order' ),
			'ajax_create_order method should exist on gateway'
		);
	}

	/**
	 * Test ajax_create_draft_order method exists on gateway.
	 */
	public function test_create_draft_order_method_exists() {
		if ( ! class_exists( 'Xmoney_Payments_Gateway' ) ) {
			$this->markTestSkipped( 'Gateway class not available in test environment' );
		}

		$this->assertTrue(
			method_exists( 'Xmoney_Payments_Gateway', 'ajax_create_draft_order' ),
			'ajax_create_draft_order method should exist on gateway'
		);
	}

	/**
	 * Test ajax_update_draft_order method exists on gateway.
	 */
	public function test_update_draft_order_method_exists() {
		if ( ! class_exists( 'Xmoney_Payments_Gateway' ) ) {
			$this->markTestSkipped( 'Gateway class not available in test environment' );
		}

		$this->assertTrue(
			method_exists( 'Xmoney_Payments_Gateway', 'ajax_update_draft_order' ),
			'ajax_update_draft_order method should exist on gateway'
		);
	}

	/**
	 * Test AJAX function wrappers exist.
	 */
	public function test_ajax_wrapper_functions_exist() {
		if ( ! function_exists( 'xmoney_payments_ajax_create_draft_order' ) ) {
			$this->markTestSkipped( 'AJAX wrapper functions not available - Gateway class required' );
		}

		$this->assertTrue(
			function_exists( 'xmoney_payments_ajax_create_draft_order' ),
			'xmoney_payments_ajax_create_draft_order function should exist'
		);

		$this->assertTrue(
			function_exists( 'xmoney_payments_ajax_update_draft_order' ),
			'xmoney_payments_ajax_update_draft_order function should exist'
		);

		$this->assertTrue(
			function_exists( 'xmoney_payments_ajax_prepare_payment' ),
			'xmoney_payments_ajax_prepare_payment function should exist'
		);

		$this->assertTrue(
			function_exists( 'xmoney_payments_ajax_create_order' ),
			'xmoney_payments_ajax_create_order function should exist'
		);
	}
}
