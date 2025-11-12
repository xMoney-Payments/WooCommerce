<?php
/**
 * Xmoney Payments Language Configurator
 *
 * Xmoney Payments general language handler for everything
 *
 * @package  xMoney Payments/Language
 * @category Admin/Front
 * @author   xMoney Payments
 */
/* Exit if the file is accessed directly. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Configuration panel from Administrator */
$xmoney_payments_lang['no_woocommerce_f']                = 'xMoney Payments requires WooCommerce plugin to work normally. Please activate it or install it from';
$xmoney_payments_lang['no_woocommerce_s']                = 'here';
$xmoney_payments_lang['configuration_title']             = 'Configuration';
$xmoney_payments_lang['configuration_edit_notice']       = 'Configuration has been edited successfully.';
$xmoney_payments_lang['configuration_subtitle']          = 'xMoney Payments general settings.';
$xmoney_payments_lang['live_mode_label']                 = 'Live mode';
$xmoney_payments_lang['live_mode_desc']                  = 'Select "Yes" if you want to use the payment gateway in Production Mode or "No" if you want to use it in Staging Mode.';
$xmoney_payments_lang['staging_id_label']                = 'Staging Site ID';
$xmoney_payments_lang['staging_id_desc']                 = 'Enter the Site ID for Staging Mode. You can get one from';
$xmoney_payments_lang['staging_key_label']               = 'Staging Private Key';
$xmoney_payments_lang['staging_key_desc']                = 'Enter the Private Key for Staging Mode. You can get one from';
$xmoney_payments_lang['live_id_label']                   = 'Live Site ID';
$xmoney_payments_lang['live_id_desc']                    = 'Enter the Site ID for Live Mode. You can get one from';
$xmoney_payments_lang['live_key_label']                  = 'Live Private Key';
$xmoney_payments_lang['live_key_desc']                   = 'Enter the Private Key for Live Mode. You can get one from';
$xmoney_payments_lang['s_t_s_notification_label']        = 'Server-to-server notification URL';
$xmoney_payments_lang['s_t_s_notification_desc']         = 'Put this URL in your xMoney Payments account.';
$xmoney_payments_lang['r_custom_thankyou_label']         = 'Redirect to custom Thank you page';
$xmoney_payments_lang['r_custom_thankyou_desc_f']        = 'If you want to display custom Thank you page, set it up here. You can create new custom page from';
$xmoney_payments_lang['r_custom_thankyou_desc_s']        = 'here';
$xmoney_payments_lang['suppress_email_label']            = 'Suppress default WooCommerce payment receipt emails';
$xmoney_payments_lang['suppress_email_desc']             = 'Option to suppress the communication sent by the ecommerce system, in order to configure it from xMoney Payments’s Merchant interface.';
$xmoney_payments_lang['configuration_save_button']       = 'Save changes';
$xmoney_payments_lang['live_mode_option_true']           = 'Yes';
$xmoney_payments_lang['live_mode_option_false']          = 'No';
$xmoney_payments_lang['get_all_wordpress_pages_default'] = 'Default';
$xmoney_payments_lang['contact_email_o']                 = 'Contact email(Optional)';
$xmoney_payments_lang['contact_email_o_desc']            = 'This email will be used on the payment error page.';


/* Transaction list from Administrator */
$xmoney_payments_lang['transaction_title']                   = 'Transaction list';
$xmoney_payments_lang['transaction_list_search_title']       = 'Search Order';
$xmoney_payments_lang['transaction_list_all_views']          = 'All';
$xmoney_payments_lang['transaction_list_refund_title']       = 'Refund transaction';
$xmoney_payments_lang['transaction_list_recurring_title']    = 'Cancel recurring on this order';
$xmoney_payments_lang['transaction_list_id']                 = 'ID';
$xmoney_payments_lang['transaction_list_id_cart']            = 'Order reference';
$xmoney_payments_lang['transaction_list_customer_name']      = 'Customer name';
$xmoney_payments_lang['transaction_list_transactionId']      = 'Transaction ID';
$xmoney_payments_lang['transaction_list_status']             = 'Status';
$xmoney_payments_lang['transaction_list_checkout_url']       = 'Checkout url';
$xmoney_payments_lang['transaction_list_refund_ptitle']      = 'Refund Payment Transaction';
$xmoney_payments_lang['transaction_list_refund_subtitle']    = 'Following payment transaction will be refunded:';
$xmoney_payments_lang['transaction_list_confirm_title']      = 'Confirm';
$xmoney_payments_lang['transaction_error_refund']            = 'Refund could not been processed.';
$xmoney_payments_lang['transaction_error_recurring']         = 'Recurring could not been processed.';
$xmoney_payments_lang['transaction_success_refund']          = 'Refund processed successfully. Refresh the page in seconds to see the update.';
$xmoney_payments_lang['transaction_success_recurring']       = 'Recurring processed successfully.';
$xmoney_payments_lang['transaction_list_recurring_ptitle']   = 'Cancel a recurring order';
$xmoney_payments_lang['transaction_list_recurring_subtitle'] = 'Following recurring order will be canceled:';
$xmoney_payments_lang['transaction_sync_finished']           = 'Subscriptions synchronization finished.';


