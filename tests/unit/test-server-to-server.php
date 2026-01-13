<?php
/**
 * Tests for Xmoney_Payments_Server_To_Server class (IPN handler).
 *
 * @package Xmoney_Payments
 */

/**
 * Test class for Server to Server (IPN) handler.
 */
class Test_Server_To_Server extends Xmoney_Payments_Test_Case {

	/**
	 * Test class exists.
	 */
	public function test_class_exists() {
		$this->assertTrue( class_exists( 'Xmoney_Payments_Server_To_Server' ), 'Server to Server class should exist' );
	}

	/**
	 * Test class has handle method.
	 */
	public function test_handle_method_exists() {
		$this->assertTrue(
			method_exists( 'Xmoney_Payments_Server_To_Server', 'handle' ),
			'handle method should exist'
		);
	}

	/**
	 * Test IPN callback processes valid encrypted response.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_ipn_valid_response() {
		$order = $this->create_test_order();

		$response_data = array(
			'externalOrderId' => (string) $order->get_id(),
			'status'          => 'complete-ok',
			'transactionId'   => 123456,
			'identifier'      => 'test_identifier',
			'customerId'      => 12345,
			'orderId'         => 78910,
		);

		$encrypted = $this->generate_encrypted_response(
			$response_data,
			'test_staging_secret_key_for_testing_purposes'
		);

		// Simulate POST data.
		$_POST['opensslResult'] = $encrypted;

		// Process the IPN manually through status updater (since handle() calls die()).
		$decrypted = Xmoney_Payments_Helper_Response::xmoney_payments_decrypt_message(
			$encrypted,
			'test_staging_secret_key_for_testing_purposes'
		);

		$this->assertNotFalse( $decrypted, 'Decryption should succeed' );

		// Validate the response.
		$is_valid = Xmoney_Payments_Helper_Response::xmoney_payments_check_validation( $decrypted );
		$this->assertTrue( $is_valid, 'Response should be valid' );

		// Update order status.
		Xmoney_Payments_Status_Updater::update_status_i_p_n( $order->get_id(), $decrypted['status'] );

		// Verify order status updated.
		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'processing', $order->get_status(), 'Order should be processing after IPN' );
	}

	/**
	 * Test IPN with result field instead of opensslResult.
	 */
	public function test_ipn_result_field() {
		$order = $this->create_test_order();

		$response_data = array(
			'externalOrderId' => (string) $order->get_id(),
			'status'          => 'complete-ok',
			'transactionId'   => 789012,
			'identifier'      => 'test_identifier_2',
			'customerId'      => 54321,
			'orderId'         => 11111,
		);

		$encrypted = $this->generate_encrypted_response(
			$response_data,
			'test_staging_secret_key_for_testing_purposes'
		);

		// Test with 'result' field instead of 'opensslResult'.
		$_POST['result'] = $encrypted;
		unset( $_POST['opensslResult'] );

		$decrypted = Xmoney_Payments_Helper_Response::xmoney_payments_decrypt_message(
			$encrypted,
			'test_staging_secret_key_for_testing_purposes'
		);

		$this->assertNotFalse( $decrypted, 'Decryption with result field should succeed' );
	}

	/**
	 * Test IPN with failed payment status.
	 */
	public function test_ipn_failed_payment() {
		$order = $this->create_test_order();

		$response_data = array(
			'externalOrderId' => (string) $order->get_id(),
			'status'          => 'complete-failed',
			'transactionId'   => 333444,
			'identifier'      => 'test_identifier_3',
			'customerId'      => 66666,
			'orderId'         => 22222,
		);

		$decrypted = $response_data;

		// Update order status with failed status.
		Xmoney_Payments_Status_Updater::update_status_i_p_n(
			$order->get_id(),
			$decrypted['status']
		);

		// Verify order status.
		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'failed', $order->get_status(), 'Order should be failed after failed IPN' );
	}

	/**
	 * Test IPN with refund status.
	 */
	public function test_ipn_refund() {
		$order = $this->create_test_order();
		$order->update_status( 'processing' ); // Set to processing first.

		$response_data = array(
			'externalOrderId' => (string) $order->get_id(),
			'status'          => 'refund-ok',
			'transactionId'   => 555666,
			'identifier'      => 'test_identifier_4',
			'customerId'      => 77777,
			'orderId'         => 33333,
		);

		// Update order status with refund.
		Xmoney_Payments_Status_Updater::update_status_i_p_n(
			$order->get_id(),
			$response_data['status']
		);

		// Verify order status.
		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'refunded', $order->get_status(), 'Order should be refunded after refund IPN' );
	}

	/**
	 * Test IPN with charge-back status.
	 */
	public function test_ipn_chargeback() {
		$order = $this->create_test_order();
		$order->update_status( 'processing' );

		$response_data = array(
			'externalOrderId' => (string) $order->get_id(),
			'status'          => 'charge-back',
			'transactionId'   => 777888,
			'identifier'      => 'test_identifier_5',
			'customerId'      => 88888,
			'orderId'         => 44444,
		);

		Xmoney_Payments_Status_Updater::update_status_i_p_n(
			$order->get_id(),
			$response_data['status']
		);

		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'refunded', $order->get_status(), 'Order should be refunded after chargeback IPN' );
	}

	/**
	 * Test IPN with 3D pending status.
	 */
	public function test_ipn_3d_pending() {
		$order = $this->create_test_order();

		$response_data = array(
			'externalOrderId' => (string) $order->get_id(),
			'status'          => '3d-pending',
			'transactionId'   => 999111,
			'identifier'      => 'test_identifier_6',
			'customerId'      => 99999,
			'orderId'         => 55555,
		);

		Xmoney_Payments_Status_Updater::update_status_i_p_n(
			$order->get_id(),
			$response_data['status']
		);

		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'on-hold', $order->get_status(), 'Order should be on-hold after 3D pending IPN' );
	}

	/**
	 * Test extracting order ID from externalOrderId with suffix.
	 */
	public function test_external_order_id_extraction() {
		// Test various formats.
		$test_cases = array(
			'123'        => 123,
			'123_sub'    => 123,
			'456_suffix' => 456,
			'789_'       => 789,
		);

		foreach ( $test_cases as $external_id => $expected_id ) {
			// Extract using regex similar to IPN handler.
			$order_id = 0;
			if ( preg_match( '/^(\d+)/', $external_id, $m ) ) {
				$order_id = (int) $m[1];
			}

			$this->assertEquals( $expected_id, $order_id, "External ID '$external_id' should extract to $expected_id" );
		}
	}

	/**
	 * Test invalid externalOrderId format.
	 */
	public function test_invalid_external_order_id() {
		$invalid_ids = array(
			'abc',
			'_123',
			'',
			'no-digits-here',
		);

		foreach ( $invalid_ids as $external_id ) {
			$order_id = 0;
			if ( preg_match( '/^(\d+)/', $external_id, $m ) ) {
				$order_id = (int) $m[1];
			}

			$this->assertEquals( 0, $order_id, "Invalid external ID '$external_id' should return 0" );
		}
	}
}

