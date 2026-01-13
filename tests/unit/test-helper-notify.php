<?php
/**
 * Tests for Xmoney_Payments_Helper_Notify class.
 *
 * @package Xmoney_Payments
 */

/**
 * Test class for Helper Notify.
 */
class Test_Helper_Notify extends Xmoney_Payments_Test_Case {

	/**
	 * Test class exists.
	 */
	public function test_class_exists() {
		$this->assertTrue( class_exists( 'Xmoney_Payments_Helper_Notify' ), 'Helper Notify class should exist' );
	}

	/**
	 * Test get_base64_json_request generates valid base64.
	 */
	public function test_get_base64_json_request_returns_base64() {
		$order_data = array(
			'siteId'   => 'test_site_id',
			'customer' => array(
				'identifier' => 'test_customer',
				'firstName'  => 'John',
				'lastName'   => 'Doe',
				'email'      => 'john@example.com',
			),
			'order'    => array(
				'orderId'  => '123',
				'type'     => 'purchase',
				'amount'   => 100.00,
				'currency' => 'RON',
			),
		);

		$result = Xmoney_Payments_Helper_Notify::get_base64_json_request( $order_data );

		$this->assertIsString( $result, 'Result should be a string' );
		$this->assertNotEmpty( $result, 'Result should not be empty' );

		// Verify it's valid base64.
		$decoded = base64_decode( $result, true );
		$this->assertNotFalse( $decoded, 'Result should be valid base64' );
	}

	/**
	 * Test get_base64_json_request decodes to original data.
	 */
	public function test_get_base64_json_request_decodes_correctly() {
		$order_data = array(
			'siteId' => 'test_site_id',
			'order'  => array(
				'orderId' => '456',
				'amount'  => 250.50,
			),
		);

		$encoded = Xmoney_Payments_Helper_Notify::get_base64_json_request( $order_data );
		$decoded = json_decode( base64_decode( $encoded ), true );

		$this->assertEquals( $order_data['siteId'], $decoded['siteId'], 'Decoded siteId should match' );
		$this->assertEquals( $order_data['order']['orderId'], $decoded['order']['orderId'], 'Decoded orderId should match' );
		$this->assertEquals( $order_data['order']['amount'], $decoded['order']['amount'], 'Decoded amount should match' );
	}

	/**
	 * Test get_base64_json_request handles unicode characters.
	 */
	public function test_get_base64_json_request_handles_unicode() {
		$order_data = array(
			'customer' => array(
				'firstName' => 'Ștefan',
				'lastName'  => 'Müller',
				'city'      => 'București',
			),
		);

		$encoded = Xmoney_Payments_Helper_Notify::get_base64_json_request( $order_data );
		$decoded = json_decode( base64_decode( $encoded ), true );

		$this->assertEquals( 'Ștefan', $decoded['customer']['firstName'], 'Unicode first name should be preserved' );
		$this->assertEquals( 'Müller', $decoded['customer']['lastName'], 'Unicode last name should be preserved' );
		$this->assertEquals( 'București', $decoded['customer']['city'], 'Unicode city should be preserved' );
	}

	/**
	 * Test get_base64_checksum returns valid base64.
	 */
	public function test_get_base64_checksum_returns_base64() {
		$order_data = array(
			'siteId' => 'test_site_id',
			'order'  => array(
				'orderId' => '789',
				'amount'  => 50.00,
			),
		);
		$secret_key = 'test_secret_key';

		$checksum = Xmoney_Payments_Helper_Notify::get_base64_checksum( $order_data, $secret_key );

		$this->assertIsString( $checksum, 'Checksum should be a string' );
		$this->assertNotEmpty( $checksum, 'Checksum should not be empty' );

		// Verify it's valid base64.
		$decoded = base64_decode( $checksum, true );
		$this->assertNotFalse( $decoded, 'Checksum should be valid base64' );
	}

	/**
	 * Test get_base64_checksum produces consistent results.
	 */
	public function test_get_base64_checksum_is_consistent() {
		$order_data = array(
			'siteId' => 'test_site_id',
			'order'  => array(
				'orderId' => '123',
				'amount'  => 100.00,
			),
		);
		$secret_key = 'test_secret_key';

		$checksum1 = Xmoney_Payments_Helper_Notify::get_base64_checksum( $order_data, $secret_key );
		$checksum2 = Xmoney_Payments_Helper_Notify::get_base64_checksum( $order_data, $secret_key );

		$this->assertEquals( $checksum1, $checksum2, 'Same data should produce same checksum' );
	}

	/**
	 * Test get_base64_checksum differs with different secret keys.
	 */
	public function test_get_base64_checksum_differs_with_different_keys() {
		$order_data = array(
			'siteId' => 'test_site_id',
			'order'  => array(
				'orderId' => '123',
				'amount'  => 100.00,
			),
		);

		$checksum1 = Xmoney_Payments_Helper_Notify::get_base64_checksum( $order_data, 'secret_key_1' );
		$checksum2 = Xmoney_Payments_Helper_Notify::get_base64_checksum( $order_data, 'secret_key_2' );

		$this->assertNotEquals( $checksum1, $checksum2, 'Different keys should produce different checksums' );
	}

	/**
	 * Test get_base64_checksum differs with different data.
	 */
	public function test_get_base64_checksum_differs_with_different_data() {
		$secret_key = 'test_secret_key';

		$order_data1 = array( 'order' => array( 'amount' => 100.00 ) );
		$order_data2 = array( 'order' => array( 'amount' => 200.00 ) );

		$checksum1 = Xmoney_Payments_Helper_Notify::get_base64_checksum( $order_data1, $secret_key );
		$checksum2 = Xmoney_Payments_Helper_Notify::get_base64_checksum( $order_data2, $secret_key );

		$this->assertNotEquals( $checksum1, $checksum2, 'Different data should produce different checksums' );
	}

	/**
	 * Test checksum uses SHA512 HMAC.
	 */
	public function test_checksum_uses_sha512_hmac() {
		$order_data = array( 'test' => 'data' );
		$secret_key = 'test_secret';

		$checksum = Xmoney_Payments_Helper_Notify::get_base64_checksum( $order_data, $secret_key );

		// SHA512 produces 64-byte (512-bit) hash, base64 encoded would be ~88 characters.
		$decoded_length = strlen( base64_decode( $checksum ) );
		$this->assertEquals( 64, $decoded_length, 'Decoded checksum should be 64 bytes (SHA512)' );
	}

	/**
	 * Test with empty order data.
	 */
	public function test_with_empty_order_data() {
		$empty_data = array();

		$request  = Xmoney_Payments_Helper_Notify::get_base64_json_request( $empty_data );
		$checksum = Xmoney_Payments_Helper_Notify::get_base64_checksum( $empty_data, 'secret' );

		$this->assertNotEmpty( $request, 'Empty data should still produce request' );
		$this->assertNotEmpty( $checksum, 'Empty data should still produce checksum' );

		// Verify decoded is empty array.
		$decoded = json_decode( base64_decode( $request ), true );
		$this->assertEmpty( $decoded, 'Decoded empty request should be empty array' );
	}
}