/* Transaction log from Administrator */
$xmoney_payments_lang['transaction_log_title']    = 'Transaction log';
$xmoney_payments_lang['transaction_log_no_log']   = 'No log recorded yet.';
$xmoney_payments_lang['transaction_log_subtitle'] = 'Transaction log in raw form.';


/* Administrator Dashboard left-side menu */
$xmoney_payments_lang['menu_main_title']          = 'xMoney Payments';
$xmoney_payments_lang['menu_configuration_tab']   = 'Configuration';
$xmoney_payments_lang['menu_transaction_tab']     = 'Transaction list';
$xmoney_payments_lang['menu_transaction_log_tab'] = 'Transaction log';


/* Woocommerce settings xMoney Payments tab */
$xmoney_payments_lang['ws_title']                      = 'xMoney Payments';
$xmoney_payments_lang['ws_description']                = 'Have your customers pay with xMoney Payments payment gateway.';
$xmoney_payments_lang['ws_enable_disable_title']       = 'Enable/Disable';
$xmoney_payments_lang['ws_enable_disable_label']       = 'Enable  xMoney Payments  Payments';
$xmoney_payments_lang['ws_title_title']                = 'Title';
$xmoney_payments_lang['ws_title_desc']                 = 'This controls the title which the customer sees during checkout.';
$xmoney_payments_lang['ws_description_title']          = 'Description';
$xmoney_payments_lang['ws_description_desc']           = 'This controls the description which the customer sees during checkout.';
$xmoney_payments_lang['ws_description_default']        = 'One integration, multiple payment methods.  xMoney Payments  enables you to accept payments from virtually anywhere in the world through a myriad of payment methods.';
$xmoney_payments_lang['ws_enable_methods_title']       = 'Enable for shipping methods';
$xmoney_payments_lang['ws_enable_methods_desc']        = 'If  xMoney Payments  is only available for certain shipping methods, set it up here. Leave blank to enable for all methods.';
$xmoney_payments_lang['ws_enable_methods_placeholder'] = 'Select shipping methods';
$xmoney_payments_lang['ws_vorder_title']               = 'Accept for virtual orders';
$xmoney_payments_lang['ws_vorder_desc']                = 'Accept  xMoney Payments if the order is virtual';


/* Order Recieved Confirmation title */
$xmoney_payments_lang['order_confirmation_title'] = 'Thank you. Your transaction is approved.';


/* Xmoney Payments Processor( Redirect page to xMoney Payments ) */
$xmoney_payments_lang['xmoney_payments_processor_error_general']               = 'You are not allowed to access this file.';
$xmoney_payments_lang['xmoney_payments_processor_error_no_item']               = 'The order has no items.';
$xmoney_payments_lang['xmoney_payments_processor_error_more_items']            = 'Orders with subscriptions cannot have other products too.';
$xmoney_payments_lang['xmoney_payments_processor_error_missing_configuration'] = 'Missing configuration for plugin.';


/* Validation LOG insertor */
$xmoney_payments_lang['log_ok_string_decrypted']    = '[RESPONSE]: Decryption successfully performed.';
$xmoney_payments_lang['log_ok_response_data']       = '[RESPONSE]: Data: ';
$xmoney_payments_lang['log_ok_status_complete']     = '[RESPONSE]: Status complete-ok for order ID: ';
$xmoney_payments_lang['log_ok_status_refund']       = '[RESPONSE]: Status refund-ok for order ID: ';
$xmoney_payments_lang['log_ok_status_failed']       = '[RESPONSE]: Status failed for order ID: ';
$xmoney_payments_lang['log_ok_status_hold']         = '[RESPONSE]: Status on-hold for order ID: ';
$xmoney_payments_lang['log_ok_status_uncertain']    = '[RESPONSE]: Status uncertain for order ID: ';
$xmoney_payments_lang['log_ok_validating_complete'] = '[RESPONSE]: Validating completed for order ID: ';

