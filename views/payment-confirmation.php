<?php
/**
 * Twispay Payment Confirmation View
 *
 * Html Payment Confirmation View
 *
 * @package  Twispay/Front
 * @category Front
 * @author   Twispay
 */

/* Load languages */
$lang = explode( '-', get_bloginfo( 'language' ) )[0];
if ( file_exists( TWISPAY_PLUGIN_DIR . 'lang/' . $lang . '/lang.php' ) ){
    require( TWISPAY_PLUGIN_DIR . 'lang/' . $lang . '/lang.php' );
} else {
    require( TWISPAY_PLUGIN_DIR . 'lang/en/lang.php' );
}

/* Require the "Twispay_TW_Logger" class. */
require_once( TWISPAY_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'Twispay_TW_Logger.php' );
/* Require the "Twispay_TW_Helper_Response" class. */
require_once( TWISPAY_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'Twispay_TW_Helper_Response.php' );
/* Require the "Twispay_TW_Status_Updater" class. */
require_once( TWISPAY_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'Twispay_TW_Status_Updater.php' );
/* Require the "Twispay_TW_Default_Thankyou" class. */
require_once( TWISPAY_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'Twispay_TW_Default_Thankyou.php' );


/* Validate if 'WooCommerce' is NOT installed. */
if ( !class_exists('WooCommerce') ) {
    ?>
    <div class="error notice" style="margin-top: 20px;">
        <h3><?php echo esc_html__( 'An error occurred:','xmoney-payments' ); ?></h3>

        <p>
            <?php echo esc_html__('xMoney Payments requires WooCommerce plugin to work normally. Please activate it or install it from', 'xmoney-payments'); ?>

            <a target="_blank"
               rel="noreferrer noopener"
               href="https://wordpress.org/plugins/woocommerce/">
                <?php echo esc_html__('here','xmoney-payments'); ?>
            </a>
        </p>
    </div>

    <?php

    die();
}


/* Get configuration from database. */
global $wpdb;
$configuration = $wpdb->get_row( "SELECT * FROM " . $wpdb->prefix . "twispay_tw_configuration" );


$secretKey = '';
if ( $configuration ) {
    if ( 1 == $configuration->live_mode ) {
        $secretKey = $configuration->live_key;
    } else if ( 0 == $configuration->live_mode ) {
        $secretKey = $configuration->staging_key;
    } else {
        echo '<style>.loader {display: none;}</style>';
        die( esc_html__( 'Missing configuration for plugin.', 'xmoney-payments' ) );
    }
}


/* Check if the POST is corrupted: Doesn't contain the 'opensslResult' and the 'result' fields. */
                                          /* OR */
/* Check if the 'backUrl' is corrupted: Doesn't contain the 'secure_key' field. */
if (((FALSE == isset($_POST['opensslResult'])) && (FALSE == isset($_POST['result']))) || (FALSE == isset($_GET['secure_key']))) {
    Twispay_TW_Logger::twispay_tw_log(esc_html__('[RESPONSE-ERROR]: Received empty response.', 'xmoney-payments'));
    ?>
    <div class="error notice" style="margin-top: 20px;">
        <h3><?php echo esc_html__( 'An error occurred:','xmoney-payments' ); ?></h3>

        <p>
            <?php echo esc_html__( 'The payment could not be processed. Please','xmoney-payments' ); ?>

            <a href="<?php echo esc_url(wc_get_cart_url()); ?>">
                <?php echo esc_html__( ' try again','xmoney-payments' ); ?>
            </a>

            <?php if ('0' == $configuration->contact_email) { ?>
                <?php
                printf(
                    '%s %s %s',
                    esc_html__( ' or','xmoney-payments' ),
                    esc_html__( ' contact','xmoney-payments' ),
                    esc_html__( ' the website administrator.','xmoney-payments' )
                );
                ?>
            <?php } else { ?>
                <?php echo esc_html__( ' or','xmoney-payments' ); ?>

                <a href="<?php echo esc_url('mailto:' . sanitize_email($configuration->contact_email)); ?>">
                    <?php echo esc_html__( ' contact','xmoney-payments' ); ?>
                </a>

                <?php echo esc_html__( ' the website administrator.','xmoney-payments' ); ?>
            <?php } ?>
        </p>
    </div>
    <?php

    die();
}

