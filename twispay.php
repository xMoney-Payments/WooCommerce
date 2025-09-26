<?php
/**
 * Plugin Name: xMoney Payments
 * Plugin URI: https://wordpress.org/plugins/xmoney-payments/
 * Description: Plugin for xMoney Payments payment gateway.
 * Version: 1.0.0
 * Author: xmoney
 * Author URI: https://www.xmoney.com
 * Text Domain: xmoney-payments
 * Domain Path: /lang/
 * License: GPLv2
 *
 *
 * @package  xMoney Payments
 * @category Core
 * @author   xMoney Payments
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Twispay')) {
    /**
     * Main Twispay Class.
     */
    final class Twispay {
        /**
         * Twispay instance.
         *
         * @private
         * @var    Twispay Instance of class Twispay
         */
        private static $__instance;

        /**
         *
         * @public
         * @var    Twispay_TW_Payment_Confirmation Instance of class Twispay_TW_Payment_Confirmation
         */
        public $payment_confirmation;

        /**
         *
         * @public
         * @var    Twispay_TW_Views Instance of class Twispay_TW_Views
         */
        public $views;

        /**
         * Main Twispay Instance
         *
         * Only one instance of Twispay is loaded
         *
         * @static
         * @return Twispay
         */
        public static function instance() {
            if (!isset(self::$__instance) && !(self::$__instance instanceof Twispay)) {
                self::$__instance = new self();

                self::$__instance->twispay_tw_set_objects();
            }

            return self::$__instance;
        }

        /**
         * Twispay Constructor
         *
         * @public
         * @return void
         */
        public function __construct() {
            $this->twispay_tw_set_constants();

            if (get_option('twispay_tw_installed')) {
                $this->twispay_tw_includes();
            }

            if (is_admin()) {
                require_once TWISPAY_PLUGIN_DIR . 'includes/install.php';
                require_once TWISPAY_PLUGIN_DIR . 'includes/admin/ma-class-menu.php';
            }

            add_filter('query_vars', [ $this, 'twispay_query_vars_filter' ]);

            if (class_exists('Twispay_Main_Processor')) {
                new Twispay_Main_Processor();
            }

            if (class_exists('Twispay_Subscription_Processor')) {
                new Twispay_Subscription_Processor();
            }

            if (class_exists('Twispay_Server_To_Server')) {
                new Twispay_Server_To_Server();
            }
        }

        /**
         * Twispay Constants
         *
         * Set all constants in order to use them later
         *
         * @private
         * @return void
         */
        private function twispay_tw_set_constants() {
            // Set plugin folder
            if (!defined('TWISPAY_PLUGIN_DIR')) {
                define('TWISPAY_PLUGIN_DIR', plugin_dir_path(__FILE__));
            }

            if (!defined('TWISPAY_PLUGIN_URL')) {
                define('TWISPAY_PLUGIN_URL', plugin_dir_url(__FILE__));
            }
        }

        /**
         * Twispay Objects
         *
         * Set all objects in order to use them later
         *
         * @private
         * @return void
         */
        private function twispay_tw_set_objects() {
            if (get_option('twispay_tw_installed')) {
                self::$__instance->payment_confirmation = new Twispay_TW_Payment_Confirmation;
                self::$__instance->views = new Twispay_TW_Views;
            }
        }

        /**
         * Twispay Includes
         *
         * Include required core files used in admin and on the frontend
         *
         * @public
         * @return void
         */
        public function twispay_tw_includes() {
            // Includes all admin required classes
            if (is_admin()) {
                require_once TWISPAY_PLUGIN_DIR . 'includes/admin/configuration/configuration.php';
                require_once TWISPAY_PLUGIN_DIR . 'includes/admin/configuration/requests.php';
                require_once TWISPAY_PLUGIN_DIR . 'includes/admin/transaction/transaction.php';
                require_once TWISPAY_PLUGIN_DIR . 'includes/admin/transaction/requests.php';
                require_once TWISPAY_PLUGIN_DIR . 'includes/admin/transaction-log/transaction-log.php';
                require_once TWISPAY_PLUGIN_DIR . 'includes/admin/admin-requests.php';
            }

            // Includes all non-admin classes
            require_once TWISPAY_PLUGIN_DIR . 'includes/scripts.php';
            require_once TWISPAY_PLUGIN_DIR . 'includes/a-functions.php';
            require_once TWISPAY_PLUGIN_DIR . 'includes/class-tw-shortcodes.php';
            require_once TWISPAY_PLUGIN_DIR . 'includes/class-tw-payment-confirmation.php';
            require_once TWISPAY_PLUGIN_DIR . 'includes/class-tw-views.php';
            require_once TWISPAY_PLUGIN_DIR . 'includes/processors/class-main-processor.php';
            require_once TWISPAY_PLUGIN_DIR . 'includes/processors/class-subscription-processor.php';
            require_once TWISPAY_PLUGIN_DIR . 'includes/class-tw-server-to-server.php';
        }

        public function twispay_query_vars_filter($vars) {
            $vars[] .= 'order_id';
            $vars[] .= 'twispay-ipn';
            return $vars;
        }
    }
}

function twispay_missing_wc_notice() {
    $lang = explode('-', get_bloginfo('language'));
    $lang = $lang[0];

    if (file_exists(plugin_dir_path(__FILE__) . 'lang/' . $lang . '/lang.php')) {
        require(plugin_dir_path(__FILE__) . 'lang/' . $lang . '/lang.php');
    } else {
        require(plugin_dir_path(__FILE__) . 'lang/en/lang.php');
    }
    ?>

      <div class="error notice" style="margin-top: 20px;">
        <p><?php echo esc_html__( 'xMoney Payments requires WooCommerce plugin to work normally. Please activate it or install it from', 'xmoney-payments' ); ?> <a target="_blank" href="https://wordpress.org/plugins/woocommerce/"><?php echo esc_html__( 'here','xmoney-payments' ); ?></a>.</p>
        <div class="clearfix"></div>
      </div>

    <?php
}

/**
 * The main instance of Twispay
 *
 * This function is used like a global variable, but without to
 * declare the global
 *
 * @return Twispay|false
 */
function TW() {
    /*
    The way I check if WC is active is a little hacky, but at least it works.

    I've tried to call this function using actions, but the payment method is missed both
    in admin and checkout page in that case.
    */
    if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        add_action('admin_notices', 'twispay_missing_wc_notice');
        return false;
    }

    return Twispay::instance();
}

TW();
