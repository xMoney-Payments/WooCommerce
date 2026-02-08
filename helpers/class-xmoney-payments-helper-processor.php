<?php
/**
 * Xmoney Payments Helper Processor
 *
 * Provides shared helper utilities used throughout the Xmoney Payments integration.
 *
 * @package Xmoney/Helpers
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper class for retrieving configuration and formatting request data.
 */
class Xmoney_Payments_Helper_Processor {
	const LIVE_URL         = 'https://secure.xmoney.com';
	const STAGE_URL        = 'https://secure-stage.xmoney.com';
	const INLINE_LIVE_URL  = 'https://api.xmoney.com';
	const INLINE_STAGE_URL = 'https://api-stage.xmoney.com';
	const LIVE_URL_JS      = 'https://secure.xmoney.com';
	const STAGE_URL_JS     = 'https://secure-stage.xmoney.com';

	public static function get_saved_cards( $customer_id, $secret_key ) {
		if ( empty( $customer_id ) ) {
			return array();
		}

		$config  = self::get_configuration();
		$is_live = ! empty( $config['is_live'] );

		$url = ( $is_live ? self::INLINE_LIVE_URL : self::INLINE_STAGE_URL ) . '/card?customerId=' . urlencode( $customer_id );

		$response = wp_remote_get(
			$url,
			array(
				'headers'   => array(
					'Authorization' => 'Bearer ' . sanitize_text_field( $secret_key ),
					'Content-Type'  => 'application/json',
				),
				'timeout'   => 30,
				'sslverify' => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		$response = array();

		if ( is_array( $body ) && isset( $body['data'] ) ) {
			$response = $body['data'];
		}

		return $response;
	}

	public static function delete_cards( $customer_id, $secret_key ) {
		$config  = self::get_configuration();
		$is_live = ! empty( $config['is_live'] );
			$url = ( $is_live ? self::INLINE_LIVE_URL : self::INLINE_STAGE_URL ) . '/card?customerId=' . $customer_id;

			$cards = wp_remote_get(
				esc_url( $url ),
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . sanitize_text_field( $secret_key ),
						'Content-Type'  => 'application/json',
					),
					'timeout' => 30,
				)
			);

		foreach ( json_decode( $cards['body'], 1 )['data'] as $id ) {
			$url  = ( $is_live ? self::INLINE_LIVE_URL : self::INLINE_STAGE_URL ) . '/card/' . $id['id'];
			$args = array(
				'method'  => 'DELETE',
				'headers' => array(
					'Authorization' => 'Bearer ' . sanitize_text_field( $secret_key ),
					'Content-Type'  => 'application/json',
				),
				'timeout' => 30,
			);

			$response = wp_remote_get(
				esc_url( $url ),
				$args
			);
		}

		exit;
	}

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

		$is_live   = '1' === $configuration->live_mode;
		$is_inline = '1' === $configuration->inline_checkout;

		if ( $is_live ) {
			$result['is_live']    = true;
			$result['site_id']    = ( $is_inline ? 'pk_live_' : '' ) . $configuration->live_id;
			$result['secret_key'] = $configuration->live_key;

			return $result;
		}

		$result['is_live']    = false;
		$result['site_id']    = ( $is_inline ? 'pk_test_' : '' ) . $configuration->staging_id;
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

		$table_name = esc_sql( $wpdb->prefix . 'xmoney_payments_configuration' );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are escaped manually and safe.
		return $wpdb->get_row( "SELECT * FROM {$table_name}" );
	}
}
