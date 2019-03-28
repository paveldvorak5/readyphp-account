<?php
declare( strict_types=1 );

namespace ReadyPhp\Account\Access;

use ReadyPhp\Account\GlobalAccount\GlobalUserRepository;
use ReadyPhp\Logger\ILogContext;
use ReadyPhp\Logger\Log\RotatingFileLog;
use ReadyPhp\Logger\LogAbstract;

final class AccessLog extends LogAbstract {

	/**
	 * @var RotatingFileLog
	 */
	private $fileLog;

	/**
	 * AccessLog constructor.
	 */
	private function __construct() {
		$this->fileLog = new RotatingFileLog( 'access' );
	}

	/**
	 * @return AccessLog
	 */
	public static function getInstance(): self {
		static $instance;
		if( $instance !== null ) {
			return $instance;
		}
		$instance = new self();

		return $instance;
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param int         $level
	 * @param mixed       $message
	 * @param ILogContext $context
	 * @param array       $components
	 *
	 * @return void
	 */
	public function log( int $level, $message, ILogContext $context = null, array $components = null ): void {
		$message .= ' ' . GlobalUserRepository::getCurrentGlobalUser()->getDebugName();
		$this->fileLog->log( $level, $message, $context, $components );
	}
}