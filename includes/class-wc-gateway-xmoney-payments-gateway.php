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
	wp_enqueue_style( 'ma-front', XMONEY_PAYMENTS_PLUGIN_URL . 'assets/css/front.css', array(), XMONEY_PAYMENTS_VERSION, true );
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
					 * The content of the file was moved to the main twispay.php file, and hooks for the virtual page
					 * were also created.
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
					 * The content of the file was moved to the main twispay.php file, and hooks for the virtual page
					 * were also created.
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
