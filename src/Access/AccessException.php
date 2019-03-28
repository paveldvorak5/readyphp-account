<?php
declare( strict_types=1 );

namespace ReadyPhp\Account\Access;

use ReadyPhp\Logger\TLog;
use Throwable;

/**
 * Class AccessException
 *
 * @package ReadyPhp\Access
 */
class AccessException extends \Exception {

	use TLog;

	public const USER_NOT_LOGGED      = 2;
	public const ACTION_ACCESS_DENIED = 3;

	/**
	 * @inheritDoc
	 */
	public function __construct( $message = '', $code = 0, Throwable $previous = null ) {
		parent::__construct( $message, $code, $previous );

		$logInstance = AccessLog::getInstance();

		switch( $code ) {
			case self::USER_NOT_LOGGED:
				$logInstance->debug( $message );
				break;

			case self::ACTION_ACCESS_DENIED:
				$logInstance->info( $message );
				break;
			default:
		}
	}
}