<?php
/**
 * Twispay Recurring Transaction
 *
 * Recurring transaction html form
 *
 * @package  Twispay/Admin
 * @category Admin
 * @author   Twispay
 */

/* Exit if the file is accessed directly. */
if (!defined('ABSPATH')) {
    exit;
}

// Load languages
$lang = explode( '-', get_bloginfo( 'language' ) );
$lang = $lang[0];
if ( file_exists( TWISPAY_PLUGIN_DIR . 'lang/' . $lang . '/lang.php' ) ) {
    require( TWISPAY_PLUGIN_DIR . 'lang/' . $lang . '/lang.php' );
} else {
    require( TWISPAY_PLUGIN_DIR . 'lang/en/lang.php' );
}

?>
<div class="wrap">
    <h2><?php echo esc_html__( 'Cancel a recurring order','xmoney-payments' ); ?></h2>
    <p><?php echo esc_html__( 'Following recurring order will be canceled:','xmoney-payments' ); ?></p>

    <!-- Get all payment order ID from the $_GET parameters -->
    <?php
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin page, only for displaying a value, not processing an action
        if ( isset( $_GET['order_ad'] ) && esc_attr(sanitize_text_field(wp_unslash($_GET['order_ad'])) ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin page, only for displaying a value, not processing an action
            foreach ( explode( ',', esc_attr(sanitize_text_field(wp_unslash($_GET['order_ad'])) ) ) as $key => $a_id ) {
                echo '<p>ID: #' . esc_html($a_id) . '</p>';
            }
        }
    ?>

    <form method="post" id="recurring_order">
        <input type="hidden" name="tw_general_action" value="recurring_order" />
        <?php wp_nonce_field('twispay_general_action', 'twispay_general_nonce'); ?>
        <?php submit_button( esc_attr__( 'Confirm', 'xmoney-payments' ), 'primary', 'createuser', true, array( 'id' => 'confirmdeletion' ) ); ?>
    </form>
</div>
