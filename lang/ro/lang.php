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
$xmoney_payments_lang['no_woocommerce_f']                = 'xMoney Payments necesită pluginul WooCommerce pentru a funcționa normal. Activează-l sau instalează-l de';
$xmoney_payments_lang['no_woocommerce_s']                = 'aici';
$xmoney_payments_lang['configuration_title']             = 'Configurație';
$xmoney_payments_lang['configuration_edit_notice']       = 'Configurația a fost editată cu succes.';
$xmoney_payments_lang['configuration_subtitle']          = 'Setări generale xMoney Payments.';
$xmoney_payments_lang['live_mode_label']                 = 'Mod live';
$xmoney_payments_lang['live_mode_desc']                  = 'Selectează "Da" dacă dorești să folosești gateway-ul de plată în modul Live sau "Nu" dacă dorești să îl utilizezi în modul Staging.';
$xmoney_payments_lang['staging_id_label']                = 'Staging Site ID';
$xmoney_payments_lang['staging_id_desc']                 = 'Introdu Site ID-ul pentru modul Staging. Poți obține unul de';
$xmoney_payments_lang['staging_key_label']               = 'Staging Private Key';
$xmoney_payments_lang['staging_key_desc']                = 'Introdu Private Key-ul pentru modul Staging. Poți obține unul de';
$xmoney_payments_lang['live_id_label']                   = 'Live Site ID';
$xmoney_payments_lang['live_id_desc']                    = 'Introdu Site ID-ul pentru modul Live. Poți obține unul de';
$xmoney_payments_lang['live_key_label']                  = 'Live Private Key';
$xmoney_payments_lang['live_key_desc']                   = 'Introdu Private Key-ul pentru modul Live. Poți obține unul de';
$xmoney_payments_lang['s_t_s_notification_label']        = 'Adresă URL de notificare server-to-server';
$xmoney_payments_lang['s_t_s_notification_desc']         = 'Introdu această adresă URL în contul tău xMoney Payments.';
$xmoney_payments_lang['r_custom_thankyou_label']         = 'Redirecționare la pagina personalizată de Thank You';
$xmoney_payments_lang['r_custom_thankyou_desc_f']        = 'Dacă dorești să afișezi pagina personalizată de Thank You, configureaz-o aici. Poți crea o pagină personalizată nouă de';
$xmoney_payments_lang['r_custom_thankyou_desc_s']        = 'aici';
$xmoney_payments_lang['suppress_email_label']            = 'Dezactivează e-mailurile implicite WooCommerce de confirmare a plății';
$xmoney_payments_lang['suppress_email_desc']             = 'Opțiunea de a dezactiva comunicarea trimisă de sistemul de e-commerce, pentru a o configura din interfața de comerciant xMoney Payments.';
$xmoney_payments_lang['configuration_save_button']       = 'Salvează modificările';
$xmoney_payments_lang['live_mode_option_true']           = 'Da';
$xmoney_payments_lang['live_mode_option_false']          = 'Nu';
$xmoney_payments_lang['get_all_wordpress_pages_default'] = 'Mod implicit';
$xmoney_payments_lang['contact_email_o']                 = 'E-mail de contact (Opțional)';
$xmoney_payments_lang['contact_email_o_desc']            = 'Acest e-mail va fi folosit pe pagina de eroare de plată.';


