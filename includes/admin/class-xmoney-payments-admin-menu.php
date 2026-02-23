<?php
/**
 * Xmoney Payments Admin Menu
 *
 * Setup the admin menus in WordPress Dashboard
 *
 * @package  Xmoney/Admin
 * @category Admin
 * @author   Xmoney Payments
 */

// Exit if the file is accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Security class check
if ( ! class_exists( 'Xmoney_Payments_Admin_Menu' ) ) :

	/**
	 * Dashboard Menus Xmoney_Payments_Admin_Menu Class.
	 */
	class Xmoney_Payments_Admin_Menu {
		/**
		 * Xmoney_Payments_Admin_Menu Constructor
		 *
		 * Will hook the admin menus in tabs
		 *
		 * @public
		 * @return void
		 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_head', array( $this, 'admin_menu_icon_css' ) );
	}

	/**
	 * Output inline CSS to constrain the admin menu icon size.
	 *
	 * @public
	 * @return void
	 */
	public function admin_menu_icon_css() {
		echo '<style>'
			. '#adminmenu .toplevel_page_xmoney-payments .wp-menu-image img{'
			. 'max-width:20px;'
			. 'max-height:20px;'
			. 'padding:7px 0 0;'
			. '}'
			. '</style>';
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
			if ( file_exists( XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/' . $lang . '/lang.php' ) ) {
				require XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/' . $lang . '/lang.php';
			} else {
				require XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/en/lang.php';
			}

		// Add main adminsitrator page
		add_menu_page( __( 'xMoney Payments', 'xmoney-payments' ), esc_attr__( 'xMoney Payments', 'xmoney-payments' ), 'edit_posts', 'xmoney-payments', 'xmoney_payments_configuration', XMONEY_PAYMENTS_PLUGIN_URL . 'assets/images/admin-menu-icon.png', 1000 );

			// Add submenus
			add_submenu_page( 'xmoney-payments', esc_attr__( 'Configuration', 'xmoney-payments' ), esc_attr__( 'Configuration', 'xmoney-payments' ), 'edit_posts', 'xmoney-payments', 'xmoney_payments_configuration' );
			add_submenu_page( 'xmoney-payments', esc_attr__( 'Transaction list', 'xmoney-payments' ), esc_attr__( 'Transaction list', 'xmoney-payments' ), 'edit_posts', 'xmoney-payments-transaction', 'xmoney_payments_transaction_administrator' );
			add_submenu_page( 'xmoney-payments', esc_attr__( 'Transaction log', 'xmoney-payments' ), esc_attr__( 'Transaction log', 'xmoney-payments' ), 'edit_posts', 'xmoney-payments-transaction-log', 'xmoney_payments_transaction_log_administrator' );
		}
	}

endif; // End if class_exists

return new Xmoney_Payments_Admin_Menu();
