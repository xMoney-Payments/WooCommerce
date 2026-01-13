<?php
/**
 * Tests for Xmoney_Payments_Helper_Response class.
 *
 * @package Xmoney_Payments
 */

/**
 * Test class for Helper Response.
 */
class Test_Helper_Response extends Xmoney_Payments_Test_Case {

	/**
	 * Secret key for testing.
	 *
	 * @var string
	 */
	private $test_secret_key = 'test_staging_secret_key_for_testing_purposes';

	/**
	 * Test class exists.
	 */
	public function test_class_exists() {
		$this->assertTrue( class_exists( 'Xmoney_Payments_Helper_Response' ), 'Helper Response class should exist' );
	}

	/**
	 * Test decrypt_message with valid encrypted message.
	 */
	public function test_decrypt_message_valid() {
		$original_data = array(
			'externalOrderId'   => '123',
			'status'            => 'complete-ok',
			'transactionId'     => 456789,
			'identifier'        => 'test_identifier',
			'customerId'        => 12345,
			'orderId'           => 78910,
		);

		$encrypted = $this->generate_encrypted_response( $original_data, $this->test_secret_key );
		$decrypted = Xmoney_Payments_Helper_Response::xmoney_payments_decrypt_message( $encrypted, $this->test_secret_key );

		$this->assertIsArray( $decrypted, 'Decrypted result should be an array' );
		$this->assertEquals( '123', $decrypted['externalOrderId'], 'externalOrderId should match' );
		$this->assertEquals( 'complete-ok', $decrypted['status'], 'status should match' );
		$this->assertEquals( 456789, $decrypted['transactionId'], 'transactionId should match' );
	}

	/**
	 * Test decrypt_message with empty message.
	 */
	public function test_decrypt_message_empty() {
		$result = Xmoney_Payments_Helper_Response::xmoney_payments_decrypt_message( '', $this->test_secret_key );

		$this->assertFalse( $result, 'Empty message should return false' );
	}

	/**
	 * Test decrypt_message with message without comma separator.
	 */
	public function test_decrypt_message_no_separator() {
		$result = Xmoney_Payments_Helper_Response::xmoney_payments_decrypt_message( 'invalidmessage', $this->test_secret_key );

		$this->assertFalse( $result, 'Message without comma should return false' );
	}

	/**
	 * Test decrypt_message with invalid base64 IV.
	 */
	public function test_decrypt_message_invalid_iv() {
		$result = Xmoney_Payments_Helper_Response::xmoney_payments_decrypt_message( '!@#$%^,validbase64', $this->test_secret_key );

		$this->assertFalse( $result, 'Invalid base64 IV should return false' );
	}

	/**
	 * Test decrypt_message with wrong secret key.
	 */
	public function test_decrypt_message_wrong_key() {
		$original_data = array(
			'externalOrderId' => '123',
			'status'          => 'complete-ok',
		);

		$encrypted = $this->generate_encrypted_response( $original_data, $this->test_secret_key );
		$decrypted = Xmoney_Payments_Helper_Response::xmoney_payments_decrypt_message( $encrypted, 'wrong_secret_key' );

		// With wrong key, decryption should fail or return garbage.
		$this->assertTrue( $decrypted === false || ! isset( $decrypted['externalOrderId'] ), 'Wrong key should fail decryption' );
	}

	/**
	 * Test decrypt_message handles externalOrderId with underscore suffix.
	 */
	public function test_decrypt_message_external_order_id_with_suffix() {
		$original_data = array(
			'externalOrderId'   => '123_sub',
			'status'            => 'complete-ok',
			'transactionId'     => 456789,
			'identifier'        => 'test_identifier',
			'customerId'        => 12345,
		);

		$encrypted = $this->generate_encrypted_response( $original_data, $this->test_secret_key );
		$decrypted = Xmoney_Payments_Helper_Response::xmoney_payments_decrypt_message( $encrypted, $this->test_secret_key );

		// The class should strip the suffix.
		$this->assertEquals( '123', $decrypted['externalOrderId'], 'externalOrderId suffix should be stripped' );
	}

	/**
	 * Test check_validation with valid response.
	 */
	public function test_check_validation_valid_response() {
		// Create a test order first.
		$order = $this->create_test_order();

		$response = array(
			'externalOrderId' => (string) $order->get_id(),
			'status'          => 'complete-ok',
			'transactionId'   => 123456,
			'identifier'      => 'test_identifier',
			'customerId'      => 12345,
			'orderId'         => 78910,
		);

		$is_valid = Xmoney_Payments_Helper_Response::xmoney_payments_check_validation( $response );

		$this->assertTrue( $is_valid, 'Valid response should pass validation' );
	}

