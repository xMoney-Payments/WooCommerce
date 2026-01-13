<?php
/**
 * Integration tests for database operations.
 *
 * @package Xmoney_Payments
 */

/**
 * Test class for database operations.
 */
class Test_Database extends Xmoney_Payments_Test_Case {

	/**
	 * Test configuration table exists.
	 */
	public function test_configuration_table_exists() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'xmoney_payments_configuration';
		$exists     = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" );

		$this->assertEquals( $table_name, $exists, 'Configuration table should exist' );
	}

	/**
	 * Test transactions table exists.
	 */
	public function test_transactions_table_exists() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'xmoney_payments_transactions';
		$exists     = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" );

		$this->assertEquals( $table_name, $exists, 'Transactions table should exist' );
	}

	/**
	 * Test configuration table has required columns.
	 */
	public function test_configuration_table_columns() {
		global $wpdb;

		$columns = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}xmoney_payments_configuration" );

		$required_columns = array(
			'id_tw_configuration',
			'live_mode',
			'staging_id',
			'staging_key',
			'live_id',
			'live_key',
			'thankyou_page',
			'suppress_email',
			'contact_email',
		);

		foreach ( $required_columns as $column ) {
			$this->assertContains( $column, $columns, "Column '$column' should exist in configuration table" );
		}
	}

	/**
	 * Test configuration table has optional inline columns.
	 */
	public function test_configuration_table_inline_columns() {
		global $wpdb;

		$columns = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}xmoney_payments_configuration" );

		$inline_columns = array(
			'inline_checkout',
			'enable_saved_cards',
			'checkout_theme',
			'theme_variables',
		);

		foreach ( $inline_columns as $column ) {
			$this->assertContains( $column, $columns, "Inline column '$column' should exist" );
		}
	}

	/**
	 * Test transactions table has required columns.
	 */
	public function test_transactions_table_columns() {
		global $wpdb;

		$columns = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}xmoney_payments_transactions" );

		$required_columns = array(
			'id_tw_transactions',
			'status',
			'checkout_url',
			'id_cart',
			'identifier',
			'orderId',
			'transactionId',
			'customerId',
			'cardId',
		);

		foreach ( $required_columns as $column ) {
			$this->assertContains( $column, $columns, "Column '$column' should exist in transactions table" );
		}
	}

	/**
	 * Test configuration insert.
	 */
	public function test_configuration_insert() {
		global $wpdb;

		$result = $wpdb->insert(
			$wpdb->prefix . 'xmoney_payments_configuration',
			array(
				'live_mode'     => 1,
				'staging_id'    => 'test_staging_insert',
				'staging_key'   => 'test_staging_key_insert',
				'live_id'       => 'test_live_insert',
				'live_key'      => 'test_live_key_insert',
				'thankyou_page' => '/thank-you',
				'contact_email' => 'insert@test.com',
			)
		);

		$this->assertNotFalse( $result, 'Insert should succeed' );
		$this->assertEquals( 1, $result, 'One row should be inserted' );

		// Cleanup.
		$wpdb->delete(
			$wpdb->prefix . 'xmoney_payments_configuration',
			array( 'staging_id' => 'test_staging_insert' )
		);
	}

	/**
	 * Test configuration update.
	 */
	public function test_configuration_update() {
		global $wpdb;

		// Update existing test config.
		$result = $wpdb->update(
			$wpdb->prefix . 'xmoney_payments_configuration',
			array( 'contact_email' => 'updated@test.com' ),
			array( 'id_tw_configuration' => 1 )
		);

		$this->assertNotFalse( $result, 'Update should succeed' );

		// Verify update.
		$config = $wpdb->get_row(
			"SELECT * FROM {$wpdb->prefix}xmoney_payments_configuration WHERE id_tw_configuration = 1"
		);

		$this->assertEquals( 'updated@test.com', $config->contact_email, 'Email should be updated' );

		// Reset.
		$wpdb->update(
			$wpdb->prefix . 'xmoney_payments_configuration',
			array( 'contact_email' => 'test@example.com' ),
			array( 'id_tw_configuration' => 1 )
		);
	}

	/**
	 * Test transaction insert.
	 */
	public function test_transaction_insert() {
		global $wpdb;

		$order = $this->create_test_order();

		$result = $wpdb->insert(
			$wpdb->prefix . 'xmoney_payments_transactions',
			array(
				'status'        => 'complete-ok',
				'checkout_url'  => 'https://example.com/checkout',
				'id_cart'       => $order->get_id(),
				'identifier'    => 'db_test_identifier',
				'orderId'       => 111222,
				'transactionId' => 333444,
				'customerId'    => 555666,
				'cardId'        => 777888,
			)
		);

		$this->assertNotFalse( $result, 'Transaction insert should succeed' );

		// Verify.
		$transaction = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}xmoney_payments_transactions WHERE id_cart = %d",
				$order->get_id()
			)
		);

		$this->assertNotNull( $transaction );
		$this->assertEquals( 'complete-ok', $transaction->status );
	}

	/**
	 * Test transaction update.
	 */
	public function test_transaction_update() {
		global $wpdb;

		$order = $this->create_test_order();

		// Insert first.
		$wpdb->insert(
			$wpdb->prefix . 'xmoney_payments_transactions',
			array(
				'status'        => 'in-progress',
				'checkout_url'  => '',
				'id_cart'       => $order->get_id(),
				'identifier'    => 'update_test_identifier',
				'orderId'       => 999111,
				'transactionId' => 222333,
				'customerId'    => 444555,
				'cardId'        => 666777,
			)
		);

		// Update.
		$result = $wpdb->update(
			$wpdb->prefix . 'xmoney_payments_transactions',
			array( 'status' => 'complete-ok' ),
			array( 'id_cart' => $order->get_id() )
		);

		$this->assertNotFalse( $result, 'Transaction update should succeed' );

		// Verify.
		$transaction = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}xmoney_payments_transactions WHERE id_cart = %d",
				$order->get_id()
			)
		);

		$this->assertEquals( 'complete-ok', $transaction->status, 'Status should be updated' );
	}

	/**
	 * Test transaction delete.
	 */
	public function test_transaction_delete() {
		global $wpdb;

		// Insert.
		$wpdb->insert(
			$wpdb->prefix . 'xmoney_payments_transactions',
			array(
				'status'        => 'complete-ok',
				'checkout_url'  => '',
				'id_cart'       => 99998,
				'identifier'    => 'delete_test',
				'orderId'       => 888999,
				'transactionId' => 111000,
				'customerId'    => 222000,
				'cardId'        => 333000,
			)
		);

		// Delete.
		$result = $wpdb->delete(
			$wpdb->prefix . 'xmoney_payments_transactions',
			array( 'id_cart' => 99998 )
		);

		$this->assertNotFalse( $result, 'Delete should succeed' );

		// Verify.
		$transaction = $wpdb->get_row(
			"SELECT * FROM {$wpdb->prefix}xmoney_payments_transactions WHERE id_cart = 99998"
		);

		$this->assertNull( $transaction, 'Transaction should be deleted' );
	}

	/**
	 * Test multiple transactions for same order.
	 */
	public function test_multiple_transactions_same_order() {
		global $wpdb;

		$order = $this->create_test_order();

		// Insert multiple transactions.
		for ( $i = 1; $i <= 3; $i++ ) {
			$wpdb->insert(
				$wpdb->prefix . 'xmoney_payments_transactions',
				array(
					'status'        => 'complete-ok',
					'checkout_url'  => '',
					'id_cart'       => $order->get_id(),
					'identifier'    => "multi_test_$i",
					'orderId'       => 100000 + $i,
					'transactionId' => 200000 + $i,
					'customerId'    => 300000,
					'cardId'        => 400000,
				)
			);
		}

		// Query all transactions.
		$transactions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}xmoney_payments_transactions WHERE id_cart = %d",
				$order->get_id()
			)
		);

		$this->assertCount( 3, $transactions, 'Should have 3 transactions for order' );
	}

	/**
	 * Test inline checkout toggle.
	 */
	public function test_inline_checkout_toggle() {
		global $wpdb;

		// Enable inline.
		$wpdb->update(
			$wpdb->prefix . 'xmoney_payments_configuration',
			array( 'inline_checkout' => 1 ),
			array( 'id_tw_configuration' => 1 )
		);

		$config = $wpdb->get_row(
			"SELECT inline_checkout FROM {$wpdb->prefix}xmoney_payments_configuration WHERE id_tw_configuration = 1"
		);

		$this->assertEquals( '1', $config->inline_checkout, 'Inline should be enabled' );

		// Disable inline.
		$wpdb->update(
			$wpdb->prefix . 'xmoney_payments_configuration',
			array( 'inline_checkout' => 0 ),
			array( 'id_tw_configuration' => 1 )
		);

		$config = $wpdb->get_row(
			"SELECT inline_checkout FROM {$wpdb->prefix}xmoney_payments_configuration WHERE id_tw_configuration = 1"
		);

		$this->assertEquals( '0', $config->inline_checkout, 'Inline should be disabled' );
	}
}

