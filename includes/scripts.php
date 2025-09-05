<?php
/**
 * Twispay Scripts Page
 *
 * Add the js and css files for administrator pages and for non-administrator pages
 *
 * @package  Twispay/Admin
 * @category Admin
 * @author   Twispay
 */

/* Require the "Twispay_TW_Logger" class. */
require_once( TWISPAY_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'Twispay_TW_Logger.php' );

/**
 * Twispay Admin Checker
 *
 * Check if the current page is an Twispay Admin Page or not
 *
 * @public
 * @return bool True if is an admin page, false otherwise
 */
function twispay_tw_check_if_is_admin() {
    // Check if is admin page
    if ( ! is_admin() ) {
        return false;
    }

    // Check if the page parameters is present
    if ( ! isset( $_GET['page'] ) ) {
        return false;
    }

    // Make array with all xMoney Payments Pages
    $tw_pages = array(
        'xmoney-payments',
        'tw-transaction'
    );

    // Check if current page is one of the xMoney Payments Pages
    return in_array( sanitize_text_field( $_GET['page'] ), $tw_pages );
}


/**
 * Twispay Add Admin Js
 *
 * This function will add all js script ONLY for Twispay Pages
 *
 * @public
 * @return void
 */
function twispay_tw_add_admin_js() {
    // Check if current page is an Twispay Admin Page
    if ( ! twispay_tw_check_if_is_admin() ) {
        return;
    }

    // Load all admin js files for Administrator Pages
    wp_enqueue_script( 'ma-admin', TWISPAY_PLUGIN_URL . 'assets/js/admin.js', [], 1, ['in_footer' => false] );
}
add_action( 'admin_enqueue_scripts', 'twispay_tw_add_admin_js' );


/**
 * Twispay Add Admin Css
 *
 * This function will add all css files ONLY for Twispay Pages
 *
 * @public
 * @return void
 */
function twispay_tw_add_admin_css() {
    // Check if current page is an xMoney Payments Admin Page
    if ( ! twispay_tw_check_if_is_admin() ) {
        return;
    }

    // Load all admin css files for Administrator Pages
    wp_enqueue_style( 'ma-admin', TWISPAY_PLUGIN_URL . 'assets/css/admin.css', [], 1 );
}
add_action( 'admin_enqueue_scripts', 'twispay_tw_add_admin_css' );


/**
 * Twispay Add Front Css
 *
 * This function will add all front css files
 *
 * @public
 * @return void
 */
function twispay_tw_add_front_css() {
    // Load all front css files
    wp_enqueue_style( 'ma-front', TWISPAY_PLUGIN_URL . 'assets/css/front.css', [], 1 );
}
add_action( 'wp_enqueue_scripts', 'twispay_tw_add_front_css' );


/**
 * Twispay init the Payment Gateway
 *
 * This function will load the payment gateway class
 *
 * @public
 * @return void
 */
