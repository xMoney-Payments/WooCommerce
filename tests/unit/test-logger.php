<?php
/**
 * Tests for Xmoney_Payments_Logger class.
 *
 * @package Xmoney_Payments
 */

/**
 * Test class for Logger.
 */
class Test_Logger extends Xmoney_Payments_Test_Case {

	/**
	 * Test class exists.
	 */
	public function test_class_exists() {
		$this->assertTrue( class_exists( 'Xmoney_Payments_Logger' ), 'Logger class should exist' );
	}

	/**
	 * Test xmoney_payments_log method exists.
	 */
	public function test_log_method_exists() {
		$this->assertTrue(
			method_exists( 'Xmoney_Payments_Logger', 'xmoney_payments_log' ),
			'xmoney_payments_log method should exist'
		);
	}

	/**
	 * Test xmoney_payments_log_transaction method exists.
	 */
	public function test_log_transaction_method_exists() {
		$this->assertTrue(
			method_exists( 'Xmoney_Payments_Logger', 'xmoney_payments_log_transaction' ),
			'xmoney_payments_log_transaction method should exist'
		);
	}

	/**
	 * Test xmoney_payments_update_transaction_status method exists.
	 */
	public function test_update_transaction_status_method_exists() {
		$this->assertTrue(
			method_exists( 'Xmoney_Payments_Logger', 'xmoney_payments_update_transaction_status' ),
			'xmoney_payments_update_transaction_status method should exist'
		);
	}

	/**
	 * Test xmoney_payments_update_transaction_id method exists.
	 */
	public function test_update_transaction_id_method_exists() {
		$this->assertTrue(
			method_exists( 'Xmoney_Payments_Logger', 'xmoney_payments_update_transaction_id' ),
			'xmoney_payments_update_transaction_id method should exist'
		);
	}

