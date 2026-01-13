<?php
/**
 * Plugin Name: xMoney Payments
 * Plugin URI: https://wordpress.org/plugins/xmoney-payments/
 * Description: Plugin for xMoney Payments payment gateway.
 * Version: 1.1.0
 * Author: xmoney
 * Author URI: https://www.xmoney.com
 * Text Domain: xmoney-payments
 * Domain Path: /lang/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package  xMoney Payments
 * @category Core
 * @author   xMoney Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Xmoney_Payments' ) ) {
	/**
	 * Main Xmoney_Payments Class.
	 */
	final class Xmoney_Payments {
		/**
		 * Xmoney_Payments instance.
		 *
		 * @private
		 * @var    Xmoney_Payments Instance of class Xmoney_Payments
		 */
		private static $instance;

		/**
		 * Payment confirmation handler instance.
		 *
		 * @var Xmoney_Payments_Payment_Confirmation
		 */
		public $payment_confirmation;

		/**
		 * Views renderer instance.
		 *
		 * @var Xmoney_Payments_Views
		 */
		public $views;

		/**
		 * Main Xmoney_Payments Instance
		 *
		 * Only one instance of Xmoney_Payments is loaded
		 *
		 * @static
		 * @return Xmoney_Payments
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Xmoney_Payments ) ) {
				self::$instance = new self();

				self::$instance->xmoney_payments_set_objects();
			}

			return self::$instance;
		}

		/**
		 * Xmoney_Payments Constructor
		 *
		 * @public
		 * @return void
		 */
		public function __construct() {
			$this->xmoney_payments_set_constants();

			if ( get_option( 'xmoney_payments_installed' ) ) {
				$this->xmoney_payments_includes();
			}

			if ( is_admin() ) {
				require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'includes/install.php';
				require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'includes/admin/class-xmoney-payments-admin-menu.php';
			}

			add_filter( 'query_vars', array( $this, 'xmoney_payments_query_vars_filter' ) );

			if ( class_exists( 'Xmoney_Payments_Main_Processor' ) ) {
				new Xmoney_Payments_Main_Processor();
			}

			if ( class_exists( 'Xmoney_Payments_Subscription_Processor' ) ) {
				new Xmoney_Payments_Subscription_Processor();
			}

			if ( class_exists( 'Xmoney_Payments_Server_To_Server' ) ) {
				new Xmoney_Payments_Server_To_Server();
			}
		}

		/**
		 * Xmoney_Payments Constants
		 *
		 * Set all constants in order to use them later
		 *
		 * @private
		 * @return void
		 */
		private function xmoney_payments_set_constants() {
			// Set plugin folder
			if ( ! defined( 'XMONEY_PAYMENTS_PLUGIN_DIR' ) ) {
				define( 'XMONEY_PAYMENTS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
			}

			if ( ! defined( 'XMONEY_PAYMENTS_PLUGIN_URL' ) ) {
				define( 'XMONEY_PAYMENTS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
			}

			if ( ! defined( 'XMONEY_PAYMENTS_VERSION' ) ) {
				define( 'XMONEY_PAYMENTS_VERSION', 1.0 );
			}
		}

		/**
		 * Xmoney Payments Objects
		 *
		 * Set all objects in order to use them later
		 *
		 * @private
		 * @return void
		 */
		private function xmoney_payments_set_objects() {
			if ( get_option( 'xmoney_payments_installed' ) ) {
				self::$instance->payment_confirmation = new Xmoney_Payments_Payment_Confirmation();
				self::$instance->views                = new Xmoney_Payments_Views();
			}
		}

		/**
		 * Xmoney_Payments Includes
		 *
		 * Include required core files used in admin and on the frontend
		 *
		 * @public
		 * @return void
		 */
		public function xmoney_payments_includes() {
			// Includes all admin required classes
			if ( is_admin() ) {
				require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'includes/admin/configuration/configuration.php';
				require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'includes/admin/configuration/requests.php';
				require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'includes/admin/transaction/transaction.php';
				require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'includes/admin/transaction/requests.php';
				require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'includes/admin/transaction-log/transaction-log.php';
				require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'includes/admin/admin-requests.php';
			}

			// Includes all non-admin classes
			require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'includes/class-xmoney-payments-gateway.php';
			require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'includes/a-functions.php';
			require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'includes/class-xmoney-payments-shortcodes.php';
			require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'includes/class-xmoney-payments-payment-confirmation.php';
			require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'includes/class-xmoney-payments-views.php';
			require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'includes/processors/class-xmoney-payments-main-processor.php';
			require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'includes/processors/class-xmoney-payments-subscription-processor.php';
			require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'includes/class-xmoney-payments-server-to-server.php';
			require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'includes/class-xmoney-payments-inline-rest.php';
		}

		/**
		 * Register additional public query variables.
		 *
		 * @param array $vars WP query vars.
		 * @return array Modified vars.
		 */
		public function xmoney_payments_query_vars_filter( $vars ) {
			$vars[] .= 'order_id';
			$vars[] .= 'twispay-ipn';
			return $vars;
		}
	}
}

/**
 * Display admin notice when WooCommerce is missing.
 *
 * @return void
 */
function xmoney_payments_missing_wc_notice() {
	$lang = explode( '-', get_bloginfo( 'language' ) );
	$lang = $lang[0];

	if ( file_exists( plugin_dir_path( __FILE__ ) . 'lang/' . $lang . '/lang.php' ) ) {
		require plugin_dir_path( __FILE__ ) . 'lang/' . $lang . '/lang.php';
	} else {
		require plugin_dir_path( __FILE__ ) . 'lang/en/lang.php';
	}
	?>

		<div class="error notice" style="margin-top: 20px;">
		<p><?php echo esc_html__( 'xMoney Payments requires WooCommerce plugin to work normally. Please activate it or install it from', 'xmoney-payments' ); ?> <a target="_blank" href="https://wordpress.org/plugins/woocommerce/"><?php echo esc_html__( 'here', 'xmoney-payments' ); ?></a>.</p>
		<div class="clearfix"></div>
		</div>

	<?php
}

/**
 * The main instance of Xmoney_Payments
 *
 * This function is used like a global variable, but without to
 * declare the global
 *
 * @return Xmoney_Payments|false
 */
function xmoney_payments_instance() {
	// Check if WooCommerce is active.
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Using core WordPress filter 'active_plugins'.
	if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
		add_action( 'admin_notices', 'xmoney_payments_missing_wc_notice' );
		return false;
	}

	return Xmoney_Payments::instance();
}

xmoney_payments_instance();
