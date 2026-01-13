<?php
/**
 * Xmoney Payments Recurring Transaction
 *
 * Recurring transaction html form
 *
 * @package  Xmoney/Admin
 * @category Admin
 * @author   Xmoney Payments
 */

/* Exit if the file is accessed directly. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load languages
$xmoney_payments_lang_code = explode( '-', get_bloginfo( 'language' ) );
$xmoney_payments_lang_code = $xmoney_payments_lang_code[0];
if ( file_exists( XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/' . $xmoney_payments_lang_code . '/lang.php' ) ) {
	require XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/' . $xmoney_payments_lang_code . '/lang.php';
} else {
	require XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/en/lang.php';
}

?>
<div class="wrap">
	<h2><?php echo esc_html__( 'Cancel a recurring order', 'xmoney-payments' ); ?></h2>
	<p><?php echo esc_html__( 'Following recurring order will be canceled:', 'xmoney-payments' ); ?></p>

	<!-- Get all payment order ID from the $_GET parameters (only if nonce is valid) -->
	<?php
		$xmoney_payments_list_nonce = isset( $_GET['xmoney_payments_transactions_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['xmoney_payments_transactions_nonce'] ) ) : '';
	if (
			! empty( $xmoney_payments_list_nonce ) &&
			wp_verify_nonce( $xmoney_payments_list_nonce, 'xmoney_payments_transactions_action' ) &&
			isset( $_GET['order_ad'] ) &&
			sanitize_text_field( wp_unslash( $_GET['order_ad'] ) )
		) {
		$xmoney_payments_ids_raw = sanitize_text_field( wp_unslash( $_GET['order_ad'] ) );
		foreach ( explode( ',', $xmoney_payments_ids_raw ) as $xmoney_payments_key => $xmoney_payments_a_id ) {
			echo '<p>ID: #' . esc_html( $xmoney_payments_a_id ) . '</p>';
		}
	}
	?>

	<form method="post" id="recurring_order">
		<input type="hidden" name="xmoney_payments_general_action" value="recurring_order" />
		<?php wp_nonce_field( 'xmoney_payments_general_action', 'xmoney_payments_general_nonce' ); ?>
		<?php submit_button( esc_attr__( 'Confirm', 'xmoney-payments' ), 'primary', 'createuser', true, array( 'id' => 'confirmdeletion' ) ); ?>
	</form>
</div>
