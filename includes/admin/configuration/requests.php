<?php
/**
 * Twispay Configuration Request Page
 *
 * Here is processed all configuration actions( edit )
 *
 * @package Twispay_Payment_Gateway
 * @category Admin
 * @author   Twispay
 */

/* Exit if the file is accessed directly. */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Twispay Edit Configuration
 *
 * Process the Edit Configuration to database
 *
 * @param array $request {
 *     Array with all arguments required for editing Configuration in database
 *
 *     @type String $live_mode                               Value '1' if the payment gateway is in Production Mode or value '0' if it is in Staging Mode
 *     @type String $staging_site_id                         The Site ID for Staging Mode
 *     @type String $staging_private_key                     The Private Key for Staging Mode
 *     @type String $live_site_id                            The Site ID for Live Mode
 *     @type String $live_private_key                        The Private Key for Live Mode
 *     @type String $thankyou_page                           The Path for Thank you page. If 0, then it is the default page
 * }
 * @public
 * @return void
 */
function tw_twispay_p_edit_general_configuration( $request ) {
    $live_mode              = sanitize_text_field(wp_unslash($request['live_mode'] ));
    $staging_site_id        = sanitize_text_field(wp_unslash($request['staging_site_id'] ));
    $staging_private_key    = sanitize_text_field(wp_unslash($request['staging_private_key']));
    $live_site_id           = sanitize_text_field(wp_unslash($request['live_site_id']));
    $live_private_key       = sanitize_text_field(wp_unslash($request['live_private_key']));
    $thankyou_page          = sanitize_text_field(wp_unslash($request['wp_pages']));
    $suppress_email         = sanitize_text_field(wp_unslash($request['suppress_email']));
    $contact_email_o        = sanitize_email(wp_unslash($request['contact_email_o']));

    if ( $contact_email_o === '' ) {
        $contact_email_o = 0;
    }

    // Wordpress database refference
    global $wpdb;
    $table_name = esc_sql($wpdb->prefix . 'twispay_tw_configuration');

    // Check if the Configuration row exist into Database
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are escaped manually and safe.
    $configuration = $wpdb->get_results("SELECT * FROM {$table_name}");

    if ( $configuration ) {
        // Edit the Configuration into Database ( twispay_tw_configuration table )
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->update(
            $table_name,
            array(
                'live_mode'       => $live_mode,
                'staging_id'      => $staging_site_id,
                'staging_key'     => $staging_private_key,
                'live_id'         => $live_site_id,
                'live_key'        => $live_private_key,
                'thankyou_page'   => $thankyou_page,
                'suppress_email'  => $suppress_email,
                'contact_email'   => $contact_email_o
            ),
            array(
                'id_tw_configuration'  => $configuration[0]->id_tw_configuration
            )
        );
    }
    else {
        // If by any chance the configuration row does not exist, add default one immediately. ( twispay_tw_configuration table )
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->insert(
            $table_name,
            array(
                'live_mode'     => 0
            )
        );

        // Edit the Configuration into Database ( twispay_tw_configuration table )
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->update(
            $table_name,
            array(
                'live_mode'       => $live_mode,
                'staging_id'      => $staging_site_id,
                'staging_key'     => $staging_private_key,
                'live_id'         => $live_site_id,
                'live_key'        => $live_private_key,
                'thankyou_page'   => $thankyou_page,
                'suppress_email'  => $suppress_email,
                'contact_email'   => $contact_email_o
            ),
            array(
                'id_tw_configuration'  => $wpdb->insert_id
            )
        );
    }

    // Redirect to the Configuration Page
    $redirect_url = add_query_arg(
        array(
            'notice' => 'edit_configuration',
            'twispay_notice_nonce' => wp_create_nonce('twispay_notice_action')
        ),
        admin_url('admin.php?page=xmoney-payments')
    );

    wp_safe_redirect($redirect_url);
    exit;
}
add_action( 'tw_edit_general_configuration', 'tw_twispay_p_edit_general_configuration' );