function init_twispay_gateway_class() {
    if ( class_exists( 'WooCommerce' ) ) {
        class WC_Gateway_Twispay_Gateway extends WC_Payment_Gateway {
            /**
             * Twispay Gateway Constructor
             *
             * @public
             * @return void
             */
            public function __construct() {
                /* Load languages */
                $lang = explode( '-', get_bloginfo( 'language' ) )[0];
                if ( file_exists( TWISPAY_PLUGIN_DIR . 'lang/' . $lang . '/lang.php' ) ) {
                    require( TWISPAY_PLUGIN_DIR . 'lang/' . $lang . '/lang.php' );
                } else {
                    require( TWISPAY_PLUGIN_DIR . 'lang/en/lang.php' );
                }

                $this->id = 'xmoney-payments';
                $this->icon =  TWISPAY_PLUGIN_URL . 'logo.png';
                $this->has_fields = true;
                $this->method_title = esc_html__( 'xMoney Payments','xmoney-payments' );
                $this->method_description = esc_html__( 'Have your customers pay with xMoney payment gateway.','xmoney-payments' );
                if( class_exists('WC_Subscriptions') ){
                    $this->supports = [ 'products'
                                      , 'refunds'
                                      , 'subscriptions'
                                      , 'subscription_cancellation'
                                      , 'subscription_suspension'
                                      , 'subscription_reactivation'
                                      , 'subscription_amount_changes'
                                      , 'subscription_date_changes'
                                      , 'subscription_payment_method_change'
                                      , 'subscription_payment_method_change_customer'
                                      , 'subscription_payment_method_change_admin'
                                      , 'multiple_subscriptions'
                                      , 'gateway_scheduled_payments'];
                } else {
                    $this->supports = [ 'products', 'refunds' ];
                }

                $this->init_form_fields();
                $this->init_settings();

                $this->title = empty( $this->get_option( 'title' ) ) ? 'xMoney Payments' : $this->get_option( 'title' );
                $this->description = empty( $this->get_option( 'description' ) ) ? esc_html__('Pay with xMoney Payments', 'xmoney-payments') : $this->get_option( 'description' );
                $this->enable_for_methods = $this->get_option( 'enable_for_methods', array() );
                $this->enable_for_virtual = $this->get_option( 'enable_for_virtual', 'yes' ) === 'yes' ? true : false;

                $shipping_methods = array();

                foreach ( WC()->shipping()->load_shipping_methods() as $method ) {
                    $shipping_methods[ $method->id ] = $method->get_method_title();
                }

                $this->form_fields = array(
                    'enabled' => array(
                        'title'    => esc_html__( 'Enable/Disable', 'xmoney-payments' ),
                        'type'     => 'checkbox',
                        'label'    => esc_html__( 'Enable xMoney Payments Payments', 'xmoney-payments' ),
                        'default'  => 'yes'
                    ),
                    'title' => array(
                        'title'        => esc_html__( 'Title', 'xmoney-payments' ),
                        'type'         => 'text',
                        'description'  => esc_html__( 'This controls the title which the customer sees during checkout.', 'xmoney-payments' ),
                        'default'      => esc_html__( 'xMoney Payments', 'xmoney-payments' ),
                        'desc_tip'     => true
                    ),
                    'description' => array(
                        'title'        => esc_html__( 'Description', 'xmoney-payments' ),
                        'type'         => 'textarea',
                        'description'  => esc_html__( 'This controls the description which the customer sees during checkout.', 'xmoney-payments' ),
                        'default'      => esc_html__( 'One integration, multiple payment methods. xMoney Payments enables you to accept payments from virtually anywhere in the world through a myriad of payment methods.', 'xmoney-payments' ),
                        'desc_tip'     => true
                    ),
                    'enable_for_methods' => array(
                        'title'              => esc_html__( 'Enable for shipping methods', 'xmoney-payments' ),
                        'type'               => 'multiselect',
                        'class'              => 'wc-enhanced-select',
                        'css'                => 'width: 400px;',
                        'default'            => '',
                        'description'        => esc_html__( 'If xMoney Payments is only available for certain shipping methods, set it up here. Leave blank to enable for all methods.', 'xmoney-payments' ),
                        'options'            => $shipping_methods,
                        'desc_tip'           => true,
                        'custom_attributes'  => array(
                            'data-placeholder'  => esc_html__( 'Select shipping methods', 'xmoney-payments' ),
                        ),
                    ),
                    'enable_for_virtual' => array(
                        'title'    => esc_html__( 'Accept for virtual orders', 'xmoney-payments' ),
                        'label'    => esc_html__( 'Accept xMoney Payments if the order is virtual', 'xmoney-payments' ),
                        'type'     => 'checkbox',
                        'default'  => 'yes',
                    )
                );

                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            }

            /**
            * Check if the Twispay Gateway is available for use
            *
            * @return bool
            */
            public function is_available() {
                $order          = null;
                $needs_shipping = false;

                // Test if shipping is needed first
                if ( WC()->cart && WC()->cart->needs_shipping() ) {
                    $needs_shipping = true;
                }
                elseif ( is_page( wc_get_page_id( 'checkout' ) ) && 0 < get_query_var( 'order-pay' ) ) {
                    $order_id = absint( get_query_var( 'order-pay' ) );
                    $order    = wc_get_order( $order_id );

                    // Test if order needs shipping.
                    if ( 0 < sizeof( $order->get_items() ) ) {
                        foreach ( $order->get_items() as $item ) {
                            $_product = $item->get_product();
                            if ( $_product && $_product->needs_shipping() ) {
                                $needs_shipping = true;
                                break;
                            }
                        }
                    }
                }

                $needs_shipping = apply_filters( 'woocommerce_cart_needs_shipping', $needs_shipping );

                // Virtual order, with virtual disabled
                if ( ! $this->enable_for_virtual && ! $needs_shipping ) {
                    return false;
                }

                // Check methods
                if ( ! empty( $this->enable_for_methods ) && $needs_shipping ) {
                    // Only apply if all packages are being shipped via chosen methods, or order is virtual
                    $chosen_shipping_methods_session = WC()->session->get( 'chosen_shipping_methods' );

                    if ( isset( $chosen_shipping_methods_session ) ) {
                        $chosen_shipping_methods = array_unique( $chosen_shipping_methods_session );
                    }
                    else {
                        $chosen_shipping_methods = array();
                    }

                    $check_method = false;

                    if ( is_object( $order ) ) {
                        if ( $order->shipping_method ) {
                            $check_method = $order->shipping_method;
                        }
                    }
                    elseif ( empty( $chosen_shipping_methods ) || sizeof( $chosen_shipping_methods ) > 1 ) {
                        $check_method = false;
                    }
                    elseif ( sizeof( $chosen_shipping_methods ) == 1 ) {
                        $check_method = $chosen_shipping_methods[0];
                    }

                    if ( ! $check_method ) {
                        return false;
                    }

                    if ( strstr( $check_method, ':' ) ) {
                        $check_method = current( explode( ':', $check_method ) );
                    }

                    $found = false;

                    foreach ( $this->enable_for_methods as $method_id ) {
                        if ( $check_method === $method_id ) {
                            $found = true;
                            break;
                        }
                    }

                    if ( ! $found ) {
                        return false;
                    }
                }

                return parent::is_available();
            }

            /**
             * Twispay Process Payment function
             *
             * @public
             * @return array with Result and Redirect
             */
            function process_payment( $order_id ) {

                /*
                 * For several pages get order working this conditions $actual_link is not equal home page
                 * and get page name, for example default - /checkout/
                 *
                 * For single page all in one (cart and checkout page) $actual_link is equal home page
                 * if in admin setting page Woocommerce -> Settings -> Advanced the field "Checkout page"
                 * - must be empty then condition str_replace(home_url(), '', $actual_link) === '' returning true
                 *
                 */
                $actual_link = wc_get_checkout_url();

                if ( str_replace( home_url(), '', $actual_link ) === '' ) {
                    $actual_link = wc_get_cart_url();
                }

                /* Check if the order contains a subscription. */
                if ( class_exists( 'WC_Subscriptions' ) && ( TRUE == wcs_order_contains_subscription( $order_id ) ) ) {
                    /*
                     * Redirect to the virtual page for products with subscription.
                     * The content of the file was moved to the main twispay.php file, and hooks for the virtual page
                     * were also created.
                     *
                     * The virtual page differs from the usual one by adding get parameters to the page url, in
                     * this case - ?order_id=xx&subscription=true will be added to the page address url
                     *
                     * The woocommerce_after_checkout_form hook will intercept the passed parameters and redirect
                     * to the xMoney Payments payment gateway page
                     */
                    $args = array( 'order_id' =>  $order_id . '_sub' );

                    return array(
                      'result' => 'success',
                      'redirect' => esc_url_raw(
                        add_query_arg(
                          $args,
                          $actual_link
                        )
                      )
                    );
                } else {
                    /*
                     * Redirect to the virtual page for products with default payment method.
                     * The content of the file was moved to the main twispay.php file, and hooks for the virtual page
                     * were also created.
                     *
                     * The virtual page differs from the usual one by adding get parameters to the page url, in
                     * this case - ?order_id=xx will be added to the page address url
                     *
                     * The woocommerce_after_checkout_form hook will intercept the passed parameters and redirect
                     * to the xMoney Payments payment gateway page
                     */
                    $args = array( 'order_id' =>  $order_id );

                    return array(
                      'result' => 'success',
                      'redirect' => esc_url_raw(
                        add_query_arg(
                          $args,
                          $actual_link
                        )
                      )
                    );
                }
            }

            /**
             * Twispay Process Payment function
             *
             * @param  int        $order_id Order ID.
             * @param  float|null $amount Refund amount.
             * @param  string     $reason Refund reason.
             *
             * @return boolean|WP_Error True or false based on success, or a WP_Error object.
             */
            function process_refund($order_id, $amount = NULL, $reason = '') {
                global $wpdb;
                $apiKey = '';
                $transaction_id = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT transactionId FROM " . $wpdb->prefix . "twispay_tw_transactions WHERE id_cart = %d",
                        $order_id
                    )
                );
                if (!$transaction_id) {
                    return new WP_Error( 'error', "Invalid transaction id");
                }

                /* Get configuration from database. */
                $configuration = $wpdb->get_row( "SELECT * FROM " . $wpdb->prefix . "twispay_tw_configuration" );
                if (!$configuration) {
	                return new WP_Error( 'error', "Missing configuration");
                }
				
                if ( 1 == $configuration->live_mode ) {
                    $apiKey = $configuration->live_key;
                    $url = 'https://api.xmoney.com/transaction/' . sanitize_key( $transaction_id );
                } else {
                    $apiKey = $configuration->staging_key;
                    $url = 'https://api-stage.xmoney.com/transaction/' . sanitize_key( $transaction_id );
                }

                $args = array('method' => 'DELETE', 'headers' => ['accept' => 'application/json', 'Authorization' => $apiKey]);
                if (!is_null($amount)) {
                    $amount = round($amount,2);
                    if ($amount > 0) {
                        $args['body']['amount'] = $amount;
                    } else {
                        return new WP_Error( 'error', "Invalid amount");
                    }
                }
                
                if ($reason) {
                    $args['body']['reason'] = 'customer-demand';
                    $args['body']['message'] = $reason;
                }
                
                $response = wp_remote_request( $url, $args );
                $code = $response['response']['code'] ?? 0;
                $msg = $response['response']['message'] ?? "Unknown reason";
                
                if ( 'OK' != $msg ) {
	                return new WP_Error( 'error', "TWISPAY API error: $code - $msg" );
                }
				
                Twispay_TW_Logger::twispay_tw_updateTransactionStatus($order_id, Twispay_TW_Status_Updater::$RESULT_STATUSES['REFUND_OK']);
                return true;
            }
        }
    }
}
add_action( 'plugins_loaded', 'init_twispay_gateway_class' );


