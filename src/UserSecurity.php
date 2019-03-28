<?php
declare( strict_types=1 );

namespace ReadyPhp\Account;

use ReadyPhp\Support\Text\TextUtil;

/**
 * Class UserSecurity
 *
 * @package ReadyPhp\Account
 */
class UserSecurity {

	const SESSION_KEY = 'CSRF_token';

	/**
	 * @return string
	 */
	public static function getCSRFToken(): string {
		if( !isset( $_SESSION[ self::SESSION_KEY ] ) ) {
			$_SESSION[ self::SESSION_KEY ] = TextUtil::randomString( 50 );
		}

		return $_SESSION[ self::SESSION_KEY ];
	}
}