/* Transaction list from Administrator */
$xmoney_payments_lang['transaction_title']                   = 'Lista de tranzacții';
$xmoney_payments_lang['transaction_list_search_title']       = 'Caută comandă';
$xmoney_payments_lang['transaction_list_all_views']          = 'Toate';
$xmoney_payments_lang['transaction_list_refund_title']       = 'Tranzacție de restituire';
$xmoney_payments_lang['transaction_list_recurring_title']    = 'Anulează recurența acestei comenzi';
$xmoney_payments_lang['transaction_list_id']                 = 'ID';
$xmoney_payments_lang['transaction_list_id_cart']            = 'Numărul comenzii';
$xmoney_payments_lang['transaction_list_customer_name']      = 'Numele clientului';
$xmoney_payments_lang['transaction_list_transactionId']      = 'ID-ul tranzacției';
$xmoney_payments_lang['transaction_list_status']             = 'Status';
$xmoney_payments_lang['transaction_list_checkout_url']       = 'Checkout URL';
$xmoney_payments_lang['transaction_list_refund_ptitle']      = 'Tranzacție de restituire a plății';
$xmoney_payments_lang['transaction_list_refund_subtitle']    = 'Următoarea tranzație de plată va fi restituită:';
$xmoney_payments_lang['transaction_list_confirm_title']      = 'Confirm';
$xmoney_payments_lang['transaction_error_refund']            = 'Restituirea nu a putut fi procesată.';
$xmoney_payments_lang['transaction_error_recurring']         = 'Plata recurentă nu a putut fi procesată.';
$xmoney_payments_lang['transaction_success_refund']          = 'Restituirea a fost procesată cu succes. Reîncarcă pagina în câteva secunde pentru a vedea actualizarea.';
$xmoney_payments_lang['transaction_success_recurring']       = 'Comandă recurentă procesată cu succes.';
$xmoney_payments_lang['transaction_list_recurring_ptitle']   = 'Anulează o comandă recurentă';
$xmoney_payments_lang['transaction_list_recurring_subtitle'] = 'Următoarea plată recurentă va fi anulată:';
$xmoney_payments_lang['transaction_sync_finished']           = 'Sincronizarea abonamentelor terminata.';


/* Transaction log from Administrator */
$xmoney_payments_lang['transaction_log_title']    = 'Jurnal de tranzacții';
$xmoney_payments_lang['transaction_log_no_log']   = 'Nicio intrare înregistrată încă';
$xmoney_payments_lang['transaction_log_subtitle'] = 'Jurnal de tranzacții în formă brută';


/* Administrator Dashboard left-side menu */
$xmoney_payments_lang['menu_main_title']          = 'xMoney Payments';
$xmoney_payments_lang['menu_configuration_tab']   = 'Configurație';
$xmoney_payments_lang['menu_transaction_tab']     = 'Lista tranzacțiilor';
$xmoney_payments_lang['menu_transaction_log_tab'] = 'Jurnal de tranzacții';


/* Woocommerce settings xMoney Payments tab */
$xmoney_payments_lang['ws_title']                      = 'xMoney Payments';
$xmoney_payments_lang['ws_description']                = 'Invită-ți clienții să folosească gateway-ul de plată xMoney Payments.';
$xmoney_payments_lang['ws_enable_disable_title']       = 'Activează/Dezactivează';
$xmoney_payments_lang['ws_enable_disable_label']       = 'Activează plățile xMoney Payments';
$xmoney_payments_lang['ws_title_title']                = 'Titlu';
$xmoney_payments_lang['ws_title_desc']                 = 'Controlează titlul pe care îl vede clientul în timpul efectuării plății.';
$xmoney_payments_lang['ws_description_title']          = 'Descriere';
$xmoney_payments_lang['ws_description_desc']           = 'Controlează descrierea pe care clientul o vede în timpul efectuării plății.';
$xmoney_payments_lang['ws_description_default']        = 'O integrare, mai multe metode de plată. xMoney Payments vă permite să acceptați plăți practic de oriunde în lume, printr-o multitudine de metode de plată.';
$xmoney_payments_lang['ws_enable_methods_title']       = 'Activează căile de expediere';
$xmoney_payments_lang['ws_enable_methods_desc']        = 'Dacă xMoney Payments este disponibil numai pentru anumite căi de expediere, configurează-le de aici. Lasă necompletat pentru a activa toate căile.';
$xmoney_payments_lang['ws_enable_methods_placeholder'] = 'Selectează căile de expediere';
$xmoney_payments_lang['ws_vorder_title']               = 'Acceptă comenzile virtuale';
$xmoney_payments_lang['ws_vorder_desc']                = 'Acceptă xMoney Payments în cazul comenzilor virtuale';


