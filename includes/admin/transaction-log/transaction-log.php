<?php
/**
 * Twispay Transaction Log Admin Page
 *
 * Twispay transaction log page on the Administrator dashboard
 *
 * @package  Twispay/Admin
 * @category Admin
 * @author   Twispay
 */

// Exit if the file is accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function twispay_tw_transaction_log_administrator() {
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
    }
    else {
        ?>
            <div class="wrap">
                <h1><?php echo esc_html__( 'Transaction log','xmoney-payments' ); ?></h1>
                <p><?php echo esc_html__( 'Transaction log in raw form.','xmoney-payments' ); ?></p>
                <?php
                    if ( file_exists( TWISPAY_PLUGIN_DIR . 'twispay-log.txt' ) ) {
                        echo '<textarea readonly style="width: 900px; height: 386px; margin-top: 10px;">' . wp_kses( file_get_contents( TWISPAY_PLUGIN_DIR . 'twispay-log.txt' ), wp_kses_allowed_html( 'strip' ) ) . '</textarea>';
                    } else {
                        echo '<p>' . esc_html__( 'No log recorded yet.','xmoney-payments' ) . '</p>';
                    }
                ?>
            </div>
        <?php
    }
}