/* Check if there is NO secret key. */
if ('' == $secretKey) {
    Twispay_TW_Logger::twispay_tw_log(esc_html__('[RESPONSE-ERROR]: Private key is not valid.', 'xmoney-payments'));
    ?>
    <div class="error notice" style="margin-top: 20px;">
        <h3><?php echo esc_html__( 'An error occurred:','xmoney-payments' ); ?></h3>

        <span><?php echo esc_html__( ' Private key is not valid.','xmoney-payments' ); ?></span>

        <p>
            <?php echo esc_html__( 'The payment could not be processed. Please','xmoney-payments' ); ?>

            <a href="<?php echo esc_url(wc_get_cart_url()); ?>">
                <?php echo esc_html__( ' try again','xmoney-payments' ); ?>
            </a>

            <?php if ('0' == $configuration->contact_email) { ?>
                <?php
                printf(
                    '%s %s %s',
                    esc_html__( ' or','xmoney-payments' ),
                    esc_html__( ' contact','xmoney-payments' ),
                    esc_html__( ' the website administrator.','xmoney-payments' )
                );
                ?>
            <?php } else { ?>
                <?php echo esc_html__( ' or','xmoney-payments' ); ?>

                <a href="<?php echo esc_url('mailto:' . sanitize_email($configuration->contact_email)); ?>">
                    <?php echo esc_html__( ' contact','xmoney-payments' ); ?>
                </a>

                <?php echo esc_html__( ' the website administrator.','xmoney-payments' ); ?>
            <?php } ?>
        </p>
    </div>
    <?php

    die();
}


/* Extract the server response and decrypt it. */
$decrypted = Twispay_TW_Helper_Response::twispay_tw_decrypt_message(/*tw_encryptedResponse*/(isset($_POST['opensslResult'])) ? (esc_html(sanitize_text_field(wp_unslash($_POST['opensslResult'])))) : (esc_html(sanitize_text_field(wp_unslash($_POST['result'])))), $secretKey, $tw_lang);

/* Check if decryption failed.  */
if (FALSE === $decrypted) {
    Twispay_TW_Logger::twispay_tw_log(esc_html__('[RESPONSE-ERROR]: Decryption failed.', 'xmoney-payments'));
    ?>
    <div class="error notice" style="margin-top: 20px;">
        <h3><?php echo esc_html__( 'An error occurred:','xmoney-payments' ); ?></h3>

        <p>
            <?php echo esc_html__( 'The payment could not be processed. Please','xmoney-payments' ); ?>

            <a href="<?php echo esc_url(wc_get_cart_url()); ?>">
                <?php echo esc_html__( ' try again','xmoney-payments' ); ?>
            </a>

            <?php if ('0' == $configuration->contact_email) { ?>
                <?php
                printf(
                    '%s %s %s',
                    esc_html__( ' or','xmoney-payments' ),
                    esc_html__( ' contact','xmoney-payments' ),
                    esc_html__( ' the website administrator.','xmoney-payments' )
                );
                ?>
            <?php } else { ?>
                <?php echo esc_html__( ' or','xmoney-payments' ); ?>

                <a href="<?php echo esc_url('mailto:' . sanitize_email($configuration->contact_email)); ?>">
                    <?php echo esc_html__( ' contact','xmoney-payments' ); ?>
                </a>

                <?php echo esc_html__( ' the website administrator.','xmoney-payments' ); ?>
            <?php } ?>
        </p>
    </div>
    <?php

    die();
} else {
    Twispay_TW_Logger::twispay_tw_log(esc_html__('[RESPONSE]: Decryption successfully performed.', 'xmoney-payments'));
}

/* Validate the decrypted response. */
$orderValidation = Twispay_TW_Helper_Response::twispay_tw_checkValidation($decrypted, $tw_lang);

/* Check if server response validation failed.  */
if (TRUE !== $orderValidation) {
    Twispay_TW_Logger::twispay_tw_log(esc_html__('[RESPONSE-ERROR]: Validation failed.', 'xmoney-payments'));
    ?>
    <div class="error notice" style="margin-top: 20px;">
        <h3><?php echo esc_html__( 'An error occurred:','xmoney-payments' ); ?></h3>

        <p>
            <?php echo esc_html__( 'The payment could not be processed. Please','xmoney-payments' ); ?>

            <a href="<?php echo esc_url(wc_get_cart_url()); ?>">
                <?php echo esc_html__( ' try again','xmoney-payments' ); ?>
            </a>

            <?php if ('0' == $configuration->contact_email) { ?>
                <?php
                printf(
                    '%s %s %s',
                    esc_html__( ' or','xmoney-payments' ),
                    esc_html__( ' contact','xmoney-payments' ),
                    esc_html__( ' the website administrator.','xmoney-payments' )
                );
                ?>
            <?php } else { ?>
                <?php echo esc_html__( ' or','xmoney-payments' ); ?>

                <a href="<?php echo esc_url('mailto:' . sanitize_email($configuration->contact_email)); ?>">
                    <?php echo esc_html__( ' contact','xmoney-payments' ); ?>
                </a>

                <?php echo esc_html__( ' the website administrator.','xmoney-payments' ); ?>
            <?php } ?>
        </p>
    </div>
    <?php

    die();
}


