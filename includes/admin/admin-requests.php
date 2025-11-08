<?php
/**
 * Twispay Main Request Page
 *
 * Here is processed all actions. They will be sent later to their controllers
 *
 * @package  Twispay/Admin
 * @category Admin
 * @author   Twispay
 */
/* Exit if the file is accessed directly. */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hooks every Twispay actions and process them in order to be
 * sent later to their own controllers.
 *
 * @public
 * @return void
 */
function twispay_tw_main_action() {
    // Only process admin actions on POST to avoid unintended GET triggers
    if ( isset( $_POST['tw_general_action'] ) ) {
        if (!isset($_POST['twispay_general_nonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['twispay_general_nonce'])), 'twispay_general_action')) {

            wp_die(esc_attr_e('You do not have permission to access this file', 'xmoney-payments'), esc_attr_e('Error', 'xmoney-payments'), array('response' => 403));
        }

        /* Sanitize the requested action slug (underscores / alphanumerics only). */
        $action_slug = sanitize_key( wp_unslash( $_POST['tw_general_action'] ) );

        /* Whitelist of allowed admin actions mapped to their hook names. */
        $allowed_actions = array(
            'edit_general_configuration' => 'tw_edit_general_configuration',
            'refund_payment_transaction' => 'tw_refund_payment_transaction',
            'recurring_order'            => 'tw_recurring_order',
            'synchronize_subscriptions'  => 'tw_synchronize_subscriptions',
        );

        /* Reject anything not explicitly whitelisted. */
        if ( ! isset( $allowed_actions[ $action_slug ] ) ) {
            wp_die(
                esc_html__( 'Invalid action requested.', 'xmoney-payments' ),
                esc_html__( 'Error', 'xmoney-payments' ),
                array( 'response' => 400 )
            );
        }

        // Check if current user have administrator permissions (prefer manage_options for configuration, manage_woocommerce otherwise)
        $needs_manage_options    = ( 'edit_general_configuration' === $action_slug );
        $required_capability     = $needs_manage_options ? 'manage_options' : 'manage_woocommerce';
        if ( ! current_user_can( $required_capability ) ) {
            wp_die(
                esc_html__( 'You do not have permission to access this file', 'xmoney-payments' ),
                esc_html__( 'Error', 'xmoney-payments' ),
                array( 'response' => 403 )
            );
        }

        // Ensure request executed only within admin context
        if ( ! is_admin() ) {
            wp_die(
                esc_html__( 'You do not have permission to access the file from here', 'xmoney-payments' ),
                esc_html__( 'Error', 'xmoney-payments' ),
                array( 'response' => 403 )
            );
        }

        /* Build sanitized argument array ONLY for the action that expects parameters. */
        $hook_name   = $allowed_actions[ $action_slug ];
        $action_args = null; // null => no args; array => pass as first arg

        if ( 'tw_edit_general_configuration' === $hook_name ) {
            // Collect and sanitize expected configuration fields. Missing ones become empty strings.
            $action_args = array(
                'live_mode'           => isset( $_POST['live_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['live_mode'] ) ) : '',
                'staging_site_id'     => isset( $_POST['staging_site_id'] ) ? sanitize_text_field( wp_unslash( $_POST['staging_site_id'] ) ) : '',
                'staging_private_key' => isset( $_POST['staging_private_key'] ) ? sanitize_text_field( wp_unslash( $_POST['staging_private_key'] ) ) : '',
                'live_site_id'        => isset( $_POST['live_site_id'] ) ? sanitize_text_field( wp_unslash( $_POST['live_site_id'] ) ) : '',
                'live_private_key'    => isset( $_POST['live_private_key'] ) ? sanitize_text_field( wp_unslash( $_POST['live_private_key'] ) ) : '',
                'wp_pages'            => isset( $_POST['wp_pages'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_pages'] ) ) : '',
                'suppress_email'      => isset( $_POST['suppress_email'] ) ? sanitize_text_field( wp_unslash( $_POST['suppress_email'] ) ) : '',
                'contact_email_o'     => isset( $_POST['contact_email_o'] ) ? sanitize_email( wp_unslash( $_POST['contact_email_o'] ) ) : '',
            );
        } elseif ( 'tw_recurring_order' === $hook_name || 'tw_synchronize_subscriptions' === $hook_name ) {
            // These callbacks are defined with one parameter but don't use it; pass an empty array to satisfy signature.
            $action_args = array();
        }

        /* Fire the whitelisted hook with sanitized arguments (or none). */
        if ( null === $action_args ) {
            do_action( $hook_name );
        } else {
            do_action( $hook_name, $action_args );
        }
    }
}
add_action( 'init', 'twispay_tw_main_action' );
