<?php
declare( strict_types=1 );

namespace ReadyPhp\Account\Login\Entity;

use ReadyPhp\Account\GlobalAccount\GlobalUser;
use ReadyPhp\Application\Entity\AppRepository;
use ReadyPhp\Application\Request\HttpData;
use ReadyPhp\Common\Date\DateTime;
use ReadyPhp\Entity\RepositoryMySqlAbstract;

/**
 * Class LoginLogRepository
 *
 * @package ReadyPhp\Account\Login\Entity
 */
class LoginLogRepository extends RepositoryMySqlAbstract {

	const IP_COLUMN             = 'ip';
	const USER_GLOBAL_ID_COLUMN = 'user_global_id';
	const ALLOWED_COLUMN        = 'allowed';
	const REQUEST_DATE_COLUMN   = 'request_date';

	/**
	 * @inheritDoc
	 */
	public function getDatabaseName(): string {
		return $this->getConfig()->getDbGlobal();
	}

	/**
	 * @param GlobalUser $globalUser
	 * @param int        $allowed
	 * @param string     $message
	 */
	public function addLog( GlobalUser $globalUser, int $allowed, string $message = null ): void {
		$entity = new LoginLog();
		$entity->user_global_id = $globalUser->getId();
		$entity->allowed = $allowed;
		$entity->app_id = AppRepository::getCurrentApp()->getId();
		$entity->ip = HttpData::getClientIP();
		$entity->request_date = new DateTime();
		$entity->hostname = \gethostbyaddr( $entity->ip );
		if( $message ) {
			$entity->error_message = $message;
		}
		$entity->save();
	}

}