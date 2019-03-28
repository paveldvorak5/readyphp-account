<?php
declare( strict_types=1 );

namespace ReadyPhp\Account\Access\Cache;

use ReadyPhp\Account\Access\AccessChecker;
use ReadyPhp\Account\UserAbstract;
use ReadyPhp\Account\UserRepository;
use ReadyPhp\Entity\Query\DeleteQuery;
use ReadyPhp\Entity\Query\SelectQuery;
use ReadyPhp\Entity\Query\UpdateQuery;
use ReadyPhp\Entity\RepositoryMySqlAbstract;

/**
 * Class AccessCacheRepository
 *
 * @package ReadyPhp\Access\Cache
 */
class AccessCacheRepository extends RepositoryMySqlAbstract {

	public const ALLOW_COLUMN     = 'allow';
	public const USER_ID_COLUMN   = 'user_id';
	public const ACTION_ID_COLUMN = 'action_id';

	/**
	 * @inheritDoc
	 */
	protected function __construct() {
		$this->usePhpCache = false;
		$this->useExternalCache = false;
		parent::__construct();
	}

	/**
	 * creates query to select cache rows = generally users
	 * user is joined
	 *
	 * @return SelectQuery
	 */
	public function getQuery(): SelectQuery {
		$userRepository = UserRepository::getInstance();

		$query = new SelectQuery( $this );
		$query->select()
			->addRepository( $userRepository );
		$query->joinInner( $userRepository )
			->setDefaultJoinCondition();
		$query->where()
			->equal( UserRepository::ACTIVE_COLUMN, 1, $userRepository )
			->equal( self::ALLOW_COLUMN, 1 );

		return $query;
	}

	/**
	 * update cache for one user and all actions
	 *
	 * @param UserAbstract $user
	 */
	public function updateUserCache( UserAbstract $user ): void {
		$this->deleteUserCache( $user );

		$accessChecker = AccessChecker::getInstance();
		foreach( $accessChecker->loadData( $user ) as $plugin ) {
			/** @var array $plugin */
			/** @noinspection ForeachSourceInspection */
			foreach( $plugin[ $accessChecker->getActionKey() ] as $actionArr ) {
				$accessCache = new AccessCache();
				$accessCache->user_id = $user;
				$accessCache->action_id = $actionArr[ 'id' ];
				$accessCache->allow = 1;
				$accessCache->save( true );
			}
		}
	}

	/**
	 * mark cache as expired for one or all users
	 */
	public function markCacheExpired(): void {
		$repository = UserRepository::getInstance();
		$updateQuery = new UpdateQuery( $repository );
		$updateQuery->set()->addColumn( UserRepository::RELOAD_ACCESS_CACHE_COLUMN, 1 );
		$repository->getConnection()->query( $updateQuery->getGeneralQuery() );
	}

	/**
	 * delete user cache for one user
	 *
	 * @param UserAbstract $user
	 */
	public function deleteUserCache( UserAbstract $user ): void {
		$deleteQuery = new DeleteQuery( $this );
		$deleteQuery->where()->equal( self::USER_ID_COLUMN, $user->getId() );
		$this->getConnection()->query( $deleteQuery->getGeneralQuery() );
	}

	/**
	 * rebuild whole cache
	 * only when necessary, otherwise use markCacheExpired
	 */
	public function rebuildCache(): void {
		$userHandler = UserRepository::getInstance();
		$query = $userHandler->getQuery();
		foreach( $userHandler->fetch( $query ) as $user ) {
			$this->updateUserCache( $user );
		}
	}
}