/* Extract the WooCommerce order. */
$orderId = explode('_', $decrypted['externalOrderId'])[0];
$order = wc_get_order($orderId);

/* Check if the WooCommerce order extraction failed. */
if (FALSE == $order) {
    Twispay_TW_Logger::twispay_tw_log(esc_html__('[RESPONSE-ERROR]: Order does not exist.', 'xmoney-payments'));
    ?>
    <div class="error notice" style="margin-top: 20px;">
        <h3><?php echo esc_html__( 'An error occurred:','xmoney-payments' ); ?></h3>

        <span><?php echo esc_html__( ' Order does not exist.','xmoney-payments' ); ?></span>

        <p>
            <?php echo esc_html__( 'The payment could not be processed. Please','xmoney-payments' ); ?>

            <a href="<?php echo esc_url(wc_get_cart_url()); ?>">
                <?php echo esc_html__( ' try again','xmoney-payments' ); ?>
            </a>

            <?php if ('0' == $configuration->contact_email) { ?>
                <?php
                printf(
                    '%s %s %s',
                    esc_html__( ' or','xmoney-payments' ),
                    esc_html__( ' contact','xmoney-payments' ),
                    esc_html__( ' the website administrator.','xmoney-payments' )
                );
                ?>
            <?php } else { ?>
                <?php echo esc_html__( ' or','xmoney-payments' ); ?>

                <a href="<?php echo esc_url('mailto:' . sanitize_email($configuration->contact_email)); ?>">
                    <?php echo esc_html__( ' contact','xmoney-payments' ); ?>
                </a>

                <?php echo esc_html__( ' the website administrator.','xmoney-payments' ); ?>
            <?php } ?>
        </p>
    </div>
    <?php

    die();
}

/* Check if the WooCommerce order cart hash does NOT MATCH the one sent to the server. */
if ( sanitize_text_field( wp_unslash($_GET['secure_key']) ) != $order->get_data()['cart_hash']) {
    Twispay_TW_Logger::twispay_tw_log(esc_html__('[RESPONSE-ERROR]: Invalid order identification key.', 'xmoney-payments'));
    ?>
    <div class="error notice" style="margin-top: 20px;">
        <h3><?php echo esc_html__( 'An error occurred:','xmoney-payments' ); ?></h3>

        <span><?php echo esc_html__( ' Invalid secure key.','xmoney-payments' ); ?></span>

        <p>
            <?php echo esc_html__( 'The payment could not be processed. Please','xmoney-payments' ); ?>

            <a href="<?php echo esc_url(wc_get_cart_url()); ?>">
                <?php echo esc_html__( ' try again','xmoney-payments' ); ?>
            </a>

            <?php if ('0' == $configuration->contact_email) { ?>
                <?php
                printf(
                    '%s %s %s',
                    esc_html__( ' or','xmoney-payments' ),
                    esc_html__( ' contact','xmoney-payments' ),
                    esc_html__( ' the website administrator.','xmoney-payments' )
                );
                ?>
            <?php } else { ?>
                <?php echo esc_html__( ' or','xmoney-payments' ); ?>

                <a href="<?php echo esc_url('mailto:' . sanitize_email($configuration->contact_email)); ?>">
                    <?php echo esc_html__( ' contact','xmoney-payments' ); ?>
                </a>

                <?php echo esc_html__( ' the website administrator.','xmoney-payments' ); ?>
            <?php } ?>
        </p>
    </div>
    <?php

    die();
}

/* Reconstruct the checkout URL to use it to allow client to try again in case of error. */
$checkout_url = esc_url( wc_get_checkout_url() . 'order-pay/' . $orderId . '/?pay_for_order=true&key=' . $order->get_data()['order_key'] );

Twispay_TW_Status_Updater::updateStatus_backUrl($orderId, $decrypted['transactionStatus'], $checkout_url, $tw_lang, $configuration);
