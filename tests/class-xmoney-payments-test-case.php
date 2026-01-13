<?php
/**
 * Base test case class for xMoney Payments plugin tests.
 *
 * @package Xmoney_Payments
 */

/**
 * Base test case for xMoney Payments plugin.
 */
class Xmoney_Payments_Test_Case extends WP_UnitTestCase {

	/**
	 * Gateway instance for testing.
	 *
	 * @var Xmoney_Payments_Gateway|null
	 */
	protected $gateway;

	/**
	 * Test order instance.
	 *
	 * @var WC_Order|null
	 */
	protected $order;

	/**
	 * Test product instance.
	 *
	 * @var WC_Product|null
	 */
	protected $product;

	/**
	 * Set up test fixtures.
	 */
	public function set_up() {
		parent::set_up();

		// Ensure WooCommerce is loaded.
		if ( function_exists( 'WC' ) && WC() ) {
			WC()->init();
		}

		// Initialize gateway.
		if ( class_exists( 'Xmoney_Payments_Gateway' ) ) {
			$this->gateway = new Xmoney_Payments_Gateway();
		}

		// Create test configuration in database.
		$this->create_test_configuration();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tear_down() {
		// Clean up test data.
		$this->cleanup_test_data();

		parent::tear_down();
	}

	/**
	 * Create a simple product for testing.
	 *
	 * @param array $args Product arguments.
	 * @return WC_Product_Simple|null
	 */
	protected function create_simple_product( $args = array() ) {
		if ( ! class_exists( 'WC_Product_Simple' ) ) {
			$this->markTestSkipped( 'WooCommerce is not available' );
			return null;
		}

		$defaults = array(
			'name'          => 'Test Product',
			'regular_price' => '10.00',
			'price'         => '10.00',
			'sku'           => 'TEST-SKU-' . wp_rand( 1000, 9999 ),
			'manage_stock'  => false,
			'tax_status'    => 'taxable',
			'downloadable'  => false,
			'virtual'       => false,
			'stock_status'  => 'instock',
			'weight'        => '1.1',
		);

		$args    = wp_parse_args( $args, $defaults );
		$product = new WC_Product_Simple();

		$product->set_props( $args );
		$product->save();

		$this->product = $product;

		return $product;
	}

	/**
	 * Create a test order.
	 *
	 * @param array $args Order arguments.
	 * @return WC_Order|null
	 */
	protected function create_test_order( $args = array() ) {
		if ( ! function_exists( 'wc_create_order' ) ) {
			$this->markTestSkipped( 'WooCommerce is not available' );
			return null;
		}

		$defaults = array(
			'status'         => 'pending',
			'customer_id'    => 0,
			'payment_method' => 'xmoney-payments',
		);

		$args = wp_parse_args( $args, $defaults );

		// Create order.
		$order = wc_create_order( $args );

		// Add product to order.
		if ( ! $this->product ) {
			$this->create_simple_product();
		}

		if ( $this->product ) {
			$order->add_product( $this->product, 1 );
		}

		// Set billing address.
		$order->set_billing_first_name( 'John' );
		$order->set_billing_last_name( 'Doe' );
		$order->set_billing_email( 'john.doe@example.com' );
		$order->set_billing_phone( '+40123456789' );
		$order->set_billing_country( 'RO' );
		$order->set_billing_city( 'Bucharest' );
		$order->set_billing_address_1( '123 Test Street' );
		$order->set_billing_postcode( '012345' );

		// Calculate totals.
		$order->calculate_totals();
		$order->save();

		$this->order = $order;

		return $order;
	}

	/**
	 * Create test configuration in database.
	 */
	protected function create_test_configuration() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'xmoney_payments_configuration';

		// Check if table exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );

		if ( ! $table_exists ) {
			// Create configuration table.
			$charset_collate = $wpdb->get_charset_collate();
			$sql             = "CREATE TABLE $table_name (
				id_tw_configuration int(10) NOT NULL AUTO_INCREMENT,
				live_mode int(10) NOT NULL DEFAULT 0,
				staging_id varchar(255) NOT NULL DEFAULT 'test_staging_id',
				staging_key varchar(255) NOT NULL DEFAULT 'test_staging_key',
				live_id varchar(255) NOT NULL DEFAULT 'test_live_id',
				live_key varchar(255) NOT NULL DEFAULT 'test_live_key',
				thankyou_page varchar(255) NOT NULL DEFAULT '0',
				suppress_email int(10) NOT NULL DEFAULT '0',
				contact_email varchar(50) NOT NULL DEFAULT 'test@example.com',
				inline_checkout TINYINT(1) NOT NULL DEFAULT 0,
				enable_saved_cards TINYINT(1) NOT NULL DEFAULT 0,
				checkout_theme VARCHAR(20) NOT NULL DEFAULT 'light',
				theme_variables TEXT,
				PRIMARY KEY  (id_tw_configuration)
			) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}

