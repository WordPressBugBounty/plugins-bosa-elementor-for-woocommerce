<?php
/**
 * BEW Changelog
 *
 * Reads the Changelog section of the bundled readme.txt, so the list shown in
 * BEW > Settings > Version Control is always the one WordPress.org shows for the
 * plugin. Nothing is fetched remotely and nothing is stored.
 *
 * The readme uses the same "= ... =" heading for a release ("= 2.1.3 =") and for
 * a group inside it ("= Added =", "= Fixed =" ...), so a heading that looks like
 * a version number starts a release and any other heading starts a group. Older
 * releases have no groups; their bullets are kept under an unlabeled group.
 *
 * @package BEW
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BEW_Changelog {

	/**
	 * Releases in the order the readme lists them (newest first).
	 *
	 * @param string $readme Path to a readme.txt. Defaults to BEW's own.
	 * @return array[] Each: 'version' => string, 'groups' => array[ 'label' => string, 'items' => string[] ].
	 */
	public static function get_releases( $readme = '' ) {
		static $cache = array();

		if ( '' === $readme ) {
			$readme = BEW_PATH . 'readme.txt';
		}

		if ( ! isset( $cache[ $readme ] ) ) {
			$cache[ $readme ] = self::parse( self::read( $readme ) );
		}

		return $cache[ $readme ];
	}

	/**
	 * @param string $text Contents of a readme.txt.
	 * @return array[] See get_releases().
	 */
	public static function parse( $text ) {
		$text = str_replace( array( "\r\n", "\r" ), "\n", (string) $text );

		// Everything between "== Changelog ==" and the next "== Section ==" (or the end).
		if ( ! preg_match( '/^==[ \t]*Changelog[ \t]*==[ \t]*$(.*?)(?=^==[^=]|\z)/ims', $text, $section ) ) {
			return array();
		}

		$releases = array();

		foreach ( explode( "\n", $section[1] ) as $line ) {
			$line = trim( $line );

			if ( '' === $line ) {
				continue;
			}

			if ( preg_match( '/^=[ \t]*(.+?)[ \t]*=$/', $line, $heading ) ) {
				if ( preg_match( '/^\d+(?:\.\d+)+(?:-[\w.]+)?$/', $heading[1] ) ) {
					$releases[] = array(
						'version' => $heading[1],
						'groups'  => array(),
					);
				} elseif ( $releases ) {
					$releases[ count( $releases ) - 1 ]['groups'][] = array(
						'label' => $heading[1],
						'items' => array(),
					);
				}
				continue;
			}

			if ( ! $releases ) {
				continue;
			}

			$release = count( $releases ) - 1;

			// Bullets before any group heading (older releases) go in an unlabeled group.
			if ( ! $releases[ $release ]['groups'] ) {
				$releases[ $release ]['groups'][] = array(
					'label' => '',
					'items' => array(),
				);
			}

			$group = count( $releases[ $release ]['groups'] ) - 1;

			if ( preg_match( '/^[*-][ \t]+(.+)$/', $line, $bullet ) ) {
				$releases[ $release ]['groups'][ $group ]['items'][] = $bullet[1];
			} elseif ( $releases[ $release ]['groups'][ $group ]['items'] ) {
				// A wrapped bullet: the text carries on from the previous item.
				$last = count( $releases[ $release ]['groups'][ $group ]['items'] ) - 1;

				$releases[ $release ]['groups'][ $group ]['items'][ $last ] .= ' ' . $line;
			}
		}

		return self::without_empty( $releases );
	}

	/**
	 * Drops groups and releases that ended up with no bullets, so nothing renders blank.
	 */
	private static function without_empty( $releases ) {
		foreach ( $releases as $r => $release ) {
			foreach ( $release['groups'] as $g => $group ) {
				if ( ! $group['items'] ) {
					unset( $releases[ $r ]['groups'][ $g ] );
				}
			}

			$releases[ $r ]['groups'] = array_values( $releases[ $r ]['groups'] );

			if ( ! $releases[ $r ]['groups'] ) {
				unset( $releases[ $r ] );
			}
		}

		return array_values( $releases );
	}

	private static function read( $path ) {
		if ( ! is_readable( $path ) ) {
			return '';
		}

		$contents = file_get_contents( $path );

		return false === $contents ? '' : $contents;
	}
}
