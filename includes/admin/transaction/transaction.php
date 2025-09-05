<?php
/**
 * Twispay Transaction List Admin Page
 *
 * Twispay transaction list page on the Administrator dashboard
 *
 * @package  Twispay/Admin
 * @category Admin
 * @author   Twispay
 */

// Exit if the file is accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Import the table class
require_once TWISPAY_PLUGIN_DIR . 'includes/admin/transaction/transaction-table.php';

function twispay_tw_transaction_administrator() {
    /* Load languages */
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
    }
    else {
        // Check if the view / edit / delete action is detected, otherwise load the campaigns form
        if ( isset( $_GET['action'] ) && sanitize_text_field( wp_unslash($_GET['action']) ) ) {
            $action = sanitize_text_field( wp_unslash($_GET['action']) );

            switch ( $action ) {
                case 'refund_payment':
                    include TWISPAY_PLUGIN_DIR . 'includes/admin/transaction/refund_t.php';
                    break;
                case 'recurring_payment':
                    include TWISPAY_PLUGIN_DIR . 'includes/admin/transaction/recurring_t.php';
                    break;
            }
        }
        else {
            ?>
                <div class="wrap">
                    <h1><?php echo esc_html__( 'Transaction list','xmoney-payments' ); ?></h1>

                    <?php if( class_exists('WC_Subscriptions') ){ ?>
                        <form method="post" id="synchronize_subscriptions">
                            <table class="form-table">
                                <tr class="form-field" id="contact_email_o">
                                    <th scope="row"><label><?php echo esc_html__('Synchronize subscriptions', 'xmoney-payments'); ?></span></label></th>
                                    <td>
                                        <input type="hidden" name="tw_general_action" value="synchronize_subscriptions" />
                                        <?php submit_button( esc_attr__('Synchronize the local status of all subscriptions with the server status.', 'xmoney-payments' ), 'primary', 'createuser', true, array( 'id' => 'synchronizesubscriptions' ) ); ?>
                                        <p class="description"><?php echo esc_html__( 'Synchronize the local status of all subscriptions with the server status.', 'xmoney-payments' ); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </form>
                    <?php } ?>


                    <?php
                        if ( isset( $_GET['notice'] ) && sanitize_text_field( wp_unslash($_GET['notice']) ) ) {
                            $notice = sanitize_text_field( wp_unslash($_GET['notice']) );

                            switch ( $notice ) {
                                case 'error_refund':
                                    ?>
                                        <div class="error notice">
                                            <p><?php echo esc_html__( 'Refund could not been processed.','xmoney-payments' ); ?></p>
                                        </div>
                                    <?php
                                    break;
                                case 'error_recurring':
                                    ?>
                                        <div class="error notice">
                                            <p><?php echo esc_html__( 'Recurring could not been processed.','xmoney-payments' ); ?></p>
                                        </div>
                                    <?php
                                    break;
                                case 'success_refund':
                                    ?>
                                        <div class="updated notice">
                                            <p><?php echo esc_html__( 'Refund processed successfully. Refresh the page in seconds to see the update.','xmoney-payments' ); ?></p>
                                        </div>
                                    <?php
                                    break;
                                case 'success_recurring':
                                    ?>
                                        <div class="updated notice">
                                            <p><?php echo esc_html__( 'Recurring processed successfully.','xmoney-payments' ); ?></p>
                                        </div>
                                    <?php
                                    break;
                                case 'sync_finished':
                                    ?>
                                        <div class="updated notice">
                                            <p><?php echo esc_html__( 'Subscriptions synchronization finished.','xmoney-payments' ); ?></p>
                                        </div>
                                    <?php
                                    break;
                                case 'errorp_refund':
                                    ?>
                                        <div class="error notice">
                                            <p><?php echo (isset($_GET['emessage']) ? esc_html(sanitize_text_field(wp_unslash( $_GET['emessage'] )) ) : ''); ?></p>
                                        </div>
                                    <?php
                                    break;
                            }
                        }

                        // Create the Payment Methods object and build the Table
                        $transaction_table = new Twispay_TransactionTable( $tw_lang );
                        $transaction_table->views();
                    ?>

                    <form method="get">
                        <input type="hidden" name="page" value="<?php echo (isset($_REQUEST['page']) ? esc_attr(sanitize_text_field(wp_unslash($_REQUEST['page'])) ) : '') ?>" />
                        <?php $transaction_table->search_box( esc_html__( 'Search Order','xmoney-payments' ), 'search-query' ); ?>
                    </form>
                    <form method="post">
                        <?php
                            $transaction_table->prepare_items();
                            $transaction_table->display();
                        ?>
                    </form>
                </div>
            <?php
        }
    }
}