		// Insert or update test configuration.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE id_tw_configuration = 1', $table_name ) );

		if ( $existing ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table_name,
				array(
					'live_mode'          => 0,
					'staging_id'         => 'test_staging_id',
					'staging_key'        => 'test_staging_secret_key_for_testing_purposes',
					'live_id'            => 'test_live_id',
					'live_key'           => 'test_live_key',
					'thankyou_page'      => '0',
					'suppress_email'     => 0,
					'contact_email'      => 'test@example.com',
					'inline_checkout'    => 0,
					'enable_saved_cards' => 0,
					'checkout_theme'     => 'light',
				),
				array( 'id_tw_configuration' => 1 )
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->insert(
				$table_name,
				array(
					'id_tw_configuration' => 1,
					'live_mode'           => 0,
					'staging_id'          => 'test_staging_id',
					'staging_key'         => 'test_staging_secret_key_for_testing_purposes',
					'live_id'             => 'test_live_id',
					'live_key'            => 'test_live_key',
					'thankyou_page'       => '0',
					'suppress_email'      => 0,
					'contact_email'       => 'test@example.com',
					'inline_checkout'     => 0,
					'enable_saved_cards'  => 0,
					'checkout_theme'      => 'light',
				)
			);
		}

		// Create transactions table if not exists.
		$transactions_table = $wpdb->prefix . 'xmoney_payments_transactions';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $transactions_table ) );

		if ( ! $table_exists ) {
			$charset_collate = $wpdb->get_charset_collate();
			$sql             = "CREATE TABLE $transactions_table (
				id_tw_transactions int(10) NOT NULL AUTO_INCREMENT,
				status varchar(50) NOT NULL,
				checkout_url varchar(255) NOT NULL,
				id_cart int(10) NOT NULL,
				identifier varchar(50) NOT NULL,
				orderId int(10) NOT NULL,
				transactionId int(10) NOT NULL,
				customerId int(10) NOT NULL,
				cardId int(10) NOT NULL,
				PRIMARY KEY  (id_tw_transactions)
			) $charset_collate;";

			dbDelta( $sql );
		}
	}

	/**
	 * Clean up test data.
	 */
	protected function cleanup_test_data() {
		global $wpdb;

		// Clear test transactions.
		$transactions_table = $wpdb->prefix . 'xmoney_payments_transactions';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $transactions_table ) );
		if ( $table_exists ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( "TRUNCATE TABLE $transactions_table" );
		}

		// Delete test orders.
		if ( $this->order && method_exists( $this->order, 'get_id' ) ) {
			wp_delete_post( $this->order->get_id(), true );
		}

		// Delete test products.
		if ( $this->product && method_exists( $this->product, 'get_id' ) ) {
			wp_delete_post( $this->product->get_id(), true );
		}

		// Clear cart.
		if ( function_exists( 'WC' ) && WC() && WC()->cart ) {
			WC()->cart->empty_cart();
		}
	}

	/**
	 * Generate a mock encrypted response from xMoney.
	 *
	 * @param array  $data       Response data.
	 * @param string $secret_key Secret key for encryption.
	 * @return string Encrypted response string.
	 */
	protected function generate_encrypted_response( $data, $secret_key = 'test_staging_secret_key_for_testing_purposes' ) {
		$json = wp_json_encode( $data );
		$iv   = openssl_random_pseudo_bytes( 16 );

		$encrypted = openssl_encrypt( $json, 'aes-256-cbc', $secret_key, OPENSSL_RAW_DATA, $iv );

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		return base64_encode( $iv ) . ',' . base64_encode( $encrypted );
	}

	/**
	 * Assert that the gateway is properly initialized.
	 */
	protected function assert_gateway_initialized() {
		if ( ! class_exists( 'Xmoney_Payments_Gateway' ) ) {
			$this->markTestSkipped( 'Gateway class not available - WooCommerce may not be loaded' );
		}

		$this->assertNotNull( $this->gateway, 'Gateway should be initialized' );
		$this->assertInstanceOf( 'Xmoney_Payments_Gateway', $this->gateway );
	}

	/**
	 * Skip test if WooCommerce is not available.
	 */
	protected function skip_if_no_woocommerce() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not available' );
		}
	}
}