$xmoney_payments_lang['log_error_validating_failed'] = '[RESPONSE-ERROR]: Validation failed.';
$xmoney_payments_lang['log_error_decryption_error']  = '[RESPONSE-ERROR]: Decryption failed.';
$xmoney_payments_lang['log_error_invalid_order']     = '[RESPONSE-ERROR]: Order does not exist.';
$xmoney_payments_lang['log_error_wrong_status']      = '[RESPONSE-ERROR]: Wrong status: ';
$xmoney_payments_lang['log_error_empty_status']      = '[RESPONSE-ERROR]: Empty status';
$xmoney_payments_lang['log_error_empty_identifier']  = '[RESPONSE-ERROR]: Empty identifier';
$xmoney_payments_lang['log_error_empty_external']    = '[RESPONSE-ERROR]: Empty externalOrderId';
$xmoney_payments_lang['log_error_empty_transaction'] = '[RESPONSE-ERROR]: Empty transactionId';
$xmoney_payments_lang['log_error_empty_response']    = ' [RESPONSE-ERROR]: Received empty response.';
$xmoney_payments_lang['log_error_invalid_private']   = '[RESPONSE-ERROR]: Private key is not valid.';
$xmoney_payments_lang['log_error_invalid_key']       = '[RESPONSE-ERROR]: Invalid order identification key.';
$xmoney_payments_lang['log_error_openssl']           = '[RESPONSE-ERROR]: opensslResult: ';


/* Subscriptions section */
$xmoney_payments_lang['subscriptions_sync_label']            = 'Synchronize subscriptions';
$xmoney_payments_lang['subscriptions_sync_desc']             = 'Synchronize the local status of all subscriptions with the server status.';
$xmoney_payments_lang['subscriptions_sync_button']           = 'Synchronize';
$xmoney_payments_lang['subscriptions_log_ok_set_status']     = '[RESPONSE]: Server status set for order ID: ';
$xmoney_payments_lang['subscriptions_log_error_set_status']  = '[RESPONSE-ERROR]: Failed to set server status for order ID: ';
$xmoney_payments_lang['subscriptions_log_error_get_status']  = '[RESPONSE-ERROR]: Failed to get server status for order ID: ';
$xmoney_payments_lang['subscriptions_log_error_call_failed'] = '[RESPONSE-ERROR]: Failed to call server: ';
$xmoney_payments_lang['subscriptions_log_error_http_code']   = '[RESPONSE-ERROR]: Unexpected HTTP response code: ';


/* WordPress Administrator Order Notice */
$xmoney_payments_lang['wa_order_status_notice']    = ' xMoney Payments  payment finalised successfully';
$xmoney_payments_lang['wa_order_refunded_notice']  = 'Website manager pressed on refund button successfully';
$xmoney_payments_lang['wa_order_cancelled_notice'] = 'Website manager pressed on cancel button successfully';
$xmoney_payments_lang['wa_order_failed_notice']    = 'xMoney Payments payment failed';
$xmoney_payments_lang['wa_order_hold_notice']      = ' payment is on hold';


/* Others */
$xmoney_payments_lang['general_error_title']           = 'An error occurred:';
$xmoney_payments_lang['general_error_desc_f']          = 'The payment could not be processed. Please';
$xmoney_payments_lang['general_error_desc_try_again']  = ' try again';
$xmoney_payments_lang['general_error_desc_or']         = ' or';
$xmoney_payments_lang['general_error_desc_contact']    = ' contact';
$xmoney_payments_lang['general_error_desc_s']          = ' the website administrator.';
$xmoney_payments_lang['general_error_hold_notice']     = ' Payment is on hold.';
$xmoney_payments_lang['general_error_invalid_key']     = ' Invalid secure key.';
$xmoney_payments_lang['general_error_invalid_order']   = ' Order does not exist.';
$xmoney_payments_lang['general_error_invalid_private'] = ' Private key is not valid.';


/* JSON decoding/encoding errors */
$xmoney_payments_lang['JSON_ERROR_DEPTH']                 = 'The maximum stack depth has been exceeded.';
$xmoney_payments_lang['JSON_ERROR_STATE_MISMATCH']        = 'Invalid or malformed JSON.';
$xmoney_payments_lang['JSON_ERROR_CTRL_CHAR']             = 'Control character error, possibly incorrectly encoded.';
$xmoney_payments_lang['JSON_ERROR_SYNTAX']                = 'Syntax error.';
$xmoney_payments_lang['JSON_ERROR_UTF8']                  = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
$xmoney_payments_lang['JSON_ERROR_RECURSION']             = 'One or more recursive references in the value to be encoded.';
$xmoney_payments_lang['JSON_ERROR_INF_OR_NAN']            = 'One or more NAN or INF values in the value to be encoded.';
$xmoney_payments_lang['JSON_ERROR_UNSUPPORTED_TYPE']      = 'A value of a type that cannot be encoded was given.';
$xmoney_payments_lang['JSON_ERROR_INVALID_PROPERTY_NAME'] = 'A property name that cannot be encoded was given.';
$xmoney_payments_lang['JSON_ERROR_UTF16']                 = 'Malformed UTF-16 characters, possibly incorrectly encoded.';
$xmoney_payments_lang['JSON_ERROR_UNKNOWN']               = 'Unknown error.';

$xmoney_payments_lang['default_description'] = 'Pay with xMoney Payments';
