<?php
/**
 * Tests for plugin initialization.
 *
 * @package Xmoney_Payments
 */

/**
 * Test class for plugin initialization.
 */
class Test_Plugin_Initialization extends Xmoney_Payments_Test_Case {

	/**
	 * Test that the main plugin class exists.
	 */
	public function test_plugin_class_exists() {
		$this->assertTrue( class_exists( 'Xmoney_Payments' ), 'Main plugin class should exist' );
	}

	/**
	 * Test plugin singleton pattern.
	 */
	public function test_plugin_singleton_instance() {
		$instance1 = Xmoney_Payments::instance();
		$instance2 = Xmoney_Payments::instance();

		$this->assertSame( $instance1, $instance2, 'Singleton should return same instance' );
	}

	/**
	 * Test that plugin constants are defined.
	 */
	public function test_plugin_constants_defined() {
		$this->assertTrue( defined( 'XMONEY_PAYMENTS_PLUGIN_DIR' ), 'XMONEY_PAYMENTS_PLUGIN_DIR should be defined' );
		$this->assertTrue( defined( 'XMONEY_PAYMENTS_PLUGIN_URL' ), 'XMONEY_PAYMENTS_PLUGIN_URL should be defined' );
		$this->assertTrue( defined( 'XMONEY_PAYMENTS_VERSION' ), 'XMONEY_PAYMENTS_VERSION should be defined' );
	}

	/**
	 * Test plugin directory constant points to correct path.
	 */
	public function test_plugin_dir_path() {
		$this->assertStringEndsWith( '/', XMONEY_PAYMENTS_PLUGIN_DIR, 'Plugin dir should end with trailing slash' );
		$this->assertDirectoryExists( XMONEY_PAYMENTS_PLUGIN_DIR, 'Plugin directory should exist' );
	}

	/**
	 * Test that helper classes exist.
	 */
	public function test_helper_classes_exist() {
		// These classes should be loaded after plugin initialization.
		$this->assertTrue( class_exists( 'Xmoney_Payments_Logger' ), 'Logger class should exist' );
		$this->assertTrue( class_exists( 'Xmoney_Payments_Helper_Response' ), 'Helper Response class should exist' );
		$this->assertTrue( class_exists( 'Xmoney_Payments_Helper_Notify' ), 'Helper Notify class should exist' );
		$this->assertTrue( class_exists( 'Xmoney_Payments_Status_Updater' ), 'Status Updater class should exist' );
		$this->assertTrue( class_exists( 'Xmoney_Payments_Helper_Processor' ), 'Helper Processor class should exist' );
	}

	/**
	 * Test query vars filter registration.
	 */
	public function test_query_vars_filter() {
		$instance = Xmoney_Payments::instance();
		$vars     = $instance->xmoney_payments_query_vars_filter( array() );

		$this->assertContains( 'order_id', $vars, 'order_id query var should be registered' );
		$this->assertContains( 'twispay-ipn', $vars, 'twispay-ipn query var should be registered' );
	}

	/**
	 * Test xmoney_payments_instance function returns instance when WooCommerce is active.
	 */
	public function test_instance_function_returns_instance() {
		$instance = xmoney_payments_instance();

		// If WooCommerce is active, should return instance.
		if ( class_exists( 'WooCommerce' ) ) {
			$this->assertInstanceOf( 'Xmoney_Payments', $instance );
		}
	}

	/**
	 * Test that plugin options are created on install.
	 */
	public function test_plugin_installed_option() {
		$installed = get_option( 'xmoney_payments_installed' );

		$this->assertNotFalse( $installed, 'xmoney_payments_installed option should be set' );
	}
}

