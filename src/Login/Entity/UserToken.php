<?php
declare( strict_types=1 );

namespace ReadyPhp\Account\Login\Entity;

use ReadyPhp\Account\GlobalAccount\GlobalUser;
use ReadyPhp\Account\GlobalAccount\GlobalUserRepository;
use ReadyPhp\Common\Date\DateTime;
use ReadyPhp\Entity\EntityAbstract;

/**
 * @property int    api_app_id
 * @property int    max_use
 * @property int    user_device_id
 * @property int    user_global_id
 * @property int    use_count
 * @property string date_valid_from
 * @property string date_valid_to
 * @property string token
 */
class UserToken extends EntityAbstract {

	/**
	 * @return string
	 */
	public function getToken(): string {
		return $this->token;
	}

	/**
	 * @return int
	 */
	public function getMaxUse(): int {
		return $this->max_use;
	}

	/**
	 * @param int $maxUse
	 */
	public function setMaxUse( int $maxUse ): void {
		$this->max_use = $maxUse;
	}

	/**
	 * @return GlobalUser
	 */
	public function getUserGlobal(): GlobalUser {
		return GlobalUserRepository::getInstance()->fetchByEntity( $this );
	}

	/**
	 * @return bool
	 */
	public function isExpired(): bool {
		$date = (string)new DateTime();

		return !( ( $this->date_valid_from === null || $this->date_valid_from <= $date )
			&& ( $this->date_valid_to === null || $this->date_valid_to >= $date )
			&& ( $this->max_use === null || $this->max_use > $this->use_count ) );
	}

	public function incrementUseCount(): void {
		if( $this->isSaved() ) {
			$this->use_count++;
			if( ( $this->max_use > 0 ) && ( $this->use_count === $this->max_use ) ) {
				if( $this->max_use === 1 ) {
					$this->delete()->permanent();
				} else {
					$this->getRepository()->update( $this );
					$this->delete()->deactivate();
				}
			} else {
				$this->getRepository()->update( $this );
			}
		}
	}
}
