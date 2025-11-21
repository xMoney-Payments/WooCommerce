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

	/**
	 * Retrieve a session token for Inline Checkout.
	 *
	 * @param bool   $is_live Whether to use live or sandbox environment.
	 * @param string $secret_key The xMoney secret key.
	 * @return string|null The session token, or null on failure.
	 */
	public static function get_session_token( $is_live, $secret_key ) {
		if ( function_exists( 'xmoney_payments_is_inline_enabled' ) && xmoney_payments_is_inline_enabled() ) {
			$url = ( $is_live ? self::INLINE_LIVE_URL : self::INLINE_STAGE_URL ) . '/auth/jwt-token';
		} else {
			$url = ( $is_live ? self::LIVE_URL : self::STAGE_URL ) . '/auth/jwt-token';
		}

		$args = array(
			'headers'   => array(
				'Authorization' => 'Bearer ' . sanitize_text_field( $secret_key ),
				'Content-Type'  => 'application/json',
			),
			'timeout'   => 30,
			'sslverify' => true,
		);

		$response = wp_remote_get( esc_url_raw( $url ), $args );
		$logger   = function_exists( 'wc_get_logger' ) ? wc_get_logger() : null;

		if ( is_wp_error( $response ) ) {
			$message = esc_html__( 'xMoney API request error: ', 'xmoney-payments' ) . $response->get_error_message();
			if ( $logger ) {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$logger->error( $message, array( 'source' => 'xmoney-payments' ) );
			} else {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( $message );
			}
			return null;
		}

		$http_code = wp_remote_retrieve_response_code( $response );
		$body      = wp_remote_retrieve_body( $response );

		if ( $http_code < 200 || $http_code >= 300 ) {
			$message = sprintf(
			/* translators: 1: HTTP status code, 2: response body */
				esc_html__( 'xMoney API returned HTTP %1$d => %2$s', 'xmoney-payments' ),
				$http_code,
				$body
			);
			if ( $logger ) {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$logger->error( $message, array( 'source' => 'xmoney-payments' ) );
			} else {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( $message );
			}
			return null;
		}

		$decoded = json_decode( $body, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$message = esc_html__( 'xMoney API invalid JSON: ', 'xmoney-payments' ) . json_last_error_msg();
			if ( $logger ) {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$logger->error( $message, array( 'source' => 'xmoney-payments' ) );
			} else {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( $message );
			}
			return null;
		}

		if ( isset( $decoded['data']['token'] ) ) {
			return sanitize_text_field( $decoded['data']['token'] );
		}

		if ( isset( $decoded['token'] ) ) {
			return sanitize_text_field( $decoded['token'] );
		}

		$message = esc_html__( 'xMoney API response missing token field: ', 'xmoney-payments' ) . wp_json_encode( $decoded );
		if ( $logger ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$logger->warning( $message, array( 'source' => 'xmoney-payments' ) );
		} else {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( $message );
		}

		return null;
	}

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