	/**
	 * Test log_transaction inserts new transaction.
	 */
	public function test_log_transaction_insert() {
		global $wpdb;

		$order = $this->create_test_order();

		$data = array(
			'id_cart'       => $order->get_id(),
			'status'        => 'complete-ok',
			'transactionId' => 123456,
			'identifier'    => 'test_identifier',
			'customerId'    => 12345,
			'orderId'       => 78910,
			'cardId'        => 99999,
		);

		Xmoney_Payments_Logger::xmoney_payments_log_transaction( $data );

		// Verify transaction was inserted.
		$transaction = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}xmoney_payments_transactions WHERE transactionId = %d",
				$data['transactionId']
			)
		);

		$this->assertNotNull( $transaction, 'Transaction should be inserted' );
		$this->assertEquals( $data['status'], $transaction->status, 'Status should match' );
		$this->assertEquals( $data['identifier'], $transaction->identifier, 'Identifier should match' );
	}

	/**
	 * Test log_transaction updates existing transaction.
	 */
	public function test_log_transaction_update() {
		global $wpdb;

		$order = $this->create_test_order();

		// Insert initial transaction.
		$data = array(
			'id_cart'       => $order->get_id(),
			'status'        => 'in-progress',
			'transactionId' => 654321,
			'identifier'    => 'test_identifier_2',
			'customerId'    => 54321,
			'orderId'       => 11111,
			'cardId'        => 88888,
		);

		Xmoney_Payments_Logger::xmoney_payments_log_transaction( $data );

		// Update with new status.
		$data['status'] = 'complete-ok';
		Xmoney_Payments_Logger::xmoney_payments_log_transaction( $data );

		// Verify transaction was updated.
		$transaction = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}xmoney_payments_transactions WHERE transactionId = %d",
				$data['transactionId']
			)
		);

		$this->assertEquals( 'complete-ok', $transaction->status, 'Status should be updated' );
	}

	/**
	 * Test update_transaction_status updates status.
	 */
	public function test_update_transaction_status() {
		global $wpdb;

		$order = $this->create_test_order();

		// Insert transaction first.
		$wpdb->insert(
			$wpdb->prefix . 'xmoney_payments_transactions',
			array(
				'id_cart'       => $order->get_id(),
				'status'        => 'in-progress',
				'transactionId' => 111222,
				'identifier'    => 'test_identifier_3',
				'customerId'    => 33333,
				'orderId'       => 44444,
				'cardId'        => 55555,
				'checkout_url'  => '',
			)
		);

		// Update status.
		Xmoney_Payments_Logger::xmoney_payments_update_transaction_status(
			$order->get_id(),
			'refund-ok'
		);

		// Verify status was updated.
		$transaction = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}xmoney_payments_transactions WHERE id_cart = %d",
				$order->get_id()
			)
		);

		$this->assertEquals( 'refund-ok', $transaction->status, 'Status should be updated' );
	}

	/**
	 * Test update_transaction_id updates transactionId.
	 */
	public function test_update_transaction_id() {
		global $wpdb;

		// Insert transaction first.
		$wpdb->insert(
			$wpdb->prefix . 'xmoney_payments_transactions',
			array(
				'id_cart'       => 999,
				'status'        => 'complete-ok',
				'transactionId' => 0,
				'identifier'    => 'test_identifier_4',
				'customerId'    => 66666,
				'orderId'       => 77777,
				'cardId'        => 88888,
				'checkout_url'  => '',
			)
		);

		// Update transaction ID.
		Xmoney_Payments_Logger::xmoney_payments_update_transaction_id( 77777, 999888 );

		// Verify transactionId was updated.
		$transaction = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}xmoney_payments_transactions WHERE orderId = %d",
				77777
			)
		);

		$this->assertEquals( 999888, $transaction->transactionId, 'transactionId should be updated' );
	}

	/**
	 * Test update_transaction_status does nothing for non-existent transaction.
	 */
	public function test_update_transaction_status_nonexistent() {
		global $wpdb;

		// Try to update non-existent transaction.
		Xmoney_Payments_Logger::xmoney_payments_update_transaction_status( 99999999, 'complete-ok' );

		// Should not throw error, just do nothing.
		$this->assertTrue( true, 'Should not throw error for non-existent transaction' );
	}

	/**
	 * Test log directory is created.
	 */
	public function test_log_directory_creation() {
		// Call log method.
		Xmoney_Payments_Logger::xmoney_payments_log( 'Test log message' );

		// Check if directory exists.
		$uploads  = wp_upload_dir();
		$base_dir = trailingslashit( $uploads['basedir'] ) . 'xmoney-payments';
		$log_dir  = trailingslashit( $base_dir ) . 'logs';

		$this->assertDirectoryExists( $log_dir, 'Log directory should be created' );
	}

	/**
	 * Test log index file is created.
	 */
	public function test_log_index_file_creation() {
		// Call log method.
		Xmoney_Payments_Logger::xmoney_payments_log( 'Test log message' );

		// Check if index file exists.
		$uploads    = wp_upload_dir();
		$log_dir    = trailingslashit( $uploads['basedir'] ) . 'xmoney-payments/logs';
		$index_file = trailingslashit( $log_dir ) . 'index.php';

		$this->assertFileExists( $index_file, 'Index.php file should be created in logs directory' );
	}

	/**
	 * Test log file is created and contains message.
	 */
	public function test_log_file_creation() {
		$test_message = 'Test log message ' . wp_rand( 1000, 9999 );

		// Call log method.
		Xmoney_Payments_Logger::xmoney_payments_log( $test_message );

		// Check if log file exists and contains message.
		$uploads  = wp_upload_dir();
		$log_file = trailingslashit( $uploads['basedir'] ) . 'xmoney-payments/logs/xmoney-payments-log.txt';

		$this->assertFileExists( $log_file, 'Log file should be created' );

		$contents = file_get_contents( $log_file );
		$this->assertStringContainsString( $test_message, $contents, 'Log file should contain the test message' );
	}

	/**
	 * Test log with array message.
	 */
	public function test_log_array_message() {
		$test_array = array(
			'key1' => 'value1',
			'key2' => 'value2',
		);

		// Call log method with array.
		Xmoney_Payments_Logger::xmoney_payments_log( $test_array );

		// Log file should contain JSON encoded array.
		$uploads  = wp_upload_dir();
		$log_file = trailingslashit( $uploads['basedir'] ) . 'xmoney-payments/logs/xmoney-payments-log.txt';

		$contents = file_get_contents( $log_file );
		$this->assertStringContainsString( '"key1":"value1"', $contents, 'Log should contain JSON encoded array' );
	}

	/**
	 * Test log with false/empty message creates newline.
	 */
	public function test_log_empty_message() {
		// Call log with false (creates separator line).
		Xmoney_Payments_Logger::xmoney_payments_log( false );

		// Should not throw error.
		$this->assertTrue( true, 'Empty log message should not throw error' );
	}
}

