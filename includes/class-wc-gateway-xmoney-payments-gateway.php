<?php
/**
 * Xmoney Payments Scripts Page
 *
 * Add the js and css files for administrator pages and for non-administrator pages
 *
 * @package  Xmoney/Admin
 * @category Admin
 * @author   Xmoney Payments
 */
/* Exit if the file is accessed directly. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Require the "Xmoney_Payments_Logger" class. */
require_once XMONEY_PAYMENTS_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'class-xmoney-payments-logger.php';

/**
 * Xmoney Payments Admin Checker
 *
 * Check if the current page is an Xmoney Payments Admin Page or not
 *
 * @public
 * @return bool True if is an admin page, false otherwise
 */
function xmoney_payments_check_if_is_admin(): bool {
	// Early exit if not in admin.
	if ( ! is_admin() ) {
		return false;
	}

	// Prefer current screen API over directly accessing $_GET to avoid nonce warnings.
	// get_current_screen() is available on admin pages after 'current_screen' is set.
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen ) {
		return false;
	}

	// Whitelist screen IDs for this plugin (top-level + subpages).
	$xmoney_payments_screen_ids = array(
		'toplevel_page_xmoney-payments',
		'xmoney-payments_page_xmoney-payments',
		'xmoney-payments_page_xmoney-payments-transaction',
		'xmoney-payments_page_xmoney-payments-transaction-log',
	);

	return in_array( $screen->id, $xmoney_payments_screen_ids, true );
}


/**
 * Xmoney Payments Add Admin Js
 *
 * This function will add all js script ONLY for Xmoney Payments Pages
 *
 * @public
 * @return void
 */
function xmoney_payments_add_admin_js() {
	// Check if current page is an Xmoney Payments Admin Page
	if ( ! xmoney_payments_check_if_is_admin() ) {
		return;
	}

	// Load all admin js files for Administrator Pages
	wp_enqueue_script( 'ma-admin', XMONEY_PAYMENTS_PLUGIN_URL . 'assets/js/admin.js', array(), 1, array( 'in_footer' => false ) );
}
add_action( 'admin_enqueue_scripts', 'xmoney_payments_add_admin_js' );


/**
 * Xmoney Payments Add Admin Css
 *
 * This function will add all css files ONLY for Xmoney Payments Pages
 *
 * @public
 * @return void
 */