	/**
	 * Test check_validation with missing externalOrderId.
	 */
	public function test_check_validation_missing_external_order_id() {
		$response = array(
			'status'        => 'complete-ok',
			'transactionId' => 123456,
			'identifier'    => 'test_identifier',
			'customerId'    => 12345,
		);

		$is_valid = Xmoney_Payments_Helper_Response::xmoney_payments_check_validation( $response );

		$this->assertFalse( $is_valid, 'Missing externalOrderId should fail validation' );
	}

	/**
	 * Test check_validation with missing status.
	 */
	public function test_check_validation_missing_status() {
		$response = array(
			'externalOrderId' => '123',
			'transactionId'   => 123456,
			'identifier'      => 'test_identifier',
			'customerId'      => 12345,
		);

		$is_valid = Xmoney_Payments_Helper_Response::xmoney_payments_check_validation( $response );

		$this->assertFalse( $is_valid, 'Missing status should fail validation' );
	}

	/**
	 * Test check_validation with missing identifier.
	 */
	public function test_check_validation_missing_identifier() {
		$response = array(
			'externalOrderId' => '123',
			'status'          => 'complete-ok',
			'transactionId'   => 123456,
			'customerId'      => 12345,
		);

		$is_valid = Xmoney_Payments_Helper_Response::xmoney_payments_check_validation( $response );

		$this->assertFalse( $is_valid, 'Missing identifier should fail validation' );
	}

	/**
	 * Test check_validation with missing transactionId.
	 */
	public function test_check_validation_missing_transaction_id() {
		$response = array(
			'externalOrderId' => '123',
			'status'          => 'complete-ok',
			'identifier'      => 'test_identifier',
			'customerId'      => 12345,
		);

		$is_valid = Xmoney_Payments_Helper_Response::xmoney_payments_check_validation( $response );

		$this->assertFalse( $is_valid, 'Missing transactionId should fail validation' );
	}

	/**
	 * Test check_validation with invalid status.
	 */
	public function test_check_validation_invalid_status() {
		$response = array(
			'externalOrderId' => '123',
			'status'          => 'invalid-status',
			'transactionId'   => 123456,
			'identifier'      => 'test_identifier',
			'customerId'      => 12345,
			'orderId'         => 78910,
		);

		$is_valid = Xmoney_Payments_Helper_Response::xmoney_payments_check_validation( $response );

		$this->assertFalse( $is_valid, 'Invalid status should fail validation' );
	}

	/**
	 * Test check_validation with empty response.
	 */
	public function test_check_validation_empty_response() {
		$is_valid = Xmoney_Payments_Helper_Response::xmoney_payments_check_validation( array() );

		$this->assertFalse( $is_valid, 'Empty response should fail validation' );
	}

	/**
	 * Test check_validation with null response.
	 */
	public function test_check_validation_null_response() {
		$is_valid = Xmoney_Payments_Helper_Response::xmoney_payments_check_validation( null );

		$this->assertFalse( $is_valid, 'Null response should fail validation' );
	}

	/**
	 * Test check_validation with transactionStatus instead of status.
	 */
	public function test_check_validation_with_transaction_status() {
		$order = $this->create_test_order();

		$response = array(
			'externalOrderId'   => (string) $order->get_id(),
			'transactionStatus' => 'complete-ok',
			'transactionId'     => 123456,
			'identifier'        => 'test_identifier',
			'customerId'        => 12345,
			'orderId'           => 78910,
		);

		$is_valid = Xmoney_Payments_Helper_Response::xmoney_payments_check_validation( $response );

		$this->assertTrue( $is_valid, 'Response with transactionStatus should pass validation' );
	}

	/**
	 * Test that all valid statuses pass validation.
	 *
	 * @dataProvider valid_statuses_provider
	 */
	public function test_check_validation_all_valid_statuses( $status ) {
		$order = $this->create_test_order();

		$response = array(
			'externalOrderId' => (string) $order->get_id(),
			'status'          => $status,
			'transactionId'   => 123456,
			'identifier'      => 'test_identifier',
			'customerId'      => 12345,
			'orderId'         => 78910,
		);

		$is_valid = Xmoney_Payments_Helper_Response::xmoney_payments_check_validation( $response );

		$this->assertTrue( $is_valid, "Status '$status' should pass validation" );
	}

	/**
	 * Data provider for valid statuses.
	 *
	 * @return array
	 */
	public function valid_statuses_provider() {
		return array(
			array( 'uncertain' ),
			array( 'in-progress' ),
			array( 'complete-ok' ),
			array( 'complete-failed' ),
			array( 'cancel-ok' ),
			array( 'refund-ok' ),
			array( 'void-ok' ),
			array( 'charge-back' ),
			array( '3d-pending' ),
			array( 'expiring' ),
		);
	}
}

