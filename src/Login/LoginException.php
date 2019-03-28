<?php
declare( strict_types=1 );

namespace ReadyPhp\Account\Login;

use ReadyPhp\Lang\LangRepository;
use ReadyPhp\Logger\LoggerExceptionUtil;
use ReadyPhp\Logger\TLog;
use Throwable;

/**
 * Class LoginException
 *
 * @package ReadyPhp\Account\Login
 */
class LoginException extends \Exception {

	use TLog;

	const TOO_MUCH_BAD_LOGIN = 2;
	const WRONG_PASSWORD     = 3;
	const ACCOUNT_DISABLED   = 4;
	const BANNED             = 5;
	const NO_LOCAL_USER      = 6;
	const INVALID_LOGIN      = 7;
	const INVALID_TOKEN      = 8;

	/**
	 * @var string
	 */
	public $langCode;
	/**
	 * @var string
	 */
	private $langKey;

	/**
	 * @inheritDoc
	 */
	public function __construct( $message = '', $code = 0, Throwable $previous = null, string $langKey ) {
		parent::__construct( $message, $code, $previous );
		$this->langKey = $langKey;
		LoggerExceptionUtil::handleException( $this, $code, $previous, $message );
	}

	/**
	 * @return string
	 */
	public function getErrorMessage(): string {
		return LangRepository::getCurrentLang()->translateGlobal( 'login', $this->langKey );
	}
}