/**
 * Add the Twispay gateway class
 *
 * @public
 * @return array $methods
 */
function add_twispay_gateway_class( $methods ) {
    if ( class_exists( 'WooCommerce' ) ) {
        $methods[] = 'WC_Gateway_Twispay_Gateway';
        return $methods;
    }
}
add_filter( 'woocommerce_payment_gateways', 'add_twispay_gateway_class' );


/**
 * Twispay Prepare buffer functions
 *
 * This function will prepare the buffer in order to use wp_redirect properly
 *
 * @public
 * @return void
 */
function twispay_tw_start_buffer_output() {
    ob_start();
}
add_action('init', 'twispay_tw_start_buffer_output');


/**
 * Custom text on the receipt page.
 */
function twispay_tw_isa_order_received_text( $text, $order ) {
    // Load languages
    $lang = explode( '-', get_bloginfo( 'language' ) );
    $lang = $lang[0];
    if ( file_exists( TWISPAY_PLUGIN_DIR . 'lang/' . $lang . '/lang.php' ) ) {
        require( TWISPAY_PLUGIN_DIR . 'lang/' . $lang . '/lang.php' );
    } else {
        require( TWISPAY_PLUGIN_DIR . 'lang/en/lang.php' );
    }

    return esc_html__( 'Thank you. Your transaction is approved.','xmoney-payments' );
}
add_filter('woocommerce_thankyou_order_received_text', 'twispay_tw_isa_order_received_text', 10, 2 );


