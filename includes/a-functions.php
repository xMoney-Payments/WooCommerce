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


/** Get Inline Checkout setting (Yes/No) */
function xmoney_payments_get_inline_checkout() {
	global $wpdb;
	$table_name = esc_sql( $wpdb->prefix . 'xmoney_payments_configuration' );
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is escaped manually and safe.
	$row   = $wpdb->get_row( "SELECT inline_checkout FROM {$table_name} LIMIT 1", ARRAY_A );
	$value = isset( $row['inline_checkout'] ) ? (int) $row['inline_checkout'] : 0;

	$html  = '<select name="inline_checkout" id="inline_checkout" class="regular-text">';
	$html .= '<option value="1"' . selected( $value, 1, false ) . '>' . esc_html__( 'Yes', 'xmoney-payments' ) . '</option>';
	$html .= '<option value="0"' . selected( $value, 0, false ) . '>' . esc_html__( 'No', 'xmoney-payments' ) . '</option>';
	$html .= '</select>';

	return $html;
}

/** Convenience: check if inline is enabled */
function xmoney_payments_is_inline_enabled() {
	global $wpdb;
	$table_name = esc_sql( $wpdb->prefix . 'xmoney_payments_configuration' );
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is escaped manually and safe.
	$val = $wpdb->get_var( "SELECT inline_checkout FROM {$table_name} LIMIT 1" );
	return (int) $val === 1;
}

/** Get Enable Saved Cards setting (Yes/No) */
function xmoney_payments_get_enable_saved_cards() {
	global $wpdb;
	$table_name = esc_sql( $wpdb->prefix . 'xmoney_payments_configuration' );
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is escaped manually and safe.
	$row   = $wpdb->get_row( "SELECT enable_saved_cards FROM {$table_name} LIMIT 1", ARRAY_A );
	$value = isset( $row['enable_saved_cards'] ) ? (int) $row['enable_saved_cards'] : 0;

	$html  = '<select name="enable_saved_cards" id="enable_saved_cards" class="regular-text">';
	$html .= '<option value="1"' . selected( $value, 1, false ) . '>' . esc_html__( 'Yes', 'xmoney-payments' ) . '</option>';
	$html .= '<option value="0"' . selected( $value, 0, false ) . '>' . esc_html__( 'No', 'xmoney-payments' ) . '</option>';
	$html .= '</select>';

	return $html;
}

/** Convenience: check if saved cards are enabled */
function xmoney_payments_is_saved_cards_enabled() {
	global $wpdb;
	$table_name = esc_sql( $wpdb->prefix . 'xmoney_payments_configuration' );
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is escaped manually and safe.
	$val = $wpdb->get_var( "SELECT enable_saved_cards FROM {$table_name} LIMIT 1" );
	return (int) $val === 1;
}

/** Get Checkout Theme setting (light/dark/custom) */
function xmoney_payments_get_checkout_theme_select() {
	global $wpdb;
	$table_name = esc_sql( $wpdb->prefix . 'xmoney_payments_configuration' );
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is escaped manually and safe.
	$row   = $wpdb->get_row( "SELECT checkout_theme FROM {$table_name} LIMIT 1", ARRAY_A );
	$value = isset( $row['checkout_theme'] ) ? $row['checkout_theme'] : 'light';

	$html  = '<select name="checkout_theme" id="checkout_theme" class="regular-text">';
	$html .= '<option value="light"' . selected( $value, 'light', false ) . '>' . esc_html__( 'Light', 'xmoney-payments' ) . '</option>';
	$html .= '<option value="dark"' . selected( $value, 'dark', false ) . '>' . esc_html__( 'Dark', 'xmoney-payments' ) . '</option>';
	$html .= '<option value="custom"' . selected( $value, 'custom', false ) . '>' . esc_html__( 'Custom', 'xmoney-payments' ) . '</option>';
	$html .= '</select>';

	return $html;
}

/** Get the checkout theme value */
function xmoney_payments_get_checkout_theme() {
	global $wpdb;
	$table_name = esc_sql( $wpdb->prefix . 'xmoney_payments_configuration' );
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is escaped manually and safe.
	$val = $wpdb->get_var( "SELECT checkout_theme FROM {$table_name} LIMIT 1" );
	return ! empty( $val ) ? $val : 'light';
}

/** Get hardcoded fallback theme variables */
function xmoney_payments_get_hardcoded_theme_variables() {
	return array(
		'colorPrimary'         => '#009688',
		'colorDanger'          => '#e53935',
		'colorBackground'      => '#f5f5f5',
		'colorText'            => '#212121',
		'colorTextSecondary'   => '#757575',
		'colorBorder'          => '#e0e0e0',
		'colorBorderFocus'     => '#009688',
		'colorTextPlaceholder' => '#bdbdbd',
		'colorBackgroundFocus' => '#ffffff',
		'borderRadius'         => '4px',
	);
}

