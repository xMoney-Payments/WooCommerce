<?php
/**
 * Xmoney Payments Custom Functions
 *
 * Here stand all Xmoney Payments Functions
 *
 * @package  Xmoney/Admin
 * @category Admin
 * @author   Xmoney_Payments
 */
/* Exit if the file is accessed directly. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retrieves Live Mode options from Configuration Panel
 *
 * @public
 * @return string Html with all Live Mode options
 */
function xmoney_payments_get_live_mode(): string {
	// WordPress database reference
	global $wpdb;
	$html       = '';
	$table_name = esc_sql( $wpdb->prefix . 'xmoney_payments_configuration' );

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are escaped manually and safe.
	$live_mode = $wpdb->get_results( "SELECT live_mode FROM {$table_name}" );

	if ( $live_mode ) {
		$html .= '<select name="live_mode" id="live_mode">';
		foreach ( $live_mode as $e_l ) {
			if ( '1' === $e_l->live_mode ) {
				$html .= '<option value="1" selected>' . esc_html__( 'Yes', 'xmoney-payments' ) . '</option>';
				$html .= '<option value="0">' . esc_html__( 'No', 'xmoney-payments' ) . '</option>';
			} else {
				$html .= '<option value="1">' . esc_html__( 'Yes', 'xmoney-payments' ) . '</option>';
				$html .= '<option value="0" selected>' . esc_html__( 'No', 'xmoney-payments' ) . '</option>';
			}

			break;
		}
		$html .= '</select>';

	} else {
		// If by any chance the configuration row does not exist, add default one immediately. ( tw_configuration table )
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$table_name,
			array(
				'live_mode' => 0,
			)
		);

		// Now display the default form
		$html .= '<select name="live_mode" id="live_mode">';
		$html .= '<option value="1">' . esc_html__( 'Yes', 'xmoney-payments' ) . '</option>';
		$html .= '<option value="0" selected>' . esc_html__( 'No', 'xmoney-payments' ) . '</option>';
		$html .= '</select>';

	}
	return $html;
}

/**
 * Retrieves a list of allowed html tags to use with wp_kses function
 *
 * @public
 * @return array Array with all the allowed tags
 */
function xmoney_payments_allowed_tags(): array {
	return array(
		'div'    => array(
			'class' => array(),
			'id'    => array(),
		),
		'select' => array(
			'name' => array(),
			'id'   => array(),
		),
		'option' => array(
			'value'    => array(),
			'class'    => array(),
			'selected' => array(),
		),
		'li'     => array(
			'class' => array(),
		),
		'a'      => array(
			'href'  => array(),
			'id'    => array(),
			'class' => array(),
		),
		'span'   => array(
			'class' => array(),
		),
		'td'     => array(
			'scope'        => array(),
			'id'           => array(),
			'class'        => array(),
			'data-colname' => array(),
		),
		'th'     => array(
			'scope' => array(),
			'id'    => array(),
			'class' => array(),
		),
		'input'  => array(
			'id'    => array(),
			'class' => array(),
			'type'  => array(),
		),
		'label'  => array(
			'class' => array(),
			'for'   => array(),
		),
		'button' => array(
			'class' => array(),
			'type'  => array(),
		),
	);
}

/**
 * Retrieves Suppress Email options from Configuration Panel
 *
 * @public
 * @return string Html with all Suppress Email options
 */
function xmoney_payments_get_suppress_email(): string {
	// WordPress database reference
	global $wpdb;
	$html       = '';
	$table_name = esc_sql( $wpdb->prefix . 'xmoney_payments_configuration' );

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are escaped manually and safe.
	$suppress_email = $wpdb->get_results( "SELECT suppress_email FROM {$table_name}" );

	if ( $suppress_email ) {
		$html .= '<select name="suppress_email" id="suppress_email">';
		foreach ( $suppress_email as $e_s ) {
			if ( '1' === $e_s->suppress_email ) {
				$html .= '<option value="1" selected>' . esc_html__( 'Yes', 'xmoney-payments' ) . '</option>';
				$html .= '<option value="0">' . esc_html__( 'No', 'xmoney-payments' ) . '</option>';
			} else {
				$html .= '<option value="1">' . esc_html__( 'Yes', 'xmoney-payments' ) . '</option>';
				$html .= '<option value="0" selected>' . esc_html__( 'No', 'xmoney-payments' ) . '</option>';
			}

			break;
		}
		$html .= '</select>';

	}
	return $html;
}

