<?php
/**
 * Twispay Helper Processor
 *
 * Provides shared helper utilities used throughout the Twispay integration.
 *
 * @package Twispay/Helpers
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper class for retrieving configuration and formatting request data.
 */
class Twispay_TW_Helper_Processor {
	const LIVE_URL  = 'https://secure.xmoney.com';
	const STAGE_URL = 'https://secure-stage.xmoney.com';

	/**
	 * Get the current site language short code (e.g. "en", "fr").
	 *
	 * @return string Language code.
	 */
	public static function get_current_language(): string {
		return explode( '-', get_bloginfo( 'language' ) )[0];
	}

	/**
	 * Format a phone number by removing non-digit characters and prefixing "+" if needed.
	 *
	 * @param string $phone Input phone number.
	 * @return string Normalized phone number.
	 */
	public static function format_phone( $phone ): string {
		$output = '';

		if ( empty( $phone ) ) {
			return $output;
		}

		$output = $phone[0] ? '+' : '';

		return $output . preg_replace( '/([^0-9]*)+/', '', $phone );
	}

	/**
	 * Retrieve processed plugin configuration values (site ID, key, mode).
	 *
	 * @return array Configuration data structured for gateway interaction.
	 */
	public static function get_configuration(): array {
		$configuration = self::query_configuration();
		$result        = array();

		if ( null === $configuration->live_mode ) {
			return $result;
		}

		$is_live = '1' === $configuration->live_mode;

		if ( $is_live ) {
			$result['is_live']    = true;
			$result['site_id']    = $configuration->live_id;
			$result['secret_key'] = $configuration->live_key;

			return $result;
		}

		$result['is_live']    = false;
		$result['site_id']    = $configuration->staging_id;
		$result['secret_key'] = $configuration->staging_key;

		return $result;
	}

	/**
	 * Query raw configuration row from database.
	 *
	 * @return object|null Database row or null if not found.
	 */
	private static function query_configuration() {
		global $wpdb;

		$table_name = esc_sql( $wpdb->prefix . 'twispay_tw_configuration' );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are escaped manually and safe.
		return $wpdb->get_row( "SELECT * FROM {$table_name}" );
	}
}
