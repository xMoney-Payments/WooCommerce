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
function twispay_tw_main_action()
{

    if (isset($_POST['tw_general_action'])) {

        /* Nonce check */
        if (
            !isset($_POST['twispay_general_nonce']) ||
            !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['twispay_general_nonce'])),
                'twispay_general_action'
            )
        ) {
            wp_die(
                esc_html__('You do not have permission to access this file.', 'xmoney-payments'),
                esc_html__('Error', 'xmoney-payments'),
                array('response' => 403)
            );
        }

        /* Sanitize the requested action */
        $action = sanitize_key(wp_unslash($_POST['tw_general_action']));

        /**
         * Whitelisted admin actions & their input fields.
         */
        $allowed_fields = array(

            'edit_general_configuration' => array(
                'live_mode' => 'text',
                'staging_site_id' => 'text',
                'staging_private_key' => 'text',
                'live_site_id' => 'text',
                'live_private_key' => 'text',
                'wp_pages' => 'text',
                'suppress_email' => 'text',
                'contact_email_o' => 'text',
            ),

            'refund_payment_transaction' => array(
                'payment_ad' => 'absint',
            ),

            'recurring_order' => array(
                'order_ad' => 'absint',
            ),

            'synchronize_subscriptions' => array(),
        );

        /* Validate the requested action */
        if (!isset($allowed_fields[$action])) {
            wp_die(
                esc_html__('Invalid request.', 'xmoney-payments'),
                esc_html__('Error', 'xmoney-payments'),
                array('response' => 403)
            );
        }

        /**
         * Capability enforcement
         */
        $required_capability = ('edit_general_configuration' === $action)
            ? 'manage_options'
            : 'manage_woocommerce';

        if (!current_user_can($required_capability)) {
            wp_die(
                esc_html__('You do not have permission to perform this action.', 'xmoney-payments'),
                esc_html__('Error', 'xmoney-payments'),
                array('response' => 403)
            );
        }

        /* Ensure admin context */
        if (!is_admin()) {
            wp_die(
                esc_html__('Access is not allowed from this location.', 'xmoney-payments'),
                esc_html__('Error', 'xmoney-payments'),
                array('response' => 403)
            );
        }

        /* Sanitize arguments for the selected action (PHPCS Safe) */
        $args = array();

        foreach ($allowed_fields[$action] as $field => $type) {

            if (isset($_POST[$field])) {

                switch ($type) {

                    case 'absint':
                        $args[$field] = absint(wp_unslash($_POST[$field]));
                        break;

                    case 'text':
                    default:
                        $args[$field] = sanitize_text_field(wp_unslash($_POST[$field]));
                        break;
                }
            }
        }

        /**
         * Direct Routing (No dynamic hook execution)
         */
        switch ($action) {

            case 'edit_general_configuration':
                tw_twispay_p_edit_general_configuration($args);
                break;

            case 'refund_payment_transaction':
                tw_twispay_p_refund_payment_transaction();
                break;

            case 'recurring_order':
                tw_twispay_p_recurring_order($args);
                break;

            case 'synchronize_subscriptions':
                tw_twispay_p_synchronize_subscriptions($args);
                break;
        }
    }
}

add_action('admin_init', 'twispay_tw_main_action');
