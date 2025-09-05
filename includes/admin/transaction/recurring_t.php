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
        if ( isset( $_GET['order_ad'] ) && esc_attr(sanitize_text_field(wp_unslash($_GET['order_ad'])) ) ) {
            foreach ( explode( ',', esc_attr(sanitize_text_field(wp_unslash($_GET['order_ad'])) ) ) as $key => $a_id ) {
                print_r( 'ID: #' . esc_html($a_id) );
                print_r( '<br>' );
            }
        }
    ?>

    <form method="post" id="recurring_order">
        <input type="hidden" name="tw_general_action" value="recurring_order" />
        <?php submit_button( esc_attr__( 'Confirm', 'xmoney-payments' ), 'primary', 'createuser', true, array( 'id' => 'confirmdeletion' ) ); ?>
    </form>
</div>