/** Check if a hex color is light (brightness > 128) */
function xmoney_payments_is_light_color( $hex ) {
	$hex = ltrim( $hex, '#' );

	if ( strlen( $hex ) === 3 ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}

	$r = hexdec( substr( $hex, 0, 2 ) );
	$g = hexdec( substr( $hex, 2, 2 ) );
	$b = hexdec( substr( $hex, 4, 2 ) );

	// Calculate perceived brightness (human eye is more sensitive to green)
	$brightness = ( ( $r * 299 ) + ( $g * 587 ) + ( $b * 114 ) ) / 1000;

	return $brightness > 128;
}

/** Try to get colors from the active WordPress theme */
function xmoney_payments_get_theme_colors_from_wp() {
	$colors = array();

	// Collect base/contrast colors to analyze their brightness later
	$base_color     = null;
	$contrast_color = null;

	// Try to get colors from theme.json (block themes like Twenty Twenty-Four)
	if ( function_exists( 'wp_get_global_settings' ) ) {
		$settings = wp_get_global_settings();

		// Get color palette
		if ( ! empty( $settings['color']['palette']['theme'] ) ) {
			$palette = $settings['color']['palette']['theme'];

			foreach ( $palette as $color ) {
				$slug = strtolower( $color['slug'] ?? '' );
				$hex  = $color['color'] ?? '';

				if ( empty( $hex ) ) {
					continue;
				}

				// Collect base/contrast for brightness analysis
				if ( 'base' === $slug ) {
					$base_color = $hex;
				} elseif ( 'contrast' === $slug ) {
					$contrast_color = $hex;
				}

				// Map explicit color slugs
				if ( in_array( $slug, array( 'primary', 'accent', 'vivid-cyan-blue', 'vivid-purple' ), true ) ) {
					$colors['colorPrimary']     = $hex;
					$colors['colorBorderFocus'] = $hex;
				} elseif ( 'secondary' === $slug && empty( $colors['colorPrimary'] ) ) {
					$colors['colorPrimary']     = $hex;
					$colors['colorBorderFocus'] = $hex;
				} elseif ( in_array( $slug, array( 'background', 'white' ), true ) ) {
					$colors['colorBackground']      = $hex;
					$colors['colorBackgroundFocus'] = $hex;
				} elseif ( in_array( $slug, array( 'foreground', 'black' ), true ) ) {
					$colors['colorText'] = $hex;
				} elseif ( in_array( $slug, array( 'vivid-red', 'luminous-vivid-orange' ), true ) ) {
					$colors['colorDanger'] = $hex;
				}
			}
		}

		// Analyze base/contrast colors by brightness (not by name)
		// In block themes: base = background, contrast = text (usually)
		// But we verify by checking which one is actually lighter
		if ( $base_color && $contrast_color ) {
			$base_is_light     = xmoney_payments_is_light_color( $base_color );
			$contrast_is_light = xmoney_payments_is_light_color( $contrast_color );

			// Assign the lighter color as background, darker as text
			if ( $base_is_light && ! $contrast_is_light ) {
				// Standard: base is light (background), contrast is dark (text)
				if ( empty( $colors['colorBackground'] ) ) {
					$colors['colorBackground']      = $base_color;
					$colors['colorBackgroundFocus'] = $base_color;
				}
				if ( empty( $colors['colorText'] ) ) {
					$colors['colorText'] = $contrast_color;
				}
			} elseif ( ! $base_is_light && $contrast_is_light ) {
				// Inverted: base is dark, contrast is light - swap them
				if ( empty( $colors['colorBackground'] ) ) {
					$colors['colorBackground']      = $contrast_color;
					$colors['colorBackgroundFocus'] = $contrast_color;
				}
				if ( empty( $colors['colorText'] ) ) {
					$colors['colorText'] = $base_color;
				}
			} elseif ( $base_is_light ) {
				// Both are light - use base as background
				if ( empty( $colors['colorBackground'] ) ) {
					$colors['colorBackground']      = $base_color;
					$colors['colorBackgroundFocus'] = $base_color;
				}
			} else {
				// Both are dark - use contrast as background (it might be slightly lighter)
				if ( empty( $colors['colorBackground'] ) ) {
					$colors['colorBackground']      = $contrast_color;
					$colors['colorBackgroundFocus'] = $contrast_color;
				}
			}
		} elseif ( $base_color && empty( $colors['colorBackground'] ) ) {
			// Only base exists - check if it's light enough for background
			if ( xmoney_payments_is_light_color( $base_color ) ) {
				$colors['colorBackground']      = $base_color;
				$colors['colorBackgroundFocus'] = $base_color;
			} else {
				$colors['colorText'] = $base_color;
			}
		} elseif ( $contrast_color && empty( $colors['colorText'] ) ) {
			// Only contrast exists - check brightness
			if ( ! xmoney_payments_is_light_color( $contrast_color ) ) {
				$colors['colorText'] = $contrast_color;
			} else {
				$colors['colorBackground']      = $contrast_color;
				$colors['colorBackgroundFocus'] = $contrast_color;
			}
		}

		// Try to get text and background from global styles (these override palette)
		if ( ! empty( $settings['color']['text'] ) ) {
			$colors['colorText'] = $settings['color']['text'];
		}
		if ( ! empty( $settings['color']['background'] ) ) {
			$colors['colorBackground'] = $settings['color']['background'];
		}
	}

	// Try theme mods (classic themes)
	$background_color = get_theme_mod( 'background_color' );
	if ( ! empty( $background_color ) && empty( $colors['colorBackground'] ) ) {
		$colors['colorBackground'] = '#' . ltrim( $background_color, '#' );
	}

	// Try customizer accent/link color
	$accent_color = get_theme_mod( 'accent_color' );
	if ( ! empty( $accent_color ) && empty( $colors['colorPrimary'] ) ) {
		$colors['colorPrimary']     = $accent_color;
		$colors['colorBorderFocus'] = $accent_color;
	}

	// Try WooCommerce colors if available
	$wc_primary = get_option( 'woocommerce_colors' );
	if ( is_array( $wc_primary ) && ! empty( $wc_primary['primary'] ) && empty( $colors['colorPrimary'] ) ) {
		$colors['colorPrimary']     = $wc_primary['primary'];
		$colors['colorBorderFocus'] = $wc_primary['primary'];
	}

	// Generate secondary colors based on primary colors if we have them
	if ( ! empty( $colors['colorText'] ) && empty( $colors['colorTextSecondary'] ) ) {
		$colors['colorTextSecondary']   = xmoney_payments_adjust_color_brightness( $colors['colorText'], 40 );
		$colors['colorTextPlaceholder'] = xmoney_payments_adjust_color_brightness( $colors['colorText'], 60 );
	}

	if ( ! empty( $colors['colorBackground'] ) && empty( $colors['colorBorder'] ) ) {
		$colors['colorBorder'] = xmoney_payments_adjust_color_brightness( $colors['colorBackground'], -15 );
	}

	return $colors;
}

