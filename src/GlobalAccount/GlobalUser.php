<?php
declare( strict_types=1 );

namespace ReadyPhp\Account\GlobalAccount;

use ReadyPhp\Account\User;
use ReadyPhp\Account\UserRepository;
use ReadyPhp\Account\UserRepositoryAbstract;
use ReadyPhp\Entity\EntityAbstract;

/**
 * @property int         disabled
 * @property int         force_password_change
 * @property int         system
 * @property int         customer_global_id
 * @property string      email
 * @property string      first_name
 * @property string      last_name
 * @property string      login
 * @property string      login_code
 * @property string      password
 * @property string      password_salt
 * @property string      uid
 * @property string      last_ip
 * @property string      last_time
 * @property string|null ban_reason
 * @method  GlobalUserRepository getRepository()
 */
class GlobalUser extends EntityAbstract {

	public const SYSTEM_USER_ID = 3;
	public const BAN_SECURITY   = 'security';
	public const BAN_DEBT       = 'debt';

	/**
	 * get core user
	 *
	 * @return User
	 */
	public function getUser(): User { // todo ze ktere app
		$userHandler = UserRepository::getInstance();
		$query = $userHandler->getQuery();
		$query->where()->equal( UserRepositoryAbstract::USER_GLOBAL_COLUMN, $this->getId() );

		return $userHandler->fetchFirst( $query );
	}

	/**
	 * @return string|null
	 */
	public function getFirstName():?string {
		return $this->first_name;
	}

	/**
	 * @return string|null
	 */
	public function getLastName():?string {
		return $this->last_name;
	}

	/**
	 * @return string|null
	 */
	public function getLoginName(): string {
		return $this->login;
	}

	/**
	 * @return string
	 */
	public function getEmail(): string {
		return $this->email;
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		$str = $this->last_name . ' ' . $this->first_name;
		if( !$str ) {
			$str = $this->login;
		}

		return $str;
	}

	/**
	 * @return string
	 */
	public function getLogin(): string {
		return $this->login;
	}

	/**
	 * get code for auto login
	 *
	 * @return string
	 */
	public function getLoginCode(): string {
		return $this->login_code;
	}

	/**
	 * get unique global user id
	 *
	 * @return string
	 */
	public function getUid(): string {
		return $this->uid;
	}

	/**
	 * @return bool
	 */
	public function needPasswordChange(): bool {
		return $this->force_password_change === 1;
	}

	/**
	 * @return bool
	 */
	public function isDisabled(): bool {
		return $this->disabled === 1;
	}

	/**
	 * is system user
	 */
	public function isSystem(): bool {
		return $this->system === 1;
	}

	/**
	 * @return \DateTimeZone
	 */
	public function getTimezone(): \DateTimeZone {
		return new \DateTimeZone( 'Europe/Prague' ); // todo
	}


	/**
	 * user friendly entity debug info
	 *
	 * @return string
	 */
	public function getDebugName(): string { // todo pridat login, name...
		$html = static::class . ': ' . $this->getId();
		if( isset( $this->name ) ) {
			$html .= ', ' . $this->getName();
		}
		$html .= ', data count: ' . $this->getRowSize();

		return $html;
	}
}