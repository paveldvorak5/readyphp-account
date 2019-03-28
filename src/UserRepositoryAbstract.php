<?php
declare( strict_types=1 );

namespace ReadyPhp\Account;

use ReadyPhp\Account\Login\LoginModule;
use ReadyPhp\Entity\RepositoryMySqlAbstract;

/**
 * Class UserRepositoryAbstract
 */
abstract class UserRepositoryAbstract extends RepositoryMySqlAbstract {

	public const USER_GLOBAL_COLUMN = 'user_global_id';
	public const GROUP_ID_COLUMN    = 'group_id';
	public const SYSTEM_COLUMN      = 'system';

	public const RELOAD_ACCESS_CACHE_COLUMN = 'reload_access_cache';

	/**
	 * @inheritDoc
	 */
	protected function __construct() {
		$this->useExternalCache = \false;
		$this->useTransaction = \true;
		parent::__construct();
	}

	/**
	 * get user id from session
	 *
	 * @return int
	 */
	public static function getCurrentUserId(): int {
		return $_SESSION[ LoginModule::USER_SESSION_KEY ] ?? UserAbstract::GUEST_ID;
	}

	/**
	 * @param int $id
	 */
	protected static function setUserSession( int $id ): void {
		$_SESSION[ LoginModule::USER_SESSION_KEY ] = $id;
	}

	/**
	 * check if some user is logged
	 * false = guest
	 */
	public static function isUserLogged(): bool {
		return self::getCurrentUserId() > 0;
	}

	/**
	 * @inheritDoc
	 */
	public function getTableName(): string {
		return 'user';
	}
}