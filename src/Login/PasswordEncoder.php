<?php
declare( strict_types=1 );

namespace ReadyPhp\Account\Login;

use ReadyPhp\Account\GlobalAccount\GlobalUser;

/**
 * Class PasswordEncoder
 *
 * @package ReadyPhp\Account\Login
 */
class PasswordEncoder {

	/**
	 * new password salt
	 *
	 * @return string
	 */
	public static function generateSalt(): string {
		return self::randomPassword( 10 );
	}

	/**
	 * @param GlobalUser $user
	 * @param string     $password
	 *
	 * @return string
	 */
	public static function encodePassword( GlobalUser $user, string $password ): string {
		$salt = $user->password_salt;

		if( $salt ) {
			/** @noinspection SpellCheckingInspection */
			return \hash_hmac( 'sha256', $password . \sha1( $salt ), '78sdf1xj5klkl4' ); // todo remove salt, use password_hash
		} else {
			return \md5( $password );
		}
	}

	/**
	 * @param int $length
	 *
	 * @return string
	 */
	public static function randomPassword( int $length = 5 ): string {
		/** @noinspection SpellCheckingInspection */
		$chars = 'abcdefghijklmnopqrstuvwxyz123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$output = '';
		$chars_count = \strlen( $chars );
		for( $i = 0; $i < $length; $i++ ) {
			$output .= $chars[ \random_int( 0, $chars_count - 1 ) ];
		}

		return $output;
	}
}