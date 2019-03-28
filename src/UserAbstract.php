<?php
declare( strict_types=1 );

namespace ReadyPhp\Account;

use ReadyPhp\Account\Access\AccessException;
use ReadyPhp\Account\Access\Entity\Group;
use ReadyPhp\Account\Access\Entity\GroupRepository;
use ReadyPhp\Account\Customer\Customer;
use ReadyPhp\Account\GlobalAccount\GlobalUser;
use ReadyPhp\Account\GlobalAccount\GlobalUserRepository;
use ReadyPhp\Account\GlobalAccount\IUserGlobalId;
use ReadyPhp\Entity\EntityAbstract;
use ReadyPhp\Lang\Lang;
use ReadyPhp\Lang\LangRepository;

/**
 * Class UserAbstract
 *
 * @package core\model\user
 * @property int    active
 * @property int    system
 * @property int    user_global_id
 * @property int    lang_id
 * @property int    group_id
 * @property int    customer_id
 * @property int    reload_access_cache
 * @property string timezone // todo move to global
 */
abstract class UserAbstract extends EntityAbstract implements IUserGlobalId {

	public const GUEST_ID       = 0;
	public const SYSTEM_LOGIN   = 'system';
	public const SYSTEM_USER_ID = 3;
	/**
	 * @var GlobalUser
	 */
	protected $cachedGlobalUser;
	/**
	 * @var Group
	 */
	protected $cachedMainGroup;

	public function clearCachedGroup(): void {
		$this->cachedMainGroup = null;
	}

	/**
	 * @return Customer
	 */
	abstract public function getCustomer(); // todo Icustomer

	/**
	 * @return \ReadyPhp\Account\Access\Entity\Group
	 */
	public function getGroup(): Group {
		if( $this->cachedMainGroup !== null ) {
			return $this->cachedMainGroup;
		}

		$this->cachedMainGroup = GroupRepository::getInstance()->fetchById( $this->group_id );

		return $this->cachedMainGroup;
	}

	/**
	 * get user language
	 *
	 * @return Lang
	 */
	public function getLang(): Lang {
		if( $this->getId() < 1 ) {
			$this->lang_id = LangRepository::getCurrentLang()->getId();
		}

		return LangRepository::getInstance()->fetchById( $this->lang_id );
	}

	/**
	 * @return int
	 */
	public function getUserGlobalId(): int {
		return $this->getGlobalUser()->getId();
	}

	/**
	 * get user from global user table
	 *
	 * @return GlobalUser
	 */
	public function getGlobalUser(): GlobalAccount\GlobalUser {
		if( $this->cachedGlobalUser ) {
			return $this->cachedGlobalUser;
		}
		$this->cachedGlobalUser = GlobalUserRepository::getInstance()->fetchByEntity( $this );

		return $this->cachedGlobalUser;
	}

	/**
	 * @return bool
	 */
	public function isGuest(): bool {
		return $this->getId() === self::GUEST_ID;
	}

	/**
	 * is system user
	 */
	public function isSystem(): bool {
		return $this->system === 1;
	}

	public function isSuperAdmin(): bool {
		$level = $this->getGroup()->getLevel();
		if( $level < 1 ) {
			throw new AccessException( 'Invalid user group level' );
		}

		return $level < Group::DISTRIBUTOR_LEVEL;
	}
}