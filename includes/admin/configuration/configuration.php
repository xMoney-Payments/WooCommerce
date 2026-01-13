<?php
/**
 * Xmoney Payments Configuration Admin Page
 *
 * Xmoney Payments general configuration page on the Administrator dashboard
 *
 * @package  Xmoney/Admin
 * @category Admin
 * @author   Xmoney Payments
 */

// Exit if the file is accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the Xmoney Payments configuration page in the WordPress admin.
 *
 * @return void
 */
function xmoney_payments_configuration() {
	// Load languages
	$lang = explode( '-', get_bloginfo( 'language' ) );
	$lang = $lang[0];
	if ( file_exists( XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/' . $lang . '/lang.php' ) ) {
		require XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/' . $lang . '/lang.php';
	} else {
		require XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/en/lang.php';
	}

	if ( ! class_exists( 'WooCommerce' ) ) {
		?>
			<div class="error notice" style="margin-top: 20px;">
				<p><?php echo esc_html__( 'xMoney Payments requires WooCommerce plugin to work normally. Please activate it or install it from', 'xmoney-payments' ); ?> <a target="_blank" href="https://wordpress.org/plugins/woocommerce/"><?php echo esc_html__( 'here', 'xmoney-payments' ); ?></a>.</p>
				<div class="clearfix"></div>
			</div>
		<?php
	} else {
		?>
			<div class="wrap">
				<h2><?php echo esc_html__( 'Configuration', 'xmoney-payments' ); ?></h2>
				<?php
				if ( isset( $_GET['notice'], $_GET['xmoney_payments_notice_nonce'] ) && sanitize_text_field( wp_unslash( $_GET['notice'] ) ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['xmoney_payments_notice_nonce'] ) ), 'xmoney_payments_notice_action' ) ) {
					$notice = sanitize_text_field( wp_unslash( $_GET['notice'] ) );

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
				<p>
					<strong><?php echo esc_html__( 'Note on Privacy:', 'xmoney-payments' ); ?></strong><br/>
					<?php echo esc_html__( 'This plugin sends order information to xMoney only for secure payment processing. No tracking or analytics data is collected.', 'xmoney-payments' ); ?>
				</p>
				<form method="post" id="general_configuration">
					<?php wp_nonce_field( 'xmoney_payments_config_nonce' ); ?>
					<table class="form-table">
						<tr class="form-field form-required">
							<th scope="row"><label for="live_mode"><?php echo esc_html__( 'Live mode', 'xmoney-payments' ); ?></label></th>
							<td>
								<?php echo wp_kses( xmoney_payments_get_live_mode(), xmoney_payments_allowed_tags() ); ?>
								<p class="description"><?php echo esc_html_e( 'Select "Yes" if you want to use the payment gateway in Production Mode or "No" if you want to use it in Staging Mode.', 'xmoney-payments' ); ?></p>
							</td>
						</tr>
						<tr class="form-field" id="staging_site_id">
							<th scope="row"><label for="staging_site_id"><?php echo esc_html__( 'Staging Site ID', 'xmoney-payments' ); ?></span></label></th>
							<td>
								<input name="staging_site_id" type="text" value="<?php echo esc_attr( xmoney_payments_get_staging_site_id() ); ?>" style="max-width: 400px;" />
								<p class="description"><?php echo esc_html__( 'Enter the Site ID for Staging Mode. You can get one from', 'xmoney-payments' ); ?> <a target="_blank" href="https://merchant-stage.xmoney.com/"><?php echo esc_html__( 'here', 'xmoney-payments' ); ?></a>.</p>
							</td>
						</tr>
						<tr class="form-field" id="staging_private_key">
							<th scope="row"><label for="staging_private_key"><?php echo esc_html__( 'Staging Private Key', 'xmoney-payments' ); ?></span></label></th>
							<td>
								<input name="staging_private_key" type="text" value="<?php echo esc_attr( xmoney_payments_get_staging_private_key() ); ?>" style="max-width: 400px;" />
								<p class="description"><?php echo esc_html__( 'Enter the Private Key for Staging Mode. You can get one from', 'xmoney-payments' ); ?> <a target="_blank" href="https://merchant-stage.xmoney.com/"><?php echo esc_html__( 'here', 'xmoney-payments' ); ?></a>.</p>
							</td>
						</tr>
						<tr class="form-field" id="live_site_id">
							<th scope="row"><label for="live_site_id"><?php echo esc_html__( 'Live Site ID', 'xmoney-payments' ); ?></span></label></th>
							<td>
								<input name="live_site_id" type="text" value="<?php echo esc_attr( xmoney_payments_get_live_site_id() ); ?>" style="max-width: 400px;" />
								<p class="description"><?php echo esc_html__( 'Enter the Site ID for Live Mode. You can get one from', 'xmoney-payments' ); ?> <a target="_blank" href="https://merchant.xmoney.com/"><?php echo esc_html__( 'here', 'xmoney-payments' ); ?></a>.</p>
							</td>
						</tr>
						<tr class="form-field" id="live_private_key">
							<th scope="row"><label for="live_private_key"><?php echo esc_html__( 'Live Private Key', 'xmoney-payments' ); ?></span></label></th>
							<td>
								<input name="live_private_key" type="text" value="<?php echo esc_attr( xmoney_payments_get_live_private_key() ); ?>" style="max-width: 400px;" />
								<p class="description"><?php echo esc_html__( 'Enter the Private Key for Live Mode. You can get one from', 'xmoney-payments' ); ?> <a target="_blank" href="https://merchant.xmoney.com/"><?php echo esc_html__( 'here', 'xmoney-payments' ); ?></a>.</p>
							</td>
						</tr>
						<tr class="form-field" id="s_t_s_notification">
							<th scope="row"><label for="s_t_s_notification"><?php echo esc_html__( 'Server-to-server notification URL', 'xmoney-payments' ); ?></span></label></th>
							<td>
								<?php $xmoney_payments_ipn_url = home_url( '?twispay-ipn' ); ?>
								<input name="s_t_s_notification" disabled="disabled" type="text"
										value="<?php echo esc_url( $xmoney_payments_ipn_url ); ?>" style="max-width: 400px;"/>
								<p class="description"><?php echo esc_html__( 'Put this URL in your xMoney Payments account.', 'xmoney-payments' ); ?></p>
							</td>
						</tr>
						<tr class="form-field" id="r_custom_thankyou">
							<th scope="row"><label for="r_custom_thankyou"><?php echo esc_html__( 'Redirect to custom Thank you page', 'xmoney-payments' ); ?></span></label></th>
							<td>
								<?php echo wp_kses( xmoney_payments_get_wp_pages(), xmoney_payments_allowed_tags() ); ?>
								<p class="description"><?php echo esc_html__( 'If you want to display custom Thank you page, set it up here. You can create new custom page from', 'xmoney-payments' ); ?> <a href="<?php echo esc_url( get_admin_url() . 'post-new.php?post_type=page' ); ?>"><?php echo esc_html__( 'here', 'xmoney-payments' ); ?></a>.</p>
							</td>
						</tr>
						<tr class="form-field" id="suppress_email">
							<th scope="row"><label for="suppress_email"><?php echo esc_html__( 'Suppress default WooCommerce payment receipt emails', 'xmoney-payments' ); ?></span></label></th>
							<td>
								<?php echo wp_kses( xmoney_payments_get_suppress_email(), xmoney_payments_allowed_tags() ); ?>
								<p class="description"><?php echo esc_html__( 'Option to suppress the communication sent by the ecommerce system, in order to configure it from xMoney Payments’s Merchant interface.', 'xmoney-payments' ); ?></p>
							</td>
						</tr>
						<tr class="form-field">
							<th scope="row">
								<label for="inline_checkout"><?php echo esc_html__( 'Enable xMoney Payments Inline Checkout', 'xmoney-payments' ); ?></label>
							</th>
							<td>
								<?php
								echo wp_kses(
									xmoney_payments_get_inline_checkout(),
									array(
										'select' => array(
											'name'  => true,
											'id'    => true,
											'class' => true,
										),
										'option' => array(
											'value'    => true,
											'selected' => true,
										),
									)
								);
								?>
								<p class="description"><?php echo esc_html__( 'If set to "Yes", the payment form is embedded inline on your checkout.', 'xmoney-payments' ); ?></p>
							</td>
						</tr>
						<tr class="form-field">
							<th scope="row">
								<label for="enable_saved_cards"><?php echo esc_html__( 'Enable Saved Cards', 'xmoney-payments' ); ?></label>
							</th>
							<td>
								<?php
								echo wp_kses(
									xmoney_payments_get_enable_saved_cards(),
									array(
										'select' => array(
											'name'  => true,
											'id'    => true,
											'class' => true,
										),
										'option' => array(
											'value'    => true,
											'selected' => true,
										),
									)
								);
								?>
								<p class="description"><?php echo esc_html__( 'If set to "Yes", logged-in customers can save their cards for future purchases.', 'xmoney-payments' ); ?></p>
							</td>
						</tr>
						<tr class="form-field">
							<th scope="row">
								<label for="checkout_theme"><?php echo esc_html__( 'Checkout Form Theme', 'xmoney-payments' ); ?></label>
							</th>
							<td>
								<?php
								echo wp_kses(
									xmoney_payments_get_checkout_theme_select(),
									array(
										'select' => array(
											'name'  => true,
											'id'    => true,
											'class' => true,
										),
										'option' => array(
											'value'    => true,
											'selected' => true,
										),
									)
								);
								?>
								<p class="description"><?php echo esc_html__( 'Choose the appearance theme for the inline checkout form.', 'xmoney-payments' ); ?></p>
							</td>
						</tr>
						<?php
						$theme_vars     = xmoney_payments_get_theme_variables();
						$current_theme  = xmoney_payments_get_checkout_theme();
						$display_custom = 'custom' === $current_theme ? '' : 'display: none;';
						?>
						<tr class="form-field xmoney-custom-theme-row" style="<?php echo esc_attr( $display_custom ); ?>">
							<th scope="row"><?php echo esc_html__( 'Custom Theme Colors', 'xmoney-payments' ); ?></th>
							<td>
								<table class="xmoney-theme-variables" style="max-width: 600px;">
									<tr>
										<td><label for="theme_colorPrimary"><?php echo esc_html__( 'Primary Color', 'xmoney-payments' ); ?></label></td>
										<td><input type="color" name="theme_colorPrimary" id="theme_colorPrimary" value="<?php echo esc_attr( $theme_vars['colorPrimary'] ); ?>" /> <input type="text" value="<?php echo esc_attr( $theme_vars['colorPrimary'] ); ?>" class="xmoney-color-text" data-for="theme_colorPrimary" style="width: 80px;" /></td>
									</tr>
									<tr>
										<td><label for="theme_colorDanger"><?php echo esc_html__( 'Danger/Error Color', 'xmoney-payments' ); ?></label></td>
										<td><input type="color" name="theme_colorDanger" id="theme_colorDanger" value="<?php echo esc_attr( $theme_vars['colorDanger'] ); ?>" /> <input type="text" value="<?php echo esc_attr( $theme_vars['colorDanger'] ); ?>" class="xmoney-color-text" data-for="theme_colorDanger" style="width: 80px;" /></td>
									</tr>
									<tr>
										<td><label for="theme_colorBackground"><?php echo esc_html__( 'Background Color', 'xmoney-payments' ); ?></label></td>
										<td><input type="color" name="theme_colorBackground" id="theme_colorBackground" value="<?php echo esc_attr( $theme_vars['colorBackground'] ); ?>" /> <input type="text" value="<?php echo esc_attr( $theme_vars['colorBackground'] ); ?>" class="xmoney-color-text" data-for="theme_colorBackground" style="width: 80px;" /></td>
									</tr>
									<tr>
										<td><label for="theme_colorText"><?php echo esc_html__( 'Text Color', 'xmoney-payments' ); ?></label></td>
										<td><input type="color" name="theme_colorText" id="theme_colorText" value="<?php echo esc_attr( $theme_vars['colorText'] ); ?>" /> <input type="text" value="<?php echo esc_attr( $theme_vars['colorText'] ); ?>" class="xmoney-color-text" data-for="theme_colorText" style="width: 80px;" /></td>
									</tr>
									<tr>
										<td><label for="theme_colorTextSecondary"><?php echo esc_html__( 'Secondary Text Color', 'xmoney-payments' ); ?></label></td>
										<td><input type="color" name="theme_colorTextSecondary" id="theme_colorTextSecondary" value="<?php echo esc_attr( $theme_vars['colorTextSecondary'] ); ?>" /> <input type="text" value="<?php echo esc_attr( $theme_vars['colorTextSecondary'] ); ?>" class="xmoney-color-text" data-for="theme_colorTextSecondary" style="width: 80px;" /></td>
									</tr>
									<tr>
										<td><label for="theme_colorBorder"><?php echo esc_html__( 'Border Color', 'xmoney-payments' ); ?></label></td>
										<td><input type="color" name="theme_colorBorder" id="theme_colorBorder" value="<?php echo esc_attr( $theme_vars['colorBorder'] ); ?>" /> <input type="text" value="<?php echo esc_attr( $theme_vars['colorBorder'] ); ?>" class="xmoney-color-text" data-for="theme_colorBorder" style="width: 80px;" /></td>
									</tr>
									<tr>
										<td><label for="theme_colorBorderFocus"><?php echo esc_html__( 'Border Focus Color', 'xmoney-payments' ); ?></label></td>
										<td><input type="color" name="theme_colorBorderFocus" id="theme_colorBorderFocus" value="<?php echo esc_attr( $theme_vars['colorBorderFocus'] ); ?>" /> <input type="text" value="<?php echo esc_attr( $theme_vars['colorBorderFocus'] ); ?>" class="xmoney-color-text" data-for="theme_colorBorderFocus" style="width: 80px;" /></td>
									</tr>
									<tr>
										<td><label for="theme_colorTextPlaceholder"><?php echo esc_html__( 'Placeholder Text Color', 'xmoney-payments' ); ?></label></td>
										<td><input type="color" name="theme_colorTextPlaceholder" id="theme_colorTextPlaceholder" value="<?php echo esc_attr( $theme_vars['colorTextPlaceholder'] ); ?>" /> <input type="text" value="<?php echo esc_attr( $theme_vars['colorTextPlaceholder'] ); ?>" class="xmoney-color-text" data-for="theme_colorTextPlaceholder" style="width: 80px;" /></td>
									</tr>
									<tr>
										<td><label for="theme_colorBackgroundFocus"><?php echo esc_html__( 'Background Focus Color', 'xmoney-payments' ); ?></label></td>
										<td><input type="color" name="theme_colorBackgroundFocus" id="theme_colorBackgroundFocus" value="<?php echo esc_attr( $theme_vars['colorBackgroundFocus'] ); ?>" /> <input type="text" value="<?php echo esc_attr( $theme_vars['colorBackgroundFocus'] ); ?>" class="xmoney-color-text" data-for="theme_colorBackgroundFocus" style="width: 80px;" /></td>
									</tr>
									<tr>
										<td><label for="theme_borderRadius"><?php echo esc_html__( 'Border Radius', 'xmoney-payments' ); ?></label></td>
										<td><input type="text" name="theme_borderRadius" id="theme_borderRadius" value="<?php echo esc_attr( $theme_vars['borderRadius'] ); ?>" style="width: 80px;" placeholder="4px" /></td>
									</tr>
								</table>
							</td>
						</tr>
						<script type="text/javascript">
						(function() {
							var themeSelect = document.getElementById('checkout_theme');
							var customRows = document.querySelectorAll('.xmoney-custom-theme-row');

							function toggleCustomTheme() {
								var show = themeSelect.value === 'custom';
								customRows.forEach(function(row) {
									row.style.display = show ? '' : 'none';
								});
							}

							themeSelect.addEventListener('change', toggleCustomTheme);

							// Sync color picker with text input
							document.querySelectorAll('.xmoney-color-text').forEach(function(input) {
								var colorInput = document.getElementById(input.dataset.for);
								if (colorInput) {
									colorInput.addEventListener('input', function() {
										input.value = this.value;
									});
									input.addEventListener('input', function() {
										if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
											colorInput.value = this.value;
										}
									});
								}
							});
						})();
						</script>
						<tr class="form-field" id="contact_email_o">
							<th scope="row"><label for="contact_email_o"><?php echo esc_html__( 'Contact email(Optional)', 'xmoney-payments' ); ?></span></label></th>
							<td>
								<input name="contact_email_o" type="text" value="<?php echo esc_url( sanitize_email( xmoney_payments_get_contact_email_o() === '0' ? '' : xmoney_payments_get_contact_email_o() ) ); ?>" style="max-width: 400px;" />
								<p class="description"><?php echo esc_html__( 'This email will be used on the payment error page.', 'xmoney-payments' ); ?></p>
							</td>
						</tr>
						<tr class="form-field" id="contact_email_o">
							<th scope="row">
								<input type="hidden" name="xmoney_payments_general_action" value="edit_general_configuration" />
								<?php wp_nonce_field( 'xmoney_payments_general_action', 'xmoney_payments_general_nonce' ); ?>
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
