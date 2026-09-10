<?php
/**
 * BEW_Crypto
 *
 * Small at-rest encryption helper for single string secrets (AI provider API
 * keys). Same AES-256-CBC-via-AUTH_KEY/SECURE_AUTH_KEY technique already
 * used for credential storage in the BEW Template Kits Demo Manager plugin,
 * ported here since BEW has no shared code with that plugin.
 *
 * @package BosaMiller\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BEW_Crypto {

	const METHOD = 'AES-256-CBC';

	/**
	 * Encrypt a single string value for storage.
	 *
	 * @param string $value Plaintext value.
	 * @return string|false Base64-encoded (IV + ciphertext); empty string for
	 *                       an empty input; false if openssl is unavailable or
	 *                       encryption fails -- callers must treat false as a
	 *                       hard error, never store it as if it were a secret.
	 */
	public static function encrypt( $value ) {
		$value = (string) $value;

		if ( '' === $value ) {
			return '';
		}

		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return false;
		}

		$key = hash( 'sha256', AUTH_KEY . SECURE_AUTH_KEY, true );
		$iv  = random_bytes( 16 );

		$encrypted = openssl_encrypt( $value, self::METHOD, $key, OPENSSL_RAW_DATA, $iv );

		if ( false === $encrypted ) {
			return false;
		}

		return base64_encode( $iv . $encrypted ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Decrypt a value produced by encrypt().
	 *
	 * @param string $encrypted Base64-encoded (IV + ciphertext).
	 * @return string Plaintext value, or empty string on any failure.
	 */
	public static function decrypt( $encrypted ) {
		$encrypted = (string) $encrypted;

		if ( '' === $encrypted ) {
			return '';
		}

		$decoded = base64_decode( $encrypted, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		if ( false === $decoded ) {
			return '';
		}

		if ( ! function_exists( 'openssl_decrypt' ) ) {
			return $decoded;
		}

		if ( strlen( $decoded ) < 17 ) {
			return '';
		}

		$key        = hash( 'sha256', AUTH_KEY . SECURE_AUTH_KEY, true );
		$iv         = substr( $decoded, 0, 16 );
		$ciphertext = substr( $decoded, 16 );

		$decrypted = openssl_decrypt( $ciphertext, self::METHOD, $key, OPENSSL_RAW_DATA, $iv );

		return false === $decrypted ? '' : $decrypted;
	}
}