/* Order Recieved Confirmation title */
$xmoney_payments_lang['order_confirmation_title'] = 'Mulțumim. Tranzacția ta a fost aprobată.';


/* Xmoney Payments Processor( Redirect page to xMoney Payments ) */
$xmoney_payments_lang['xmoney_payments_processor_error_general']               = 'Nu ai permisiunea de a accesa acest fișier.';
$xmoney_payments_lang['xmoney_payments_processor_error_no_item']               = 'Comanda nu are nici un produs.';
$xmoney_payments_lang['xmoney_payments_processor_error_more_items']            = 'Comenzile cu abonamente nu pot sa contina mai mult de un produs.';
$xmoney_payments_lang['xmoney_payments_processor_error_missing_configuration'] = 'Lipsa fisier de configurare pentru plugin.';


/* Validation LOG insertor */
$xmoney_payments_lang['log_ok_string_decrypted']    = '[RESPONSE]: Decriptare efectuata cu succes.';
$xmoney_payments_lang['log_ok_response_data']       = '[RESPONSE]: Data: ';
$xmoney_payments_lang['log_ok_status_complete']     = '[RESPONSE]: Status complet-ok';
$xmoney_payments_lang['log_ok_status_refund']       = '[RESPONSE]: Status refund-ok pentru comanda cu ID-ul: ';
$xmoney_payments_lang['log_ok_status_failed']       = '[RESPONSE]: Status failed pentru comanda cu ID-ul: ';
$xmoney_payments_lang['log_ok_status_hold']         = '[RESPONSE]: Status on-hold pentru comanda cu ID-ul: ';
$xmoney_payments_lang['log_ok_status_uncertain']    = '[RESPONSE]: Status uncertain pentru comanda cu ID-ul: ';
$xmoney_payments_lang['log_ok_validating_complete'] = '[RESPONSE]: Validare cu succes pentru comanda cu ID-ul: ';

$xmoney_payments_lang['log_error_validating_failed'] = '[RESPONSE-ERROR]: Validare esuată pentru comanda cu ID-ul: ';
$xmoney_payments_lang['log_error_decryption_error']  = '[RESPONSE-ERROR]: Decriptarea nu a funcționat.';
$xmoney_payments_lang['log_error_invalid_order']     = '[RESPONSE-ERROR]: Comanda nu există.';
$xmoney_payments_lang['log_error_wrong_status']      = '[RESPONSE-ERROR]: Status greșit: ';
$xmoney_payments_lang['log_error_empty_status']      = '[RESPONSE-ERROR]: Status nul';
$xmoney_payments_lang['log_error_empty_identifier']  = '[RESPONSE-ERROR]: Identificator nul';
$xmoney_payments_lang['log_error_empty_external']    = '[RESPONSE-ERROR]: ExternalOrderId gol';
$xmoney_payments_lang['log_error_empty_transaction'] = '[RESPONSE-ERROR]: TransactionID nul';
$xmoney_payments_lang['log_error_empty_response']    = ' [RESPONSE-ERROR]: Răspunsul primit este nul.';
$xmoney_payments_lang['log_error_invalid_private']   = '[RESPONSE-ERROR]: Cheie privată nevalidă.';
$xmoney_payments_lang['log_error_invalid_key']       = '[RESPONSE-ERROR]: Cheie de identificare a comenzii nevalidă.';
$xmoney_payments_lang['log_error_openssl']           = '[RESPONSE-ERROR]: opensslResult: ';


