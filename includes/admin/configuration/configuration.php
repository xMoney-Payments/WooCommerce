<?php
/**
 * Twispay Configuration Admin Page
 *
 * Twispay general configuration page on the Administrator dashboard
 *
 * @package  Twispay/Admin
 * @category Admin
 * @author   Twispay
 */

// Exit if the file is accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function twispay_tw_configuration() {
    // Load languages
    $lang = explode( '-', get_bloginfo( 'language' ) );
    $lang = $lang[0];
    if ( file_exists( TWISPAY_PLUGIN_DIR . 'lang/' . $lang . '/lang.php' ) ) {
        require( TWISPAY_PLUGIN_DIR . 'lang/' . $lang . '/lang.php' );
    } else {
        require( TWISPAY_PLUGIN_DIR . 'lang/en/lang.php' );
    }

    if ( ! class_exists( 'WooCommerce' ) ) {
        ?>
            <div class="error notice" style="margin-top: 20px;">
                <p><?php echo esc_html__( 'xMoney Payments requires WooCommerce plugin to work normally. Please activate it or install it from', 'xmoney-payments' ); ?> <a target="_blank" href="https://wordpress.org/plugins/woocommerce/"><?php echo esc_html__( 'here','xmoney-payments' ); ?></a>.</p>
                <div class="clearfix"></div>
            </div>
        <?php
    } else {
        ?>
            <div class="wrap">
                <h2><?php echo esc_html__( 'Configuration', 'xmoney-payments' ); ?></h2>
                <?php
                    if ( isset( $_GET['notice'] ) && sanitize_text_field( wp_unslash($_GET['notice']) ) ) {
                        $notice = sanitize_text_field( wp_unslash($_GET['notice']) );

                        switch ( $notice ) {
                            case 'edit_configuration':
                                ?>
                                    <div class="updated notice">
                                        <p><?php echo esc_html__( 'Configuration has been edited successfully.', 'xmoney-payments' ); ?></p>
                                    </div>
                                <?php
                            break;
                        }
                    }
                ?>

                <p><?php echo esc_html__( 'xMoney Payments general settings.', 'xmoney-payments' ); ?></p>
                <form method="post" id="general_configuration">
                    <table class="form-table">
                        <tr class="form-field form-required">
                            <th scope="row"><label for="live_mode"><?php echo esc_html__( 'Live mode', 'xmoney-payments' ); ?></label></th>
                            <td>
                                <?php echo wp_kses(twispay_tw_get_live_mode( $tw_lang ),twispay_allowed_tags()); ?>
                                <p class="description"><?php echo esc_html_e( 'Select "Yes" if you want to use the payment gateway in Production Mode or "No" if you want to use it in Staging Mode.', 'xmoney-payments' ); ?></p>
                            </td>
                        </tr>
                        <tr class="form-field" id="staging_site_id">
                            <th scope="row"><label for="staging_site_id"><?php echo esc_html__('Staging Site ID','xmoney-payments'); ?></span></label></th>
                            <td>
                                <input name="staging_site_id" type="text" value="<?php echo esc_attr( twispay_tw_get_staging_site_id() ); ?>" style="max-width: 400px;" />
                                <p class="description"><?php echo esc_html__( 'Enter the Site ID for Staging Mode. You can get one from', 'xmoney-payments' ); ?> <a target="_blank" href="https://merchant-stage.xmoney.com/"><?php echo esc_html__( 'here','xmoney-payments' ); ?></a>.</p>
                            </td>
                        </tr>
                        <tr class="form-field" id="staging_private_key">
                            <th scope="row"><label for="staging_private_key"><?php echo esc_html__( 'Staging Private Key', 'xmoney-payments' ); ?></span></label></th>
                            <td>
                                <input name="staging_private_key" type="text" value="<?php echo esc_attr( twispay_tw_get_staging_private_key() ); ?>" style="max-width: 400px;" />
                                <p class="description"><?php echo esc_html__( 'Enter the Private Key for Staging Mode. You can get one from', 'xmoney-payments' ); ?> <a target="_blank" href="https://merchant-stage.xmoney.com/"><?php echo esc_html__( 'here','xmoney-payments' ); ?></a>.</p>
                            </td>
                        </tr>
                        <tr class="form-field" id="live_site_id">
                            <th scope="row"><label for="live_site_id"><?php echo esc_html__( 'Live Site ID','xmoney-payments' ); ?></span></label></th>
                            <td>
                                <input name="live_site_id" type="text" value="<?php echo esc_attr( twispay_tw_get_live_site_id() ); ?>" style="max-width: 400px;" />
                                <p class="description"><?php echo esc_html__( 'Enter the Site ID for Live Mode. You can get one from','xmoney-payments' ); ?> <a target="_blank" href="https://merchant.xmoney.com/"><?php echo esc_html__( 'here','xmoney-payments' ); ?></a>.</p>
                            </td>
                        </tr>
                        <tr class="form-field" id="live_private_key">
                            <th scope="row"><label for="live_private_key"><?php echo esc_html__( 'Live Private Key','xmoney-payments' ); ?></span></label></th>
                            <td>
                                <input name="live_private_key" type="text" value="<?php echo esc_attr( twispay_tw_get_live_private_key() ); ?>" style="max-width: 400px;" />
                                <p class="description"><?php echo esc_html__( 'Enter the Private Key for Live Mode. You can get one from','xmoney-payments' ); ?> <a target="_blank" href="https://merchant.xmoney.com/"><?php echo esc_html__( 'here','xmoney-payments' ); ?></a>.</p>
                            </td>
                        </tr>
                        <tr class="form-field" id="s_t_s_notification">
                            <th scope="row"><label for="s_t_s_notification"><?php echo esc_html__( 'Server-to-server notification URL','xmoney-payments' ); ?></span></label></th>
                            <td>
                                <input name="s_t_s_notification" disabled="disabled" type="text" value="<?php echo esc_url(home_url($path = '?twispay-ipn')); ?>" style="max-width: 400px;" />
                                <p class="description"><?php echo esc_html__( 'Put this URL in your xMoney Payments account.','xmoney-payments' ); ?></p>
                            </td>
                        </tr>
                        <tr class="form-field" id="r_custom_thankyou">
                            <th scope="row"><label for="r_custom_thankyou"><?php echo esc_html__( 'Redirect to custom Thank you page','xmoney-payments' ); ?></span></label></th>
                            <td>
                                <?php echo wp_kses(twispay_tw_get_wp_pages( $tw_lang ), twispay_allowed_tags()); ?>
                                <p class="description"><?php echo esc_html__( 'If you want to display custom Thank you page, set it up here. You can create new custom page from','xmoney-payments' ); ?> <a href="<?php echo esc_url_raw(get_admin_url() . 'post-new.php?post_type=page'); ?>"><?php echo esc_html__( 'here','xmoney-payments' ); ?></a>.</p>
                            </td>
                        </tr>
                        <tr class="form-field" id="suppress_email">
                            <th scope="row"><label for="suppress_email"><?php echo esc_html__( 'Suppress default WooCommerce payment receipt emails','xmoney-payments' ); ?></span></label></th>
                            <td>
                                <?php echo wp_kses(twispay_tw_get_suppress_email( $tw_lang ), twispay_allowed_tags()); ?>
                                <p class="description"><?php echo esc_html__( 'Option to suppress the communication sent by the ecommerce system, in order to configure it from xMoney Payments’s Merchant interface.','xmoney-payments' ); ?></p>
                            </td>
                        </tr>
                        <tr class="form-field" id="contact_email_o">
                            <th scope="row"><label for="contact_email_o"><?php echo esc_html__( 'Contact email(Optional)','xmoney-payments' ); ?></span></label></th>
                            <td>
                                <input name="contact_email_o" type="text" value="<?php echo esc_url(sanitize_email( twispay_tw_get_contact_email_o() == '0' ? '' : twispay_tw_get_contact_email_o() )); ?>" style="max-width: 400px;" />
                                <p class="description"><?php echo esc_html__( 'This email will be used on the payment error page.','xmoney-payments' ); ?></p>
                            </td>
                        </tr>
                        <tr class="form-field" id="contact_email_o">
                            <th scope="row">
                                <input type="hidden" name="tw_general_action" value="edit_general_configuration" />
                                <?php submit_button( esc_attr__( 'Save changes', 'xmoney-payments' ), 'primary', 'edituser', true, array( 'id' => 'ceditusersub' ) ); ?>
                            </th>
                            <td></td>
                        </tr>
                    </table>
                </form>
            </div>
        <?php
    }
}
