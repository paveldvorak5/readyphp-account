<?php
declare( strict_types=1 );

namespace ReadyPhp\Account\Settings;

use ReadyPhp\Account\UserRepository;
use ReadyPhp\Entity\Query\SelectQuery;
use ReadyPhp\Entity\RepositoryMySqlAbstract;

/**
 * Class UserSettingRepository
 *
 * @package ReadyPhp\Account\Settings
 * @method UserSetting fetchFirst( SelectQuery $query = null )
 */
class UserSettingRepository extends RepositoryMySqlAbstract {

	const USER_ID_COLUMN = 'user_id';

	protected static $instance;

	/**
	 * @inheritDoc
	 */
	public function getTableName(): string {
		return 'session';
	}

	/**
	 * @inheritDoc
	 */
	public function getPrimaryKey(): string {
		return 'user_id';
	}

	/**
	 * for current user
	 *
	 * @return UserSetting
	 */
	public static function getCurrent(): UserSetting {
		if( self::$instance ) {
			return self::$instance;
		}
		$query = self::getInstance()->getQuery();
		$userId = UserRepository::getCurrentUserId();
		$query->where()->equal( self::USER_ID_COLUMN, $userId );
		self::$instance = self::getInstance()->fetchFirst( $query );
		if( self::$instance->isNew() ) {
			self::$instance->user_id = $userId;
		}

		return self::$instance;
	}
}