/* Subscriptions section */
$xmoney_payments_lang['subscriptions_sync_label']            = 'Sincronizeaza abonamentele';
$xmoney_payments_lang['subscriptions_sync_desc']             = 'Sincronizeaza starea locala cu starea de pe server a tuturor abonamentelor.';
$xmoney_payments_lang['subscriptions_sync_button']           = 'Sincronizeaza';
$xmoney_payments_lang['subscriptions_log_ok_set_status']     = '[RESPONSE]: Starea de pe server setata pentru comanda cu ID-ul: ';
$xmoney_payments_lang['subscriptions_log_error_set_status']  = '[RESPONSE-ERROR]: Eroare la setarea starii pentru comanda ci ID-ul: ';
$xmoney_payments_lang['subscriptions_log_error_get_status']  = '[RESPONSE-ERROR]: Eroare la extragerea starii de pe server pentru comanda cu ID-ul:A';
$xmoney_payments_lang['subscriptions_log_error_call_failed'] = '[RESPONSE-ERROR]: Eroare la apelarea server-ului: ';
$xmoney_payments_lang['subscriptions_log_error_http_code']   = '[RESPONSE-ERROR]: Cod HTTP neasteptat: ';


/* WordPress Administrator Order Notice */
$xmoney_payments_lang['wa_order_status_notice']    = 'Plata xMoney Payments a fost finalizată cu succes';
$xmoney_payments_lang['wa_order_refunded_notice']  = 'Managerul site-ului a apăsat cu succes butonul de restituire';
$xmoney_payments_lang['wa_order_cancelled_notice'] = 'Managerul site-ului a apăsat cu succes butonul de anulare';
$xmoney_payments_lang['wa_order_failed_notice']    = 'Plata xMoney Payments a fost finalizată cu eroare';
$xmoney_payments_lang['wa_order_hold_notice']      = 'Plata xMoney Payments este in asteptare';


/* Others */
$xmoney_payments_lang['general_error_title']           = 'S-a petrecut o eroare:';
$xmoney_payments_lang['general_error_desc_f']          = 'Plata nu a putut fi procesată. Te rog';
$xmoney_payments_lang['general_error_desc_try_again']  = ' încearcă din nou';
$xmoney_payments_lang['general_error_desc_or']         = ' sau';
$xmoney_payments_lang['general_error_desc_contact']    = ' contactează';
$xmoney_payments_lang['general_error_desc_s']          = ' administratorul site-ului.';
$xmoney_payments_lang['general_error_hold_notice']     = ' Plata este in asteptare.';
$xmoney_payments_lang['general_error_invalid_key']     = ' Cheie de siguranță nevalidă.';
$xmoney_payments_lang['general_error_invalid_order']   = ' Comanda nu există.';
$xmoney_payments_lang['general_error_invalid_private'] = ' Cheie privată nevalidă.';


/* JSON decoding/encoding errors */
$xmoney_payments_lang['JSON_ERROR_DEPTH']                 = 'Adancimea maxima a stivei a fost depasita.';
$xmoney_payments_lang['JSON_ERROR_STATE_MISMATCH']        = 'JSON invalid sau deformat.';
$xmoney_payments_lang['JSON_ERROR_CTRL_CHAR']             = 'Eroare la caracterul de control, posibil sa nu fie codificat corect.';
$xmoney_payments_lang['JSON_ERROR_SYNTAX']                = 'Eroare de sintaxa.';
$xmoney_payments_lang['JSON_ERROR_UTF8']                  = 'Caractere UTF-8 deformate, posibil sa nu fie codificate corect.';
$xmoney_payments_lang['JSON_ERROR_RECURSION']             = 'Una sau mai multe referinte recursive in valorile care trebuie codificate.';
$xmoney_payments_lang['JSON_ERROR_INF_OR_NAN']            = 'Una sau mai multe valoru NAN sau INF in valorile care trebuie codificate.';
$xmoney_payments_lang['JSON_ERROR_UNSUPPORTED_TYPE']      = 'A fost trimisa o valoare de un tip ce nu poate fi codificat.';
$xmoney_payments_lang['JSON_ERROR_INVALID_PROPERTY_NAME'] = 'A fost trimis un nume de proprietate ce nu poate fi codificat.';
$xmoney_payments_lang['JSON_ERROR_UTF16']                 = 'Caractere UTF-16 deformate, posibil sa nu fie codificate corect.';
$xmoney_payments_lang['JSON_ERROR_UNKNOWN']               = 'Eroare necunoscuta.';

$xmoney_payments_lang['default_description'] = 'Plateste cu xMoney Payments';
