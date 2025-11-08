<?php
/**
 * Twispay Refund Transaction
 *
 * Refund transaction html form
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
    <h2><?php echo esc_html__( 'Refund Payment Transaction','xmoney-payments' ); ?></h2>
    <p><?php echo esc_html__( 'Following payment transaction will be refunded:','xmoney-payments' ); ?></p>

    <!-- Get all payment transactions ID from the $_GET parameters (only if nonce is valid) -->
    <?php
        $list_nonce = isset($_GET['twispay_transactions_nonce']) ? sanitize_text_field( wp_unslash( $_GET['twispay_transactions_nonce'] ) ) : '';
        if (
            ! empty( $list_nonce ) &&
            wp_verify_nonce( $list_nonce, 'twispay_transactions_action' ) &&
            isset( $_GET['payment_ad'] ) &&
            sanitize_text_field( wp_unslash( $_GET['payment_ad'] ) )
        ) {
            $ids_raw = sanitize_text_field( wp_unslash( $_GET['payment_ad'] ) );
            foreach ( explode( ',', $ids_raw ) as $key => $a_id ) {
                echo '<p>ID: #' . esc_html( $a_id ) . '</p>';
            }
        }
    ?>

    <form method="post" id="refund_payment_transaction">
        <input type="hidden" name="tw_general_action" value="refund_payment_transaction" />
        <?php wp_nonce_field('twispay_general_action', 'twispay_general_nonce'); ?>
        <?php submit_button( esc_attr__( 'Confirm', 'xmoney-payments' ), 'primary', 'createuser', true, array( 'id' => 'confirmdeletion' ) ); ?>
    </form>
</div>