/**
 * Suppress email functionality
 */
function twispay_tw_unhook_woo_order_emails( $email_class ) {
    // New order emails
    remove_action( 'woocommerce_order_status_pending_to_processing_notification', array( $email_class->emails['WC_Email_New_Order'], 'trigger' ) );
    remove_action( 'woocommerce_order_status_pending_to_completed_notification', array( $email_class->emails['WC_Email_New_Order'], 'trigger' ) );
    remove_action( 'woocommerce_order_status_pending_to_on-hold_notification', array( $email_class->emails['WC_Email_New_Order'], 'trigger' ) );
    remove_action( 'woocommerce_order_status_failed_to_processing_notification', array( $email_class->emails['WC_Email_New_Order'], 'trigger' ) );
    remove_action( 'woocommerce_order_status_failed_to_completed_notification', array( $email_class->emails['WC_Email_New_Order'], 'trigger' ) );
    remove_action( 'woocommerce_order_status_failed_to_on-hold_notification', array( $email_class->emails['WC_Email_New_Order'], 'trigger' ) );

    // Processing order emails
    remove_action( 'woocommerce_order_status_pending_to_processing_notification', array( $email_class->emails['WC_Email_Customer_Processing_Order'], 'trigger' ) );
    remove_action( 'woocommerce_order_status_pending_to_on-hold_notification', array( $email_class->emails['WC_Email_Customer_Processing_Order'], 'trigger' ) );

    // Completed order emails
    remove_action( 'woocommerce_order_status_completed_notification', array( $email_class->emails['WC_Email_Customer_Completed_Order'], 'trigger' ) );
}

