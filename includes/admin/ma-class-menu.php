<?php
/**
 * Twispay Admin Menu
 *
 * Setup the admin menus in Wordpress Dashboard
 *
 * @package  Twispay/Admin
 * @category Admin
 * @author   Twispay
 */

// Exit if the file is accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Security class check
if ( ! class_exists( 'Twispay_Tw_Admin_Menu' ) ) :

/**
 * Dashboard Menus Twispay_Tw_Admin_Menu Class.
 */
class Twispay_Tw_Admin_Menu {
    /**
     * Twispay_Tw_Admin_Menu Constructor
     *
     * Will hook the admin menus in tabs
     *
     * @public
     * @return void
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
    }

    /**
     * Function that will add the menus items, as well the submenus
     *
     * @public
     * @return void
     */
    public function admin_menu() {
        // Load languages
        $lang = explode( '-', get_bloginfo( 'language' ) );
        $lang = $lang[0];
        if ( file_exists( TWISPAY_PLUGIN_DIR . 'lang/' . $lang . '/lang.php' ) ) {
            require( TWISPAY_PLUGIN_DIR . 'lang/' . $lang . '/lang.php' );
        } else {
            require( TWISPAY_PLUGIN_DIR . 'lang/en/lang.php' );
        }

        // Add main adminsitrator page
        add_menu_page( __( 'xMoney Payments', 'xmoney-payments' ), esc_attr__( 'xMoney Payments', 'xmoney-payments' ), 'administrator', 'xmoney-payments', 'twispay_tw_configuration', 'dashicons-editor-paste-text', 1000 );

        // Add submenus
        add_submenu_page( 'xmoney-payments', esc_attr__('Configuration', 'xmoney-payments' ), esc_attr__( 'Configuration', 'xmoney-payments' ), 'administrator', 'xmoney-payments', 'twispay_tw_configuration' );
        add_submenu_page( 'xmoney-payments', esc_attr__( 'Transaction list', 'xmoney-payments' ), esc_attr__( 'Transaction list', 'xmoney-payments' ), 'administrator', 'tw-transaction', 'twispay_tw_transaction_administrator' );
        add_submenu_page( 'xmoney-payments', esc_attr__( 'Transaction log', 'xmoney-payments' ), esc_attr__( 'Transaction log', 'xmoney-payments' ), 'administrator', 'tw-transaction-log', 'twispay_tw_transaction_log_administrator' );
    }
}

endif; // End if class_exists

return new Twispay_Tw_Admin_Menu();
