<?php
declare( strict_types=1 );

namespace ReadyPhp\Account\Session;

use ReadyPhp\Application\Request\HttpData;
use ReadyPhp\Cache\RedisPool;

/**
 * Class SessionManager
 *
 * @package ReadyPhp\Account\Session
 */
class SessionManager {

	public static function start(): void {
		$currentCookieParams = \session_get_cookie_params();

		\session_set_cookie_params(
			$currentCookieParams[ 'lifetime' ],
			$currentCookieParams[ 'path' ],
			$currentCookieParams[ 'domain' ],
			HttpData::isSecure(),
			true
		);
		\ini_set( 'session.cookie_samesite', 'strict' );
		\ini_set( 'use_strict_mode', 'on' );

		if( RedisPool::getInstanceCustom( __CLASS__ )->isEnabled() ) {
			$handler = new RedisSessionHandler();
			session_set_save_handler( $handler, true );
		}

		if( \session_status() === \PHP_SESSION_NONE ) {
			\session_start();
		}
	}

	public static function destroy(): void {
		\session_destroy();
	}

}