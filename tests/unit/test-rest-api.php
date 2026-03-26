<?php
/**
 * Tests for REST API endpoints.
 *
 * @package Xmoney_Payments
 */

/**
 * Test class for REST API.
 */
class Test_Rest_Api extends Xmoney_Payments_Test_Case {

	/**
	 * Set up test fixtures.
	 */
	public function set_up() {
		parent::set_up();

		// Initialize REST server.
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init' );
	}

	/**
	 * Test REST route is registered.
	 */
	public function test_rest_route_registered() {
		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey( '/xmoney/v1/inline/confirm', $routes, 'Inline confirm route should be registered' );
	}

	/**
	 * Test REST route accepts POST method.
	 */
	public function test_rest_route_accepts_post() {
		$routes = rest_get_server()->get_routes();

		if ( isset( $routes['/xmoney/v1/inline/confirm'] ) ) {
			$route   = $routes['/xmoney/v1/inline/confirm'];
			$methods = array();

			foreach ( $route as $endpoint ) {
				if ( isset( $endpoint['methods'] ) ) {
					$methods = array_merge( $methods, array_keys( $endpoint['methods'] ) );
				}
			}

			$this->assertContains( 'POST', $methods, 'Route should accept POST method' );
		}
	}

	/**
	 * Test REST endpoint with missing order_id returns error.
	 */
	public function test_rest_endpoint_missing_order_id() {
		$request = new WP_REST_Request( 'POST', '/xmoney/v1/inline/confirm' );
		$request->set_param( 'result', array( 'status' => 'complete-ok' ) );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status(), 'Should return 400 for missing order_id' );
		$this->assertFalse( $data['success'], 'Response should indicate failure' );
	}

	/**
	 * Test REST endpoint with missing result returns error.
	 */
	public function test_rest_endpoint_missing_result() {
		$request = new WP_REST_Request( 'POST', '/xmoney/v1/inline/confirm' );
		$request->set_param( 'order_id', 123 );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status(), 'Should return 400 for missing result' );
		$this->assertFalse( $data['success'], 'Response should indicate failure' );
	}

	/**
	 * Test REST endpoint with valid data.
	 */
	public function test_rest_endpoint_valid_data() {
		// Create test order.
		$order = $this->create_test_order();

		$request = new WP_REST_Request( 'POST', '/xmoney/v1/inline/confirm' );
		$request->set_param( 'order_id', $order->get_id() );
		$request->set_param(
			'result',
			array(
				'status'        => 'complete-ok',
				'transactionId' => 123456,
				'id'            => 78910,
				'customerId'    => 12345,
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status(), 'Should return 200 for valid request' );
		$this->assertTrue( $data['success'], 'Response should indicate success' );
		$this->assertArrayHasKey( 'redirect', $data, 'Response should contain redirect URL' );
	}

	/**
	 * Test REST endpoint with failed payment status.
	 */
	public function test_rest_endpoint_failed_payment() {
		$order = $this->create_test_order();

		$request = new WP_REST_Request( 'POST', '/xmoney/v1/inline/confirm' );
		$request->set_param( 'order_id', $order->get_id() );
		$request->set_param(
			'result',
			array(
				'status'        => 'failed',
				'transactionId' => 654321,
				'id'            => 11111,
				'customerId'    => 22222,
			)
		);

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status(), 'Failed payment should still return 200' );

		// Verify order status is updated.
		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'failed', $order->get_status(), 'Order should be failed' );
	}

	/**
	 * Test REST endpoint with orderStatus instead of status.
	 */
	public function test_rest_endpoint_order_status_field() {
		$order = $this->create_test_order();

		$request = new WP_REST_Request( 'POST', '/xmoney/v1/inline/confirm' );
		$request->set_param( 'order_id', $order->get_id() );
		$request->set_param(
			'result',
			array(
				'orderStatus'   => 'complete-ok',
				'transactionId' => 111222,
				'id'            => 333444,
				'customerId'    => 555666,
			)
		);

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status(), 'Should accept orderStatus field' );
	}

	/**
	 * Test REST endpoint updates order correctly.
	 */
	public function test_rest_endpoint_order_update() {
		$order = $this->create_test_order();

		$request = new WP_REST_Request( 'POST', '/xmoney/v1/inline/confirm' );
		$request->set_param( 'order_id', $order->get_id() );
		$request->set_param(
			'result',
			array(
				'status'        => 'complete-ok',
				'transactionId' => 555666,
				'id'            => 777888,
				'customerId'    => 999000,
			)
		);

		rest_get_server()->dispatch( $request );

		// Refresh order and verify.
		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'processing', $order->get_status(), 'Order should be processing' );
	}

	/**
	 * Test REST endpoint with invalid order returns error.
	 */
	public function test_rest_endpoint_invalid_order() {
		$request = new WP_REST_Request( 'POST', '/xmoney/v1/inline/confirm' );
		$request->set_param( 'order_id', 99999999 );
		$request->set_param(
			'result',
			array(
				'status'        => 'complete-ok',
				'transactionId' => 123456,
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status(), 'Should return 400 for invalid order' );
		$this->assertFalse( $data['success'], 'Response should indicate failure' );
	}

	/**
	 * Test REST endpoint returns redirect URL.
	 */
	public function test_rest_endpoint_redirect_url() {
		$order = $this->create_test_order();

		$request = new WP_REST_Request( 'POST', '/xmoney/v1/inline/confirm' );
		$request->set_param( 'order_id', $order->get_id() );
		$request->set_param(
			'result',
			array(
				'status'        => 'complete-ok',
				'transactionId' => 999888,
				'id'            => 777666,
				'customerId'    => 123123,
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertNotEmpty( $data['redirect'], 'Redirect URL should not be empty' );
		$this->assertStringContainsString( 'order-received', $data['redirect'], 'Redirect should be to order received page' );
	}
}

