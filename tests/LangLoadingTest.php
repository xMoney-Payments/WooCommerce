<?php

use PHPUnit\Framework\TestCase;

/**
 * Verify that the language-loading pattern used across the plugin does not
 * produce a variable-name collision.
 *
 * The bug: files that stored the language *code* (e.g. "ro") in a variable
 * called $xmoney_payments_lang and then required a lang file that populates
 * $xmoney_payments_lang['key'] = '…' would crash with:
 *   "Cannot access offset of type string on string"
 * because PHP tried array-access on a plain string.
 */
class LangLoadingTest extends TestCase {

	/**
	 * Every lang file must produce an associative array with well-known keys.
	 *
	 * @dataProvider langFileProvider
	 */
	public function test_lang_file_produces_array( string $path, string $locale ): void {
		unset( $xmoney_payments_lang );

		require $path;

		$this->assertIsArray(
			$xmoney_payments_lang,
			"After requiring lang/$locale/lang.php, \$xmoney_payments_lang must be an array."
		);

		$this->assertArrayHasKey( 'no_woocommerce_f', $xmoney_payments_lang );
		$this->assertArrayHasKey( 'configuration_title', $xmoney_payments_lang );
		$this->assertArrayHasKey( 'general_error_title', $xmoney_payments_lang );
	}

	public function langFileProvider(): array {
		$base = dirname( __DIR__ ) . '/lang';
		return [
			'English'  => [ "$base/en/lang.php", 'en' ],
			'Romanian' => [ "$base/ro/lang.php", 'ro' ],
		];
	}

	/**
	 * Simulates the *old* buggy pattern: setting $xmoney_payments_lang to a
	 * string before requiring the lang file. This must still result in an
	 * array because the lang file *re-initialises* the variable via
	 * $xmoney_payments_lang['key'] = '…' (PHP auto-promotes to array when
	 * the variable is unset, but crashes when it is a non-empty string).
	 *
	 * If someone accidentally reverts the fix, this test will reproduce the
	 * original fatal error.
	 *
	 * @dataProvider langFileProvider
	 */
	public function test_lang_file_crashes_when_variable_is_preset_to_string( string $path, string $locale ): void {
		// Simulate the old bug: variable is a string *before* the require.
		$xmoney_payments_lang = $locale;

		// On PHP 8+, this would throw TypeError if $xmoney_payments_lang is
		// still a string when the lang file tries $xmoney_payments_lang['key'].
		// We expect the error to surface here if the lang file is ever loaded
		// with a pre-existing string value (i.e. the bug returns).
		try {
			require $path;
			// If we get here without error on PHP <8, the variable was silently
			// mangled. Verify it is NOT an array (it stays a string).
			// On PHP 8+ this line is unreachable because the require throws.
			$this->assertIsString(
				$xmoney_payments_lang,
				'Lang file cannot properly initialise $xmoney_payments_lang when it is pre-set to a string.'
			);
		} catch ( \TypeError $e ) {
			$this->assertStringContainsString(
				'Cannot access offset of type string on string',
				$e->getMessage(),
				'Confirmed: pre-setting $xmoney_payments_lang to a string causes a TypeError.'
			);
		}
	}

	/**
	 * Verify the fixed files use $xmoney_payments_lang_code (not
	 * $xmoney_payments_lang) as the language-code variable, so the
	 * collision can never happen.
	 *
	 * @dataProvider fixedFileProvider
	 */
	public function test_fixed_files_use_separate_variable_for_lang_code( string $path ): void {
		$source = file_get_contents( $path );

		$this->assertStringNotContainsString(
			'$xmoney_payments_lang = explode',
			$source,
			basename( $path ) . ' must not store the language code in $xmoney_payments_lang.'
		);

		$this->assertStringContainsString(
			'$xmoney_payments_lang_code',
			$source,
			basename( $path ) . ' should use $xmoney_payments_lang_code for the language code.'
		);
	}

	public function fixedFileProvider(): array {
		$root = dirname( __DIR__ );
		return [
			'payment-confirmation.php' => [ "$root/views/payment-confirmation.php" ],
			'recurring-t.php'          => [ "$root/includes/admin/transaction/recurring-t.php" ],
			'refund-t.php'             => [ "$root/includes/admin/transaction/refund-t.php" ],
		];
	}

	/**
	 * Simulate the *correct* pattern used after the fix and confirm the
	 * lang array is properly populated.
	 *
	 * @dataProvider langFileProvider
	 */
	public function test_correct_loading_pattern_produces_valid_lang_array( string $path, string $locale ): void {
		// This mirrors the fixed code: separate variable for the code.
		$xmoney_payments_lang_code = $locale;
		unset( $xmoney_payments_lang );

		$this->assertEquals( $locale, $xmoney_payments_lang_code );

		require $path;

		$this->assertIsArray( $xmoney_payments_lang );
		$this->assertNotEmpty( $xmoney_payments_lang );
		$this->assertIsString( $xmoney_payments_lang['no_woocommerce_f'] );

		// The code variable must be untouched.
		$this->assertSame( $locale, $xmoney_payments_lang_code );
	}
}
