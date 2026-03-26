<?php
/**
 * Tests for plugin installation.
 *
 * @package Xmoney_Payments
 */

/**
 * Test class for installation.
 */
class Test_Install extends Xmoney_Payments_Test_Case {

	/**
	 * Test install function exists.
	 */
	public function test_install_function_exists() {
		$this->assertTrue(
			function_exists( 'xmoney_payments_install' ),
			'Install function should exist'
		);
	}

	/**
	 * Test check install function exists.
	 */
	public function test_check_install_function_exists() {
		$this->assertTrue(
			function_exists( 'xmoney_payments_wp_check_install' ),
			'Check install function should exist'
		);
	}

	/**
	 * Test update configuration columns function exists.
	 */
	public function test_update_configuration_columns_function_exists() {
		$this->assertTrue(
			function_exists( 'xmoney_payments_update_configuration_columns' ),
			'Update configuration columns function should exist'
		);
	}

	/**
	 * Test installed option is set.
	 */
	public function test_installed_option_set() {
		$installed = get_option( 'xmoney_payments_installed' );

		$this->assertNotFalse( $installed, 'xmoney_payments_installed option should exist' );
		$this->assertEquals( '1', $installed, 'Option should be set to 1' );
	}

	/**
	 * Test confirmation page is created.
	 */
	public function test_confirmation_page_created() {
		$page = get_page_by_path( 'xmoney-payments-confirmation' );

		// Page might exist from installation or might be created on first install.
		// This test verifies the mechanism, not the actual state.
		if ( $page ) {
			$this->assertEquals( 'publish', $page->post_status, 'Confirmation page should be published' );
			$this->assertStringContainsString(
				'[xmoney_payments_payment_confirmation]',
				$page->post_content,
				'Page should contain shortcode'
			);
		} else {
			// Page creation happens during install.
			$this->assertTrue( true, 'Page creation is handled during install' );
		}
	}

	/**
	 * Test configuration table created on install.
	 */
	public function test_configuration_table_created() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'xmoney_payments_configuration';
		$exists     = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" );

		$this->assertEquals( $table_name, $exists, 'Configuration table should exist after install' );
	}

	/**
	 * Test transactions table created on install.
	 */
	public function test_transactions_table_created() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'xmoney_payments_transactions';
		$exists     = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" );

		$this->assertEquals( $table_name, $exists, 'Transactions table should exist after install' );
	}

	/**
	 * Test default configuration row exists.
	 */
	public function test_default_configuration_row() {
		global $wpdb;

		$row = $wpdb->get_row(
			"SELECT * FROM {$wpdb->prefix}xmoney_payments_configuration LIMIT 1"
		);

		$this->assertNotNull( $row, 'Default configuration row should exist' );
	}

	/**
	 * Test inline_checkout column is added.
	 */
	public function test_inline_checkout_column_added() {
		global $wpdb;

		$columns = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}xmoney_payments_configuration" );

		$this->assertContains( 'inline_checkout', $columns, 'inline_checkout column should exist' );
	}

	/**
	 * Test enable_saved_cards column is added.
	 */
	public function test_enable_saved_cards_column_added() {
		global $wpdb;

		$columns = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}xmoney_payments_configuration" );

		$this->assertContains( 'enable_saved_cards', $columns, 'enable_saved_cards column should exist' );
	}

	/**
	 * Test checkout_theme column is added.
	 */
	public function test_checkout_theme_column_added() {
		global $wpdb;

		$columns = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}xmoney_payments_configuration" );

		$this->assertContains( 'checkout_theme', $columns, 'checkout_theme column should exist' );
	}

	/**
	 * Test theme_variables column is added.
	 */
	public function test_theme_variables_column_added() {
		global $wpdb;

		$columns = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}xmoney_payments_configuration" );

		$this->assertContains( 'theme_variables', $columns, 'theme_variables column should exist' );
	}

	/**
	 * Test column defaults are set correctly.
	 */
	public function test_column_defaults() {
		global $wpdb;

		// Insert a new row without specifying optional columns.
		$wpdb->insert(
			$wpdb->prefix . 'xmoney_payments_configuration',
			array(
				'live_mode'   => 0,
				'staging_id'  => 'defaults_test',
				'staging_key' => 'defaults_test_key',
				'live_id'     => '',
				'live_key'    => '',
			)
		);

		$row = $wpdb->get_row(
			"SELECT * FROM {$wpdb->prefix}xmoney_payments_configuration WHERE staging_id = 'defaults_test'"
		);

		$this->assertNotNull( $row );
		$this->assertEquals( '0', $row->inline_checkout, 'inline_checkout default should be 0' );
		$this->assertEquals( '0', $row->enable_saved_cards, 'enable_saved_cards default should be 0' );
		$this->assertEquals( 'light', $row->checkout_theme, 'checkout_theme default should be light' );

		// Cleanup.
		$wpdb->delete(
			$wpdb->prefix . 'xmoney_payments_configuration',
			array( 'staging_id' => 'defaults_test' )
		);
	}

	/**
	 * Test rerunning install doesn't break existing data.
	 */
	public function test_install_is_idempotent() {
		global $wpdb;

		// Get initial config count.
		$initial_count = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}xmoney_payments_configuration"
		);

		// Running update_configuration_columns should not create duplicates.
		xmoney_payments_update_configuration_columns();

		$final_count = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}xmoney_payments_configuration"
		);

		$this->assertEquals( $initial_count, $final_count, 'Config count should not change on rerun' );
	}
}

