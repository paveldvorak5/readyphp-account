<?php
declare( strict_types=1 );

namespace ReadyPhp\Account\Access\Cache;

use ReadyPhp\Account\User;
use ReadyPhp\Account\UserRepository;
use ReadyPhp\Entity\EntityAbstract;
use ReadyPhp\Entity\EntitySaver;
use ReadyPhp\Entity\Query\DeleteQuery;

/**
 * @property    int id
 * @property    int user_id
 * @property    int action_id
 * @property    int allow
 */
class AccessCache extends EntityAbstract {

	/**
	 * get user
	 *
	 * @return User
	 */
	public function getUser(): User {
		return UserRepository::getInstance()->fetchById( $this->user_id );
	}

	/**
	 * save AccessCache object
	 *
	 * @param boolean $forceInsert rows don't exist, can do insert directly
	 *
	 * @return static
	 */
	public function save( bool $forceInsert = null ): self {
		$entitySaver = new EntitySaver( $this->getRepository(), $this );

		if( $this->allow ) {
			if( $forceInsert === true ) {
				$entitySaver->insert();
			} else {
				$entitySaver->saveReplace( [ AccessCacheRepository::USER_ID_COLUMN, AccessCacheRepository::ACTION_ID_COLUMN ] );
			}
		} else {
			$deleteQuery = new DeleteQuery( $this->getRepository() );
			$deleteQuery->where()
				->equal( AccessCacheRepository::USER_ID_COLUMN, $this->user_id )
				->equal( AccessCacheRepository::ACTION_ID_COLUMN, $this->action_id );

			$this->getRepository()->getConnection()->query( $deleteQuery->getGeneralQuery() );
		}
		return $this;
	}
}