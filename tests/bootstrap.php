<?php
/**
 * PHPUnit bootstrap file for xMoney Payments plugin tests.
 *
 * @package Xmoney_Payments
 */

// Require composer autoloader if available.
if ( file_exists( dirname( __DIR__ ) . '/vendor/autoload.php' ) ) {
	require_once dirname( __DIR__ ) . '/vendor/autoload.php';
}

// Determine the tests directory (try a handful of possible locations).
$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
$_phpunit_polyfills_path = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' );
if ( false !== $_phpunit_polyfills_path ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path );
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL;
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/includes/functions.php";

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	// Define plugin constants early.
	if ( ! defined( 'XMONEY_PAYMENTS_PLUGIN_DIR' ) ) {
		define( 'XMONEY_PAYMENTS_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
	}
	if ( ! defined( 'XMONEY_PAYMENTS_PLUGIN_URL' ) ) {
		define( 'XMONEY_PAYMENTS_PLUGIN_URL', 'http://example.org/wp-content/plugins/xmoney-payments/' );
	}
	if ( ! defined( 'XMONEY_PAYMENTS_VERSION' ) ) {
		define( 'XMONEY_PAYMENTS_VERSION', '1.0.0' );
	}

	// Set installed option so plugin loads properly.
	update_option( 'xmoney_payments_installed', '1' );

	// Load WooCommerce first.
	$wc_path = dirname( __DIR__ ) . '/woocommerce-example/plugins/woocommerce/woocommerce.php';
	if ( file_exists( $wc_path ) ) {
		require_once $wc_path;
	}

	// Then load our plugin main file.
	require dirname( __DIR__ ) . '/class-xmoney-payments.php';

	// Also load helper files directly to ensure classes are available.
	require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'helpers/class-xmoney-payments-logger.php';
	require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'helpers/class-xmoney-payments-helper-response.php';
	require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'helpers/class-xmoney-payments-helper-notify.php';
	require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'helpers/class-xmoney-payments-status-updater.php';
	require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'helpers/class-xmoney-payments-helper-processor.php';

	// Load install file (defines admin functions).
	require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'includes/install.php';

	// Load inline REST endpoint file.
	require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'includes/class-xmoney-payments-inline-rest.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

/**
 * Install WooCommerce and plugin after setup.
 */
function _install_woocommerce() {
	// Suppress database errors during installation.
	global $wpdb;
	$wpdb->suppress_errors();

	// Set plugins as active.
	update_option(
		'active_plugins',
		array(
			'woocommerce/woocommerce.php',
			'xmoney-payments/class-xmoney-payments.php',
		)
	);

	// Install WooCommerce.
	if ( class_exists( 'WC_Install' ) ) {
		WC_Install::install();
	}

	// Initialize WooCommerce.
	if ( function_exists( 'WC' ) && WC() ) {
		WC()->init();
	}

	// Create xMoney Payments tables.
	if ( function_exists( 'xmoney_payments_install' ) ) {
		xmoney_payments_install();
	}

	$wpdb->suppress_errors( false );
}

tests_add_filter( 'setup_theme', '_install_woocommerce' );

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";

// After WordPress is loaded, fire plugins_loaded to initialize the gateway.
if ( ! class_exists( 'Xmoney_Payments_Gateway' ) && function_exists( 'xmoney_payments_init_gateway_class' ) ) {
	xmoney_payments_init_gateway_class();
}

// Load our test case base class.
require_once __DIR__ . '/class-xmoney-payments-test-case.php';
