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

    <!-- Get all payment transactions ID from the $_GET parameters -->
    <?php
        if ( isset( $_GET['payment_ad'] ) && esc_attr( sanitize_text_field(wp_unslash($_GET['payment_ad'])) ) ) {
            foreach ( explode( ',', esc_attr(sanitize_text_field(wp_unslash($_GET['payment_ad'])) ) ) as $key => $a_id ) {
                print_r( 'ID: #' . esc_html( $a_id ) );
                print_r( '<br>' );
            }
        }
    ?>

    <form method="post" id="refund_payment_transaction">
        <input type="hidden" name="tw_general_action" value="refund_payment_transaction" />
        <?php submit_button( esc_attr__( 'Confirm', 'xmoney-payments' ), 'primary', 'createuser', true, array( 'id' => 'confirmdeletion' ) ); ?>
    </form>
</div>