// Get configuration from database
global $wpdb;
$suppress_email = $wpdb->get_row( "SELECT suppress_email FROM " . $wpdb->prefix . "twispay_tw_configuration" );

if ( $suppress_email ) {
    if ( $suppress_email->suppress_email == 1 ) {
        add_action( 'woocommerce_email', 'twispay_tw_unhook_woo_order_emails' );
    }
}



function subscription_terminated( $subscription ){
    /* Get configuration from database. */
    global $wpdb;
    $apiKey = '';
    $configuration = $wpdb->get_row( "SELECT * FROM " . $wpdb->prefix . "twispay_tw_configuration" );
    $serverOrderId = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT orderId FROM " . $wpdb->prefix . "twispay_tw_transactions WHERE id_cart = %d",
            $subscription->get_parent_id()
        )
    );
    if ( $configuration ) {
        if ( $configuration->live_mode == 1 ) {
            $apiKey = $configuration->live_key;
            $url = 'https://api.xmoney.com/order/' . sanitize_key( $serverOrderId );
        } else if ( $configuration->live_mode == 0 ) {
            $apiKey = $configuration->staging_key;
            $url = 'https://api-stage.xmoney.com/order/' . sanitize_key( $serverOrderId );
        }
    }

    /* Load languages */
    $lang = explode( '-', get_bloginfo( 'language' ) )[0];
    if ( file_exists( TWISPAY_PLUGIN_DIR . 'lang/' . $lang . '/lang.php' ) ) {
        require( TWISPAY_PLUGIN_DIR . 'lang/' . $lang . '/lang.php' );
    } else {
        require( TWISPAY_PLUGIN_DIR . 'lang/en/lang.php' );
    }

    $args = array( 'method' => 'DELETE'
                  , 'headers' => ['accept' => 'application/json', 'Authorization' => $apiKey]);
    $response = wp_remote_request($url, $args);

    if ( $response['response']['message'] == 'OK' ) {
        Twispay_TW_Logger::twispay_tw_log(esc_html__('[RESPONSE]: Server status set for order ID: ', 'xmoney-payments') . esc_html( $subscription->get_parent_id() ));
    } else {
        Twispay_TW_Logger::twispay_tw_log(esc_html__('[RESPONSE-ERROR]: Failed to set server status for order ID: ', 'xmoney-payments') . esc_html( $subscription->get_parent_id() ));
    }
}
add_action('woocommerce_subscription_status_cancelled', 'subscription_terminated');
add_action('woocommerce_subscription_status_expired', 'subscription_terminated');