function xmoney_payments_add_admin_css() {
	// Check if current page is an xMoney Payments Admin Page
	if ( ! xmoney_payments_check_if_is_admin() ) {
		return;
	}

	// Load all admin css files for Administrator Pages
	wp_enqueue_style( 'ma-admin', XMONEY_PAYMENTS_PLUGIN_URL . 'assets/css/admin.css', array(), XMONEY_PAYMENTS_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'xmoney_payments_add_admin_css' );


/**
 * Xmoney Payments Add Front Css
 *
 * This function will add all front css files
 *
 * @public
 * @return void
 */
function xmoney_payments_add_front_css() {
	// Load all front css files
	wp_enqueue_style( 'ma-front', XMONEY_PAYMENTS_PLUGIN_URL . 'assets/css/front.css', array(), XMONEY_PAYMENTS_VERSION, 'all' );
}
add_action( 'wp_enqueue_scripts', 'xmoney_payments_add_front_css' );


/**
 * Xmoney Payments init the Payment Gateway
 *
 * This function will load the payment gateway class
 *
 * @public
 */
function xmoney_payments_init_gateway_class() {
	if ( class_exists( 'WooCommerce' ) ) {
		/**
		 *  WooCommerce Gateway implementation for xMoney Payments.
		 */
		class WC_Gateway_Xmoney_Payments_Gateway extends WC_Payment_Gateway {
			/**
			 * Enabled shipping methods.
			 *
			 * @var array
			 */
			private $enable_for_methods;
			/**
			 * Whether gateway is enabled for virtual orders.
			 *
			 * @var bool
			 */
			private $enable_for_virtual;
			/**
			 * Xmoney Payments Gateway Constructor
			 *
			 * @public
			 * @return void
			 */
			public function __construct() {
				/* Load languages */
				$lang = explode( '-', get_bloginfo( 'language' ) )[0];
				if ( file_exists( XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/' . $lang . '/lang.php' ) ) {
					require XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/' . $lang . '/lang.php';
				} else {
					require XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/en/lang.php';
				}

				$this->id                 = 'xmoney-payments';
				$this->icon               = XMONEY_PAYMENTS_PLUGIN_URL . 'logo.png';
				$this->has_fields         = true;
				$this->method_title       = esc_html__( 'xMoney Payments', 'xmoney-payments' );
				$this->method_description = esc_html__( 'Have your customers pay with xMoney payment gateway.', 'xmoney-payments' );
				if ( class_exists( 'WC_Subscriptions' ) ) {
					$this->supports = array(
						'products',
						'refunds',
						'subscriptions',
						'subscription_cancellation',
						'subscription_suspension',
						'subscription_reactivation',
						'subscription_amount_changes',
						'subscription_date_changes',
						'subscription_payment_method_change',
						'subscription_payment_method_change_customer',
						'subscription_payment_method_change_admin',
						'multiple_subscriptions',
						'gateway_scheduled_payments',
					);
				} else {
					$this->supports = array( 'products', 'refunds' );
				}

				$this->init_form_fields();
				$this->init_settings();

				$this->title              = empty( $this->get_option( 'title' ) ) ? 'xMoney Payments' : $this->get_option( 'title' );
				$this->description        = empty( $this->get_option( 'description' ) ) ? esc_html__( 'Pay with xMoney Payments', 'xmoney-payments' ) : $this->get_option( 'description' );
				$this->enable_for_methods = $this->get_option( 'enable_for_methods', array() );
				$this->enable_for_virtual = $this->get_option( 'enable_for_virtual', 'yes' ) === 'yes' ? true : false;

				$shipping_methods = array();

				foreach ( WC()->shipping()->load_shipping_methods() as $method ) {
					$shipping_methods[ $method->id ] = $method->get_method_title();
				}

				$this->form_fields = array(
					'enabled'            => array(
						'title'   => esc_html__( 'Enable/Disable', 'xmoney-payments' ),
						'type'    => 'checkbox',
						'label'   => esc_html__( 'Enable xMoney Payments Payments', 'xmoney-payments' ),
						'default' => 'yes',
					),
					'title'              => array(
						'title'       => esc_html__( 'Title', 'xmoney-payments' ),
						'type'        => 'text',
						'description' => esc_html__( 'This controls the title which the customer sees during checkout.', 'xmoney-payments' ),
						'default'     => esc_html__( 'xMoney Payments', 'xmoney-payments' ),
						'desc_tip'    => true,
					),
					'description'        => array(
						'title'       => esc_html__( 'Description', 'xmoney-payments' ),
						'type'        => 'textarea',
						'description' => esc_html__( 'This controls the description which the customer sees during checkout.', 'xmoney-payments' ),
						'default'     => esc_html__( 'One integration, multiple payment methods. xMoney Payments enables you to accept payments from virtually anywhere in the world through a myriad of payment methods.', 'xmoney-payments' ),
						'desc_tip'    => true,
					),
					'enable_for_methods' => array(
						'title'             => esc_html__( 'Enable for shipping methods', 'xmoney-payments' ),
						'type'              => 'multiselect',
						'class'             => 'wc-enhanced-select',
						'css'               => 'width: 400px;',
						'default'           => '',
						'description'       => esc_html__( 'If xMoney Payments is only available for certain shipping methods, set it up here. Leave blank to enable for all methods.', 'xmoney-payments' ),
						'options'           => $shipping_methods,
						'desc_tip'          => true,
						'custom_attributes' => array(
							'data-placeholder' => esc_html__( 'Select shipping methods', 'xmoney-payments' ),
						),
					),
					'enable_for_virtual' => array(
						'title'   => esc_html__( 'Accept for virtual orders', 'xmoney-payments' ),
						'label'   => esc_html__( 'Accept xMoney Payments if the order is virtual', 'xmoney-payments' ),
						'type'    => 'checkbox',
						'default' => 'yes',
					),
				);

				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
				add_action( 'wp_ajax_xmoney_prepare_payment', array( $this, 'ajax_prepare_payment' ) );
				add_action( 'wp_ajax_nopriv_xmoney_prepare_payment', array( $this, 'ajax_prepare_payment' ) );
				add_action( 'wp_ajax_xmoney_create_order', array( $this, 'ajax_create_order' ) );
				add_action( 'wp_ajax_nopriv_xmoney_create_order', array( $this, 'ajax_create_order' ) );
				add_action( 'wp_ajax_xmoney_create_draft_order', array( $this, 'ajax_create_draft_order' ) );
				add_action( 'wp_ajax_nopriv_xmoney_create_draft_order', array( $this, 'ajax_create_draft_order' ) );
				add_action( 'wp_ajax_xmoney_update_draft_order', array( $this, 'ajax_update_draft_order' ) );
				add_action( 'wp_ajax_nopriv_xmoney_update_draft_order', array( $this, 'ajax_update_draft_order' ) );
			}

			/**
			 * Check if the Xmoney Payments Gateway is available for use
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				$xmoney_payments_order = null;
				$needs_shipping        = false;

				// Test if shipping is needed first
				if ( WC()->cart && WC()->cart->needs_shipping() ) {
					$needs_shipping = true;
				} elseif ( is_page( wc_get_page_id( 'checkout' ) ) && 0 < get_query_var( 'order-pay' ) ) {
					$order_id              = absint( get_query_var( 'order-pay' ) );
					$xmoney_payments_order = wc_get_order( $order_id );

					// Test if order needs shipping.
					if ( 0 < count( $xmoney_payments_order->get_items() ) ) {
						foreach ( $xmoney_payments_order->get_items() as $item ) {
							$_product = $item->get_product();
							if ( $_product && $_product->needs_shipping() ) {
								$needs_shipping = true;
								break;
							}
						}
					}
				}

                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Using a core WooCommerce hook.
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
					} else {
						$chosen_shipping_methods = array();
					}

					$check_method = false;

					if ( is_object( $xmoney_payments_order ) ) {
						if ( $xmoney_payments_order->shipping_method ) {
							$check_method = $xmoney_payments_order->shipping_method;
						}
					} elseif ( empty( $chosen_shipping_methods ) || count( $chosen_shipping_methods ) > 1 ) {
						$check_method = false;
					} elseif ( count( $chosen_shipping_methods ) === 1 ) {
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
			 * Process payment and redirect to xMoney payment page.
			 *
			 * @param int $order_id Order ID.
			 * @return array Result and redirect URL.
			 */
			public function process_payment( $order_id ) {
				// For inline checkout, generate payment data and trigger SDK initialization
				if ( function_exists( 'xmoney_payments_is_inline_enabled' ) && xmoney_payments_is_inline_enabled() ) {
					require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'helpers/class-xmoney-payments-helper-processor.php';
					require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'helpers/class-xmoney-payments-helper-notify.php';

					$order      = wc_get_order( $order_id );
					$config     = Xmoney_Payments_Helper_Processor::get_configuration();
					$is_live    = ! empty( $config['is_live'] );
					$secret_key = $config['secret_key'];
					$public_key = $config['site_id'];
					$data       = $order->get_data();

					// Build order data (simplified from processor class)
					$items = array();
					foreach ( $order->get_items() as $item ) {
						$items[] = array(
							'item'      => $item['name'],
							'units'     => $item['quantity'],
							'unitPrice' => number_format( $item['subtotal'] / $item['quantity'], 2, '.', '' ),
						);
					}

					$site_hash           = substr( md5( get_site_url() ), 0, 8 );
					$current_user_id     = get_current_user_id();
					$customer_identifier = $current_user_id
						? sprintf( 'site%s_user_%d', $site_hash, $current_user_id )
						: sprintf( 'site%s_guest_%s', $site_hash, uniqid() );

					$customer = array(
						'identifier' => $customer_identifier,
						'firstName'  => ! empty( $data['billing']['first_name'] ) ? $data['billing']['first_name'] : '',
						'lastName'   => ! empty( $data['billing']['last_name'] ) ? $data['billing']['last_name'] : '',
						'country'    => ! empty( $data['billing']['country'] ) ? $data['billing']['country'] : '',
						'city'       => ! empty( $data['billing']['city'] ) ? $data['billing']['city'] : '',
						'address'    => ! empty( $data['billing']['address_1'] ) ? $data['billing']['address_1'] : '',
						'zipCode'    => ! empty( $data['billing']['postcode'] ) ? $data['billing']['postcode'] : '',
						'phone'      => Xmoney_Payments_Helper_Processor::format_phone( $data['billing']['phone'] ),
						'email'      => $data['billing']['email'],
					);

					$order_data = array(
						'siteId'              => $public_key,
						'customer'            => $customer,
						'order'               => array(
							'orderId'  => (string) $order_id,
							'type'     => 'purchase',
							'amount'   => $data['total'],
							'currency' => $data['currency'],
							'items'    => $items,
						),
						'cardTransactionMode' => 'authAndCapture',
						'invoiceEmail'        => '',
						'backUrl'             => $order->get_checkout_order_received_url(),
						'publicKey'           => $public_key,
					);

					$request_data  = Xmoney_Payments_Helper_Notify::get_base64_json_request( $order_data );
					$checksum      = Xmoney_Payments_Helper_Notify::get_base64_checksum( $order_data, $secret_key );
					$session_token = Xmoney_Payments_Helper_Processor::get_session_token( $is_live, $secret_key );

					// Return payment data to JavaScript
					return array(
						'result'             => 'success',
						'redirect'           => '', // Keep user on checkout page
						'xmoney_inline_data' => array(
							'payload'      => $request_data,
							'checksum'     => $checksum,
							'publicKey'    => $public_key,
							'orderId'      => $order_id,
							'sessionToken' => $session_token,
							'confirmUrl'   => esc_url_raw( rest_url( 'xmoney/v1/inline/confirm' ) ),
							'restNonce'    => wp_create_nonce( 'wp_rest' ),
						),
					);
				}

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
				if ( class_exists( 'WC_Subscriptions' ) && ( true === wcs_order_contains_subscription( $order_id ) ) ) {
					/*
					 * Redirect to the virtual page for products with subscription.
					 *
					 * The virtual page differs from the usual one by adding get parameters to the page url, in
					 * this case - ?order_id=xx&subscription=true will be added to the page address url
					 *
					 * The woocommerce_after_checkout_form hook will intercept the passed parameters and redirect
					 * to the xMoney Payments payment gateway page
					 */
					// Include a nonce so the subscription processor can verify the request.
					$args = array(
						'order_id' => $order_id . '_sub',
						'_wpnonce' => wp_create_nonce( 'xmoney_payments_process' ),
					);

					return array(
						'result'   => 'success',
						'redirect' => esc_url_raw(
							add_query_arg(
								$args,
								$actual_link
							)
						),
					);
				} else {
					/*
					 * Redirect to the virtual page for products with default payment method.
					 *
					 * The virtual page differs from the usual one by adding get parameters to the page url, in
					 * this case - ?order_id=xx will be added to the page address url
					 *
					 * The woocommerce_after_checkout_form hook will intercept the passed parameters and redirect
					 * to the xMoney Payments payment gateway page
					 */
					// Include a nonce so the main processor can verify the request.
					$args = array(
						'order_id' => $order_id,
						'_wpnonce' => wp_create_nonce( 'xmoney_payments_process' ),
					);

					return array(
						'result'   => 'success',
						'redirect' => esc_url_raw(
							add_query_arg(
								$args,
								$actual_link
							)
						),
					);
				}
			}

			/**
			 * Process a refund via xMoney Payments.
			 *
			 * @param int        $order_id Order ID.
			 * @param float|null $amount Refund amount.
			 * @param string     $reason Refund reason.
			 * @return bool|WP_Error True on success, false or WP_Error on failure.
			 */
			public function process_refund( $order_id, $amount = null, $reason = '' ) {
				global $wpdb;
				$api_key = '';
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$transaction_id = $wpdb->get_var(
					$wpdb->prepare(
						'SELECT transactionId FROM ' . $wpdb->prefix . 'xmoney_payments_transactions WHERE id_cart = %d',
						$order_id
					)
				);
				if ( ! $transaction_id ) {
					return new WP_Error( 'error', 'Invalid transaction id' );
				}

				/*
				Get configuration from database. */
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$configuration = $wpdb->get_row( 'SELECT * FROM ' . $wpdb->prefix . 'xmoney_payments_configuration' );
				if ( ! $configuration ) {
					return new WP_Error( 'error', 'Missing configuration' );
				}

				if ( '1' === $configuration->live_mode ) {
					$api_key = $configuration->live_key;
					$url     = 'https://api.xmoney.com/transaction/' . sanitize_key( $transaction_id );
				} else {
					$api_key = $configuration->staging_key;
					$url     = 'https://api-stage.xmoney.com/transaction/' . sanitize_key( $transaction_id );
				}

				$args = array(
					'method'  => 'DELETE',
					'headers' => array(
						'accept'        => 'application/json',
						'Authorization' => $api_key,
					),
				);
				if ( ! is_null( $amount ) ) {
					$amount = round( $amount, 2 );
					if ( $amount > 0 ) {
						$args['body']['amount'] = $amount;
					} else {
						return new WP_Error( 'error', 'Invalid amount' );
					}
				}

				if ( $reason ) {
					$args['body']['reason']  = 'customer-demand';
					$args['body']['message'] = $reason;
				}

				$response = wp_remote_request( $url, $args );
				$code     = $response['response']['code'] ?? 0;
				$msg      = $response['response']['message'] ?? 'Unknown reason';

				if ( 'OK' !== $msg ) {
					return new WP_Error( 'error', "TWISPAY API error: $code - $msg" );
				}

				Xmoney_Payments_Logger::xmoney_payments_update_transaction_status( $order_id, Xmoney_Payments_Status_Updater::$result_statuses['REFUND_OK'] );
				return true;
			}

			public function payment_fields() {
				// Enqueue inline scripts on checkout when payment method is displayed
				if ( function_exists( 'xmoney_payments_is_inline_enabled' ) && xmoney_payments_is_inline_enabled() ) {
					$this->enqueue_inline_scripts();
				}

				// Inline iframe container (persistent!)
				echo '<div id="tw-xmoney-inline-wrap" style="margin:16px 0;">
                        <div id="xmoney-checkout-container"></div>
                      </div>';
			}

			/**
			 * Enqueue inline checkout scripts early so they're available when payment method is selected
			 */
			private function enqueue_inline_scripts() {
				if ( wp_script_is( 'xmoney-inline-sdk', 'enqueued' ) ) {
					return; // Already enqueued
				}

				require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'helpers/class-xmoney-payments-helper-processor.php';

				$config  = Xmoney_Payments_Helper_Processor::get_configuration();
				$is_live = ! empty( $config['is_live'] );

				$sdk_url = $is_live
					? ( Xmoney_Payments_Helper_Processor::LIVE_URL_JS . '/sdk/0.0.19.alpha.2/xmoney.js' )
					: ( Xmoney_Payments_Helper_Processor::STAGE_URL_JS . '/sdk/0.0.19.alpha.2/xmoney.js' );

				// Enqueue the xMoney SDK script
				wp_enqueue_script(
					'xmoney-inline-sdk',
					$sdk_url,
					array(),
					XMONEY_PAYMENTS_VERSION,
					true
				);

				// Enqueue our inline handler script
				wp_enqueue_script(
					'xmoney-inline-js',
					XMONEY_PAYMENTS_PLUGIN_URL . 'assets/js/inline.js',
					array( 'jquery', 'xmoney-inline-sdk' ),
					XMONEY_PAYMENTS_VERSION,
					true
				);

				// Localize minimal data needed for initialization trigger
				wp_localize_script(
					'xmoney-inline-js',
					'xmoneyConfig',
					array(
						'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
						'nonce'           => wp_create_nonce( 'xmoney_prepare_payment' ),
						'isInlineEnabled' => true,
					)
				);
			}

			/**
			 * AJAX handler to prepare payment data without creating order
			 *
			 * @return void
			 */
			public function ajax_prepare_payment() {
				// Verify nonce
				if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'xmoney_prepare_payment' ) ) {
					wp_send_json_error( array( 'message' => 'Invalid nonce' ), 403 );
				}

				// Check if cart exists
				if ( ! WC()->cart || WC()->cart->is_empty() ) {
					wp_send_json_error( array( 'message' => 'Cart is empty' ), 400 );
				}

				require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'helpers/class-xmoney-payments-helper-processor.php';

				// Get configuration
				$config = Xmoney_Payments_Helper_Processor::get_configuration();
				if ( empty( $config ) ) {
					wp_send_json_error( array( 'message' => 'Configuration not available' ), 500 );
				}

				$is_live    = ! empty( $config['is_live'] );
				$public_key = $config['site_id'];
				$secret_key = $config['secret_key'];

				// Get cart data
				$cart       = WC()->cart;
				$cart_total = $cart->get_total( 'raw' );

				// Build order data similar to processor
				$customer_id        = get_current_user_id();
				$billing_email      = WC()->customer ? WC()->customer->get_billing_email() : '';
				$billing_phone      = WC()->customer ? WC()->customer->get_billing_phone() : '';
				$billing_first_name = WC()->customer ? WC()->customer->get_billing_first_name() : '';
				$billing_last_name  = WC()->customer ? WC()->customer->get_billing_last_name() : '';
				$billing_country    = WC()->customer ? WC()->customer->get_billing_country() : '';
				$billing_city       = WC()->customer ? WC()->customer->get_billing_city() : '';
				$billing_address    = WC()->customer ? WC()->customer->get_billing_address_1() : '';
				$billing_postcode   = WC()->customer ? WC()->customer->get_billing_postcode() : '';

				// Generate temporary order ID (will be replaced when actual order is created)
				$temp_order_id = 'temp_' . time() . '_' . wp_rand( 1000, 9999 );

				// Generate customer identifier similar to processor
				$site_hash = substr( md5( get_site_url() ), 0, 8 );
				if ( $customer_id ) {
					$customer_identifier = sprintf( 'site%s_user_%d', $site_hash, $customer_id );
				} else {
					$customer_identifier = sprintf( 'site%s_guest_%s', $site_hash, uniqid() );
				}

				// Build customer array
				$customer = array(
					'identifier' => $customer_identifier,
					'firstName'  => $billing_first_name,
					'lastName'   => $billing_last_name,
					'country'    => $billing_country,
					'city'       => $billing_city,
					'address'    => $billing_address,
					'zipCode'    => $billing_postcode,
					'phone'      => Xmoney_Payments_Helper_Processor::format_phone( $billing_phone ),
					'email'      => $billing_email,
				);

				// Build items array from cart
				$items = array();
				foreach ( $cart->get_cart() as $cart_item ) {
					$product = $cart_item['data'];
					$items[] = array(
						'item'      => $product->get_name(),
						'units'     => $cart_item['quantity'],
						'unitPrice' => floatval( $cart_item['line_subtotal'] / $cart_item['quantity'] ),
					);
				}

				// Build backUrl (temporary - will be replaced when order is created)
				$back_url = wc_get_checkout_url();

				// Build order array matching processor format
				// NOTE: Don't include orderId at all for early initialization to avoid validation
				$order_data = array(
					'siteId'              => $public_key,
					'customer'            => $customer,
					'order'               => array(
						// orderId intentionally omitted - will be added when order is created
						'type'     => 'purchase',
						'amount'   => $cart_total,
						'currency' => get_woocommerce_currency(),
						'items'    => $items,
					),
					'cardTransactionMode' => 'authAndCapture',
					'invoiceEmail'        => '',
					'backUrl'             => $back_url,
				);

				// Add publicKey for inline checkout
				$order_data['publicKey'] = $public_key;

				// Check for subscriptions in cart
				$has_subscription = false;
				if ( class_exists( 'WC_Subscriptions_Cart' ) ) {
					$has_subscription = WC_Subscriptions_Cart::cart_contains_subscription();
				}

				// Build payload and checksum using the same method as processor
				require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'helpers/class-xmoney-payments-helper-notify.php';
				$payload  = Xmoney_Payments_Helper_Notify::get_base64_json_request( $order_data );
				$checksum = Xmoney_Payments_Helper_Notify::get_base64_checksum( $order_data, $secret_key );

				// Get session token if customer is logged in
				$session_token = null;
				$saved_cards   = array();
				if ( $customer_id > 0 ) {
					$token_data = Xmoney_Payments_Helper_Processor::get_session_token( $is_live, $secret_key );
					if ( ! empty( $token_data ) && isset( $token_data['sessionToken'] ) ) {
						$session_token = $token_data['sessionToken'];
						$user_id       = isset( $token_data['userId'] ) ? $token_data['userId'] : null;

						// Get saved cards if session token is available
						if ( $session_token && $user_id ) {
							$saved_cards_data = Xmoney_Payments_Helper_Processor::get_saved_cards( $session_token, $user_id, $is_live );
							if ( ! empty( $saved_cards_data ) ) {
								$saved_cards = $saved_cards_data;
							}
						}
					}
				}

				// Build response
				$response = array(
					'publicKey'       => $public_key,
					'payload'         => $payload,
					'checksum'        => $checksum,
					'hasSubscription' => $has_subscription,
					'tempOrderId'     => $temp_order_id,
				);

				// Add session token data if available
				if ( $session_token ) {
					$response['sessionToken'] = $session_token;
					if ( ! empty( $token_data['userId'] ) ) {
						$response['userId'] = $token_data['userId'];
					}
					if ( ! empty( $saved_cards ) ) {
						$response['savedCards'] = $saved_cards;
					}
				}

				wp_send_json_success( $response );
			}

			/**
			 * AJAX handler to create WooCommerce order before payment
			 *
			 * @return void
			 */
			public function ajax_create_order() {
				// Verify nonce
				if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'xmoney_prepare_payment' ) ) {
					wp_send_json_error( array( 'message' => 'Invalid nonce' ), 403 );
				}

				// Check if cart exists
				if ( ! WC()->cart || WC()->cart->is_empty() ) {
					wp_send_json_error( array( 'message' => 'Cart is empty' ), 400 );
				}

				try {
					// Create the order
					$checkout = WC()->checkout();

					// Get posted data (simulate checkout submission)
					$data = array(
						'payment_method' => 'xmoney-payments',
						'terms'          => '1',
						'terms-field'    => '1',
					);

					// Merge with customer billing/shipping data
					if ( WC()->customer ) {
						$data['billing_first_name'] = WC()->customer->get_billing_first_name();
						$data['billing_last_name']  = WC()->customer->get_billing_last_name();
						$data['billing_email']      = WC()->customer->get_billing_email();
						$data['billing_phone']      = WC()->customer->get_billing_phone();
						$data['billing_country']    = WC()->customer->get_billing_country();
						$data['billing_address_1']  = WC()->customer->get_billing_address_1();
						$data['billing_address_2']  = WC()->customer->get_billing_address_2();
						$data['billing_city']       = WC()->customer->get_billing_city();
						$data['billing_state']      = WC()->customer->get_billing_state();
						$data['billing_postcode']   = WC()->customer->get_billing_postcode();

						if ( WC()->cart->needs_shipping() ) {
							$data['shipping_first_name'] = WC()->customer->get_shipping_first_name();
							$data['shipping_last_name']  = WC()->customer->get_shipping_last_name();
							$data['shipping_country']    = WC()->customer->get_shipping_country();
							$data['shipping_address_1']  = WC()->customer->get_shipping_address_1();
							$data['shipping_address_2']  = WC()->customer->get_shipping_address_2();
							$data['shipping_city']       = WC()->customer->get_shipping_city();
							$data['shipping_state']      = WC()->customer->get_shipping_state();
							$data['shipping_postcode']   = WC()->customer->get_shipping_postcode();
						}
					}

					// Process checkout
					$order_id = $checkout->create_order( $data );

					if ( is_wp_error( $order_id ) ) {
						wp_send_json_error( array( 'message' => $order_id->get_error_message() ), 400 );
					}

					if ( ! $order_id ) {
						wp_send_json_error( array( 'message' => 'Failed to create order' ), 500 );
					}

					// Get the order
					$order = wc_get_order( $order_id );
					if ( ! $order ) {
						wp_send_json_error( array( 'message' => 'Order not found after creation' ), 500 );
					}

					// Set payment method
					$order->set_payment_method( $this );
					$order->save();

					// Now generate the proper payload with real order ID
					require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'helpers/class-xmoney-payments-helper-processor.php';
					require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'helpers/class-xmoney-payments-helper-notify.php';

					$config     = Xmoney_Payments_Helper_Processor::get_configuration();
					$is_live    = ! empty( $config['is_live'] );
					$public_key = $config['site_id'];
					$secret_key = $config['secret_key'];

					// Get order data
					$order_data_obj = $order->get_data();
					$customer_id    = get_current_user_id();

					// Generate customer identifier
					$site_hash = substr( md5( get_site_url() ), 0, 8 );
					if ( $customer_id ) {
						$customer_identifier = sprintf( 'site%s_user_%d', $site_hash, $customer_id );
					} else {
						$customer_identifier = sprintf( 'site%s_guest_%s', $site_hash, uniqid() );
					}

					// Build customer array
					$customer = array(
						'identifier' => $customer_identifier,
						'firstName'  => $order_data_obj['billing']['first_name'],
						'lastName'   => $order_data_obj['billing']['last_name'],
						'country'    => $order_data_obj['billing']['country'],
						'city'       => $order_data_obj['billing']['city'],
						'address'    => $order_data_obj['billing']['address_1'],
						'zipCode'    => $order_data_obj['billing']['postcode'],
						'phone'      => Xmoney_Payments_Helper_Processor::format_phone( $order_data_obj['billing']['phone'] ),
						'email'      => $order_data_obj['billing']['email'],
					);

					// Build items array
					$items = array();
					foreach ( $order->get_items() as $item ) {
						$items[] = array(
							'item'      => $item->get_name(),
							'units'     => $item->get_quantity(),
							'unitPrice' => floatval( $item->get_subtotal() / $item->get_quantity() ),
						);
					}

					// Build order data
					$order_data = array(
						'siteId'              => $public_key,
						'customer'            => $customer,
						'order'               => array(
							'orderId'  => strval( $order_id ),
							'type'     => 'purchase',
							'amount'   => floatval( $order->get_total() ),
							'currency' => $order->get_currency(),
							'items'    => $items,
						),
						'cardTransactionMode' => 'authAndCapture',
						'invoiceEmail'        => '',
						'backUrl'             => $order->get_checkout_order_received_url(),
					);

					// Add publicKey for inline checkout
					$order_data['publicKey'] = $public_key;

					// Generate payload and checksum
					$payload  = Xmoney_Payments_Helper_Notify::get_base64_json_request( $order_data );
					$checksum = Xmoney_Payments_Helper_Notify::get_base64_checksum( $order_data, $secret_key );

					// Return order info with new payload
					wp_send_json_success(
						array(
							'orderId'    => $order_id,
							'payload'    => $payload,
							'checksum'   => $checksum,
							'confirmUrl' => esc_url_raw( rest_url( 'xmoney/v1/inline/confirm' ) ),
							'restNonce'  => wp_create_nonce( 'wp_rest' ),
						)
					);

				} catch ( Exception $e ) {
					wp_send_json_error( array( 'message' => $e->getMessage() ), 500 );
				}
			}

			/**
			 * AJAX handler to create a draft order when payment method is selected
			 * This allows showing the iframe immediately without clicking "Place Order"
			 *
			 * @return void
			 */
			public function ajax_create_draft_order() {
				try {
					// Verify nonce
					if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'woocommerce-process_checkout' ) ) {
						wp_send_json_error( array( 'message' => 'Invalid nonce' ), 403 );
						return;
					}

					// Check if cart is empty
					if ( WC()->cart->is_empty() ) {
						wp_send_json_error( array( 'message' => 'Cart is empty' ), 400 );
						return;
					}

					// Check for existing draft order in session
					$draft_order_id = WC()->session->get( 'xmoney_draft_order_id' );

					if ( $draft_order_id ) {
						$existing_order = wc_get_order( $draft_order_id );
						if ( $existing_order && $existing_order->has_status( array( 'pending', 'failed' ) ) ) {
							// Always delete old draft orders to ensure fresh data
							error_log( '[XMoney Draft] Deleting old draft order: ' . $draft_order_id );
							wp_delete_post( $draft_order_id, true );
						}
						$draft_order_id = null;
					}

					// Always create a fresh draft order with current cart data and fresh session
					{
						// Create order from cart
						$checkout = WC()->checkout();

						// Get posted data
						$data = array();
					if ( isset( $_POST['billing_first_name'] ) ) {
						foreach ( $_POST as $key => $value ) {
							if ( strpos( $key, 'billing_' ) === 0 || strpos( $key, 'shipping_' ) === 0 ) {
								$data[ $key ] = sanitize_text_field( wp_unslash( $value ) );
							}
						}
					}

						// Create the order
						$order = wc_create_order( array( 'status' => 'pending' ) );

						// Add cart items to order
					foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
						$product = $cart_item['data'];
						$order->add_product( $product, $cart_item['quantity'] );
					}

						// Set billing/shipping address if provided
					if ( ! empty( $data ) ) {
						$order->set_address( $data, 'billing' );
						if ( isset( $data['ship_to_different_address'] ) ) {
							$order->set_address( $data, 'shipping' );
						}
					} else {
						// Use customer data if logged in
						if ( is_user_logged_in() ) {
							$customer = new WC_Customer( get_current_user_id() );
							$order->set_address(
								array(
									'first_name' => $customer->get_billing_first_name(),
									'last_name'  => $customer->get_billing_last_name(),
									'email'      => $customer->get_billing_email(),
									'phone'      => $customer->get_billing_phone(),
									'address_1'  => $customer->get_billing_address_1(),
									'city'       => $customer->get_billing_city(),
									'postcode'   => $customer->get_billing_postcode(),
									'country'    => $customer->get_billing_country(),
								),
								'billing'
							);
						}
					}

						// Calculate totals
						$order->calculate_totals();

						// Set payment method
						$order->set_payment_method( $this );

						// Add order note
						$order->add_order_note( __( 'Draft order created for inline payment initialization', 'xmoney-payments' ) );

						// Save order
						$order->save();

						$draft_order_id = $order->get_id();

						// Store in session
						WC()->session->set( 'xmoney_draft_order_id', $draft_order_id );

						error_log( '[XMoney Draft] Created new draft order: ' . $draft_order_id );
					}

					// Generate payment data for this order
					require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'helpers/class-xmoney-payments-helper-processor.php';
					require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'helpers/class-xmoney-payments-helper-notify.php';

					$order      = wc_get_order( $draft_order_id );
					$config     = Xmoney_Payments_Helper_Processor::get_configuration();
					$is_live    = ! empty( $config['is_live'] );
					$secret_key = $config['secret_key'];
					$public_key = $config['site_id'];
					$data       = $order->get_data();

					// Build customer data
					$site_hash           = substr( md5( get_site_url() ), 0, 8 );
					$current_user_id     = get_current_user_id();
					$customer_identifier = $current_user_id
						? sprintf( 'site%s_user_%d', $site_hash, $current_user_id )
						: sprintf( 'site%s_guest_%s', $site_hash, uniqid() );

					$customer = array(
						'identifier' => $customer_identifier,
						'firstName'  => ! empty( $data['billing']['first_name'] ) ? $data['billing']['first_name'] : '',
						'lastName'   => ! empty( $data['billing']['last_name'] ) ? $data['billing']['last_name'] : '',
						'country'    => ! empty( $data['billing']['country'] ) ? $data['billing']['country'] : '',
						'city'       => ! empty( $data['billing']['city'] ) ? $data['billing']['city'] : '',
						'address'    => ! empty( $data['billing']['address_1'] ) ? $data['billing']['address_1'] : '',
						'zipCode'    => ! empty( $data['billing']['postcode'] ) ? $data['billing']['postcode'] : '',
						'phone'      => Xmoney_Payments_Helper_Processor::format_phone( $data['billing']['phone'] ),
						'email'      => $data['billing']['email'],
					);

					// Build items
					$items = array();
					foreach ( $order->get_items() as $item ) {
						$items[] = array(
							'item'      => $item['name'],
							'units'     => $item['quantity'],
							'unitPrice' => number_format( $item['subtotal'] / $item['quantity'], 2, '.', '' ),
						);
					}

					// Build order data
					$order_data = array(
						'siteId'              => $public_key,
						'customer'            => $customer,
						'order'               => array(
							'orderId'  => (string) $draft_order_id,
							'type'     => 'purchase',
							'amount'   => $data['total'],
							'currency' => $data['currency'],
							'items'    => $items,
						),
						'cardTransactionMode' => 'authAndCapture',
						'invoiceEmail'        => '',
						'backUrl'             => $order->get_checkout_order_received_url(),
						'publicKey'           => $public_key,
					);

					$request_data  = Xmoney_Payments_Helper_Notify::get_base64_json_request( $order_data );
					$checksum      = Xmoney_Payments_Helper_Notify::get_base64_checksum( $order_data, $secret_key );
					$session_token = Xmoney_Payments_Helper_Processor::get_session_token( $is_live, $secret_key );

					// Get saved card for logged-in users
					$user_id    = get_current_user_id();
					$saved_card = $user_id ? get_user_meta( $user_id, '_xmoney_saved_card', true ) : null;

					// Build response with saved card support
					$response = array(
						'orderId'      => $draft_order_id,
						'payload'      => $request_data,
						'checksum'     => $checksum,
						'publicKey'    => $public_key,
						'sessionToken' => $session_token,
						'confirmUrl'   => esc_url_raw( rest_url( 'xmoney/v1/inline/confirm' ) ),
						'restNonce'    => wp_create_nonce( 'wp_rest' ),
						'options'      => array(
							'displaySaveCardOption' => $user_id ? true : false,
							'displayCardHolderName' => false,
							'enableSavedCards'      => false,
						),
					);

                    $response['options']['displayCardHolderName'] = true;
                    // Add saved card data if available
                    if ( $session_token && $saved_card && ! empty( $saved_card['customer_id'] ) ) {
						$response['options']['enableSavedCards']      = true;
						$response['userId']                           = $saved_card['customer_id'];
					}

					// Return payment data
					wp_send_json_success( $response );

				} catch ( Exception $e ) {
					error_log( '[XMoney Draft] Error: ' . $e->getMessage() );
					wp_send_json_error( array( 'message' => $e->getMessage() ), 500 );
				}
			}

			/**
			 * AJAX handler to update draft order address when checkout fields change
			 * Just silently updates the order - don't touch the payment session
			 *
			 * @return void
			 */
			public function ajax_update_draft_order() {
				try {
					// Verify nonce
					if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'woocommerce-process_checkout' ) ) {
						wp_send_json_error( array( 'message' => 'Invalid nonce' ), 403 );
						return;
					}

					// Get order ID
					if ( empty( $_POST['order_id'] ) ) {
						wp_send_json_error( array( 'message' => 'Missing order ID' ), 400 );
						return;
					}

					$order_id = intval( $_POST['order_id'] );
					$order    = wc_get_order( $order_id );

					if ( ! $order || ! $order->has_status( array( 'pending', 'failed' ) ) ) {
						wp_send_json_error( array( 'message' => 'Invalid order' ), 400 );
						return;
					}

					// Update billing address
					$billing_data   = array();
					$billing_fields = array( 'first_name', 'last_name', 'email', 'phone', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country' );

					foreach ( $billing_fields as $field ) {
						$key = 'billing_' . $field;
						if ( isset( $_POST[ $key ] ) ) {
							$billing_data[ $field ] = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
						}
					}

					if ( ! empty( $billing_data ) ) {
						$order->set_address( $billing_data, 'billing' );
					}

					// Update shipping address if provided
					if ( isset( $_POST['ship_to_different_address'] ) && $_POST['ship_to_different_address'] === '1' ) {
						$shipping_data   = array();
						$shipping_fields = array( 'first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country' );

						foreach ( $shipping_fields as $field ) {
							$key = 'shipping_' . $field;
							if ( isset( $_POST[ $key ] ) ) {
								$shipping_data[ $field ] = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
							}
						}

						if ( ! empty( $shipping_data ) ) {
							$order->set_address( $shipping_data, 'shipping' );
						}
					}

					// Recalculate totals in case address affects tax
					$order->calculate_totals();
					$order->save();

					error_log( '[XMoney Draft] Silently updated order address: ' . $order_id );

					wp_send_json_success(
						array(
							'message' => 'Order updated',
						)
					);

				} catch ( Exception $e ) {
					error_log( '[XMoney Draft Update] Error: ' . $e->getMessage() );
					wp_send_json_error( array( 'message' => $e->getMessage() ), 500 );
				}
			}
		}
	}
}
add_action( 'plugins_loaded', 'xmoney_payments_init_gateway_class' );


