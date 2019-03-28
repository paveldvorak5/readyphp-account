<?php
declare( strict_types=1 );

namespace ReadyPhp\Account;

use ReadyPhp\Account\GlobalAccount\GlobalCustomerRepository;
use ReadyPhp\Entity\IRepositoryExportApp;
use ReadyPhp\Entity\IRepositoryExportDataCustom;
use ReadyPhp\Entity\Query\IEntityResultSet;
use ReadyPhp\Entity\Query\SelectQuery;
use ReadyPhp\Logger\Request\ConsoleMode;

/**
 * Class UserRepository
 * @method User fetchById( int $id )
 * @method User fetchByIdIfExists( int $id )
 * @method User fetchByIdNoCache( int $id )
 * @method User fetchFirst( SelectQuery $query = null )
 * @method User fetchByEntity( $entity )
 * @method User|null fetchFirstIfExists( SelectQuery $query = null )
 * @method User[]|IEntityResultSet fetch( SelectQuery $query = null )
 */
final class UserRepository extends UserRepositoryAbstract implements IRepositoryExportDataCustom, IRepositoryExportApp {

	/**
	 * @var User
	 */
	private static $cachedUser;

	/**
	 * get logged user
	 *
	 * @return User
	 */
	public static function getCurrentUser(): User {
		if( self::$cachedUser ) {
			return self::$cachedUser;
		}
		self::$cachedUser = static::getInstance()->fetchById( self::getCurrentUserId() );

		return self::$cachedUser;
	}

	/**
	 * set logged user
	 *
	 * @param User $user
	 */
	public static function setCurrentUser( User $user ): void {
		self::$cachedUser = $user;
		if( ConsoleMode::isConsole() === false ) {
			self::setUserSession( $user->getId() );
		}
		GlobalCustomerRepository::setCurrentCustomer( $user->getCustomer()->getGlobalCustomer() );
	}

	/**
	 * @return User
	 */
	public static function getSystemUser(): User {
		return self::getInstance()->fetchById( UserAbstract::SYSTEM_USER_ID );
	}

	/**
	 * @return SelectQuery
	 */
	public function getQueryExport(): SelectQuery {
		$query = $this->getQuery()
			->clearWhere();

		$query->where()
			->equal( self::SYSTEM_COLUMN, 1 );

		return $query;
	}

	/**
	 * @inheritdoc
	 */
	public function getUniqueColumnsExport(): ? array {
		return [ 'login_code' ];
	}
}