/** Adjust color brightness (positive = lighter, negative = darker) */
function xmoney_payments_adjust_color_brightness( $hex, $percent ) {
	$hex = ltrim( $hex, '#' );

	if ( strlen( $hex ) === 3 ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}

	$r = hexdec( substr( $hex, 0, 2 ) );
	$g = hexdec( substr( $hex, 2, 2 ) );
	$b = hexdec( substr( $hex, 4, 2 ) );

	$r = max( 0, min( 255, $r + ( $r * $percent / 100 ) ) );
	$g = max( 0, min( 255, $g + ( $g * $percent / 100 ) ) );
	$b = max( 0, min( 255, $b + ( $b * $percent / 100 ) ) );

	return sprintf( '#%02x%02x%02x', $r, $g, $b );
}

/** Get default theme variables (from WP theme or hardcoded fallback) */
function xmoney_payments_get_default_theme_variables() {
	$hardcoded  = xmoney_payments_get_hardcoded_theme_variables();
	$from_theme = xmoney_payments_get_theme_colors_from_wp();

	// Merge: theme colors override hardcoded, but only if they exist
	return array_merge( $hardcoded, array_filter( $from_theme ) );
}

/** Get theme variables from database */
function xmoney_payments_get_theme_variables() {
	global $wpdb;
	$table_name = esc_sql( $wpdb->prefix . 'xmoney_payments_configuration' );
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is escaped manually and safe.
	$val = $wpdb->get_var( "SELECT theme_variables FROM {$table_name} LIMIT 1" );

	$defaults = xmoney_payments_get_default_theme_variables();

	if ( ! empty( $val ) ) {
		$saved = json_decode( $val, true );
		if ( is_array( $saved ) ) {
			return array_merge( $defaults, $saved );
		}
	}

	return $defaults;
}

/** Get theme appearance config for SDK */
function xmoney_payments_get_appearance_config() {
	$theme = xmoney_payments_get_checkout_theme();

	$appearance = array(
		'theme' => $theme,
	);

	if ( 'custom' === $theme ) {
		$appearance['variables'] = xmoney_payments_get_theme_variables();
	}

	return $appearance;
}