/**
 * Add the Xmoney Payments gateway class.
 *
 * @param array $methods Existing payment methods.
 * @return array
 */
function xmoney_payments_add_gateway_class( $methods ): array {
	if ( class_exists( 'WooCommerce' ) ) {
		$methods[] = 'WC_Gateway_Xmoney_Payments_Gateway';
		return $methods;
	}
	return array();
}
add_filter( 'woocommerce_payment_gateways', 'xmoney_payments_add_gateway_class' );


/**
 * Xmoney Payments Prepare buffer functions
 *
 * This function will prepare the buffer in order to use wp_redirect properly
 *
 * @public
 * @return void
 */
function xmoney_payments_start_buffer_output() {
	ob_start();
}
add_action( 'init', 'xmoney_payments_start_buffer_output' );


/**
 * Custom text on the receipt page.
 */
function xmoney_payments_isa_order_received_text(): string {
	// Load languages
	$lang = explode( '-', get_bloginfo( 'language' ) );
	$lang = $lang[0];
	if ( file_exists( XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/' . $lang . '/lang.php' ) ) {
		require XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/' . $lang . '/lang.php';
	} else {
		require XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/en/lang.php';
	}

	return esc_html__( 'Thank you. Your transaction is approved.', 'xmoney-payments' );
}
add_filter( 'woocommerce_thankyou_order_received_text', 'xmoney_payments_isa_order_received_text', 10, 2 );


/**
 * Suppress email functionality.
 *
 * @param WC_Emails $email_class Email class instance.
 * @return void
 */
function xmoney_payments_unhook_woo_order_emails( $email_class ) {
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
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$xmoney_payments_suppress_email = $wpdb->get_row( 'SELECT suppress_email FROM ' . $wpdb->prefix . 'xmoney_payments_configuration' );

if ( $xmoney_payments_suppress_email ) {
	if ( '1' === $xmoney_payments_suppress_email->suppress_email ) {
		add_action( 'woocommerce_email', 'xmoney_payments_unhook_woo_order_emails' );
	}
}


/**
 * Sends cancellation request to xMoney API when WooCommerce subscription ends.
 *
 * @param WC_Subscription $subscription Subscription object.
 * @return void
 */
function xmoney_payments_subscription_terminated( $subscription ) {
	/* Get configuration from database. */
	global $wpdb;
	$api_key = '';
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$configuration = $wpdb->get_row( 'SELECT * FROM ' . $wpdb->prefix . 'xmoney_payments_configuration' );
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$server_order_id = $wpdb->get_var(
		$wpdb->prepare(
			'SELECT orderId FROM ' . $wpdb->prefix . 'xmoney_payments_transactions WHERE id_cart = %d',
			$subscription->get_parent_id()
		)
	);
	if ( $configuration ) {
		if ( '1' === $configuration->live_mode ) {
			$api_key = $configuration->live_key;
			$url     = 'https://api.xmoney.com/order/' . sanitize_key( $server_order_id );
		} elseif ( '0' === $configuration->live_mode ) {
			$api_key = $configuration->staging_key;
			$url     = 'https://api-stage.xmoney.com/order/' . sanitize_key( $server_order_id );
		}
	}

	/* Load languages */
	$lang = explode( '-', get_bloginfo( 'language' ) )[0];
	if ( file_exists( XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/' . $lang . '/lang.php' ) ) {
		require XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/' . $lang . '/lang.php';
	} else {
		require XMONEY_PAYMENTS_PLUGIN_DIR . 'lang/en/lang.php';
	}

	$args     = array(
		'method'  => 'DELETE',
		'headers' => array(
			'accept'        => 'application/json',
			'Authorization' => $api_key,
		),
	);
	$response = wp_remote_request( $url, $args );

	if ( 'OK' === $response['response']['message'] ) {
		Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( '[RESPONSE]: Server status set for order ID: ', 'xmoney-payments' ) . esc_html( $subscription->get_parent_id() ) );
	} else {
		Xmoney_Payments_Logger::xmoney_payments_log( esc_html__( '[RESPONSE-ERROR]: Failed to set server status for order ID: ', 'xmoney-payments' ) . esc_html( $subscription->get_parent_id() ) );
	}
}
add_action( 'woocommerce_subscription_status_cancelled', 'xmoney_payments_subscription_terminated' );
add_action( 'woocommerce_subscription_status_expired', 'xmoney_payments_subscription_terminated' );