/**
 * Retrieves all WordPress Pages for configuring Thank you redirect
 *
 * @public
 * @return string Html with all WordPress Pages options
 */
function xmoney_payments_get_wp_pages(): string {
	// WordPress database reference
	global $wpdb;
	$html             = '';
	$table_name       = esc_sql( $wpdb->prefix . 'xmoney_payments_configuration' );
	$posts_table_name = esc_sql( $wpdb->posts );

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are escaped manually and safe.
	$configuration = $wpdb->get_results( "SELECT thankyou_page FROM {$table_name}" );
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are escaped manually and safe.
	$wp_pages = $wpdb->get_results( "SELECT post_title, guid FROM {$posts_table_name} WHERE post_type = 'page' AND post_status = 'publish' " );

	if ( $wp_pages ) {
		$html .= '<select name="wp_pages" id="wp_pages">';
		$html .= '<option value="0">' . esc_html__( 'Default', 'xmoney-payments' ) . '</option>';

		foreach ( $wp_pages as $e_p ) {
			if ( 'Xmoney Payments confirmation' !== $e_p->post_title ) {
				if ( $configuration ) {
					foreach ( $configuration as $e_c ) {
						$html .= '<option value="' . esc_attr( $e_p->guid ) . '"' . selected( $e_c->thankyou_page, $e_p->guid, false ) . ' >' . esc_html( $e_p->post_title ) . '</option>';

						break;
					}
				}
			}
		}
		$html .= '</select>';

	}
	return $html;
}

/**
 * Retrieves Contact email on the current Shop
 *
 * @public
 * @return string contact_email
 */
function xmoney_payments_get_contact_email_o(): string {
	// WordPress database reference
	global $wpdb;
	$table_name = esc_sql( $wpdb->prefix . 'xmoney_payments_configuration' );

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are escaped manually and safe.
	$contact_email = $wpdb->get_results( "SELECT contact_email FROM {$table_name}" );

	if ( $contact_email ) {
		return $contact_email[0]->contact_email;
	} else {
		return '';
	}
}

/**
 * Retrieves Staging Site ID on the current Shop
 *
 * @public
 * @return string staging_id
 */
function xmoney_payments_get_staging_site_id(): string {
	// WordPress database reference
	global $wpdb;
	$table_name = esc_sql( $wpdb->prefix . 'xmoney_payments_configuration' );

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are escaped manually and safe.
	$staging_id = $wpdb->get_results( "SELECT staging_id FROM {$table_name}" );

	if ( $staging_id ) {
		return $staging_id[0]->staging_id;
	} else {
		return '';
	}
}

/**
 * Retrieves Staging Private Key on the current Shop
 *
 * @public
 * @return string staging_key
 */
function xmoney_payments_get_staging_private_key(): string {
	// WordPress database refference
	global $wpdb;
	$table_name = esc_sql( $wpdb->prefix . 'xmoney_payments_configuration' );

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are escaped manually and safe.
	$staging_key = $wpdb->get_results( "SELECT staging_key FROM {$table_name}" );

	if ( $staging_key ) {
		return $staging_key[0]->staging_key;
	} else {
		return '';
	}
}

/**
 * Retrieves Live Site ID on the current Shop
 *
 * @public
 * @return string live_id
 */
function xmoney_payments_get_live_site_id(): string {
	// WordPress database refference
	global $wpdb;
	$table_name = esc_sql( $wpdb->prefix . 'xmoney_payments_configuration' );

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are escaped manually and safe.
	$live_id = $wpdb->get_results( "SELECT live_id FROM {$table_name}" );

	if ( $live_id ) {
		return $live_id[0]->live_id;
	} else {
		return '';
	}
}

/**
 * Retrieves Live Private Key on the current Shop
 *
 * @public
 * @return string live_key
 */
function xmoney_payments_get_live_private_key(): string {
	// WordPress database refference
	global $wpdb;
	$table_name = esc_sql( $wpdb->prefix . 'xmoney_payments_configuration' );

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are escaped manually and safe.
	$live_key = $wpdb->get_results( "SELECT live_key FROM {$table_name}" );

	if ( $live_key ) {
		return $live_key[0]->live_key;
	} else {
		return '';
	}
}
