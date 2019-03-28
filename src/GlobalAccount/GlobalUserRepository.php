<?php
declare( strict_types=1 );

namespace ReadyPhp\Account\GlobalAccount;

use ReadyPhp\Account\UserRepository;
use ReadyPhp\Entity\EntityAbstract;
use ReadyPhp\Entity\EntitySaver;
use ReadyPhp\Entity\IRepositoryExportDataCustom;
use ReadyPhp\Entity\Query\SelectQuery;
use ReadyPhp\Entity\RepositoryGlobalAbstract;
use ReadyPhp\Support\Text\TextUtil;

/**
 * Class GlobalUserRepository
 * @method GlobalUser fetchFirst( SelectQuery $query = null )
 * @method GlobalUser fetchById( int $id )
 * @method GlobalUser fetchByEntity( $entity )
 */
class GlobalUserRepository extends RepositoryGlobalAbstract implements IRepositoryExportDataCustom {

	public const FIRST_NAME_COLUMN = 'first_name';
	public const LAST_NAME_COLUMN  = 'last_name';
	public const LOGIN_COLUMN      = 'login';
	public const PASSWORD_COLUMN   = 'password';
	/**
	 * login hash
	 */
	public const LOGIN_CODE_COLUMN = 'login_code';
	public const SYSTEM_COLUMN     = 'system';
	/**
	 * unique user identifier
	 *
	 * @var string
	 */
	public const UID_COLUMN           = 'uid';
	public const PASSWORD_SALT_COLUMN = 'password_salt';
	public const CUSTOMER_COLUMN      = 'customer_global_id';

	/**
	 * @var GlobalUser
	 */
	protected static $cachedGlobalUser;

	/**
	 * @inheritDoc
	 */
	protected function __construct() {
		$this->useExternalCache = false;
		parent::__construct();
		$this->callback()->setCallback( EntitySaver::BEFORE_SAVE_DIRTY, function ( EntityAbstract $entity ) {
			if( !$entity->{self::LOGIN_CODE_COLUMN} ) {
				$entity->{self::LOGIN_CODE_COLUMN} = $this->createUniqueLoginCode();
			}

			if( !$entity->{self::UID_COLUMN} ) {
				$entity->{self::UID_COLUMN} = $this->createUniqueId();
			}
		} );
	}

	/**
	 * @inheritDoc
	 */
	public function getTableName(): string {
		return 'user_global';
	}

	/**
	 * @inheritdoc
	 */
	public function getQuery(): SelectQuery {
		$query = parent::getQuery();
		$query->order()
			->ascColumn( self::LAST_NAME_COLUMN )
			->ascColumn( self::FIRST_NAME_COLUMN )
			->ascColumn( self::LOGIN_COLUMN );

		return $query;
	}

	/**
	 * get logged user global
	 *
	 * @return GlobalUser
	 */
	public static function getCurrentGlobalUser(): GlobalUser {
		if( self::$cachedGlobalUser ) {
			return self::$cachedGlobalUser;
		}
		self::$cachedGlobalUser = UserRepository::getCurrentUser()->getGlobalUser();

		return self::$cachedGlobalUser;
	}

	/**
	 * fetch user by login
	 *
	 * @param string $login
	 * @param bool   $includeInactive
	 *
	 * @return GlobalUser
	 */
	public function fetchByLogin( $login, bool $includeInactive = null ): GlobalUser {
		$query = $this->getQuery();
		$query->clearOrder();
		if( $includeInactive ) {
			$query->clearWhere();
		}
		$query->where()
			->equal( self::LOGIN_COLUMN, $login );

		return $this->fetchFirst( $query );
	}

	/**
	 * @param string $login
	 * @param int    $idToExclude
	 *
	 * @return bool
	 */
	public function loginExists( $login, int $idToExclude = null ): bool {
		$query = new SelectQuery( $this );
		$query->where()->equal( self::LOGIN_COLUMN, $login );
		if( $idToExclude ) {
			$query->where()->notEqual( self::ID, $idToExclude );
		}

		return $this->fetchFirst( $query )->isSaved();
	}

	/**
	 * fetch user by login
	 *
	 * @param string $uid
	 * @param bool   $includeInactive
	 *
	 * @return GlobalUser
	 */
	public function fetchByUid( $uid, bool $includeInactive = null ): GlobalUser {
		$query = $this->getQuery();
		$query->clearOrder();
		if( $includeInactive ) {
			$query->clearWhere();
		}
		$query->where()->equal( self::UID_COLUMN, $uid );

		return $this->fetchFirst( $query );
	}

	/**
	 * generate unique user login code, 32 chars long string
	 *
	 * @return string
	 * @deprecated user token
	 */
	protected function createUniqueLoginCode(): string {
		$code = TextUtil::randomString( 32 );
		$query = $this->getQuery()
			->clearWhere();

		$query->where()
			->equal( self::LOGIN_CODE_COLUMN, $code );
		$count = $this->fetchCount( $query );
		while( $count > 0 ) {
			$code = TextUtil::randomString( 32 );
			$query->clearWhere()
				->where()
				->equal( self::LOGIN_CODE_COLUMN, $code );
			$count = $this->fetchCount( $query );
		}

		return $code;
	}

	/**
	 * generate unique user id, 8 chars long number including zeros
	 *
	 * @return string
	 */
	protected function createUniqueId(): string {
		$code = TextUtil::randomString( 8, true );
		$query = $this->getQuery()
			->clearWhere();

		$query->where()
			->equal( self::UID_COLUMN, $code );
		$count = $this->fetchCount( $query );
		while( $count > 0 ) {
			$code = TextUtil::randomString( 8, true );
			$query->clearWhere()
				->where()
				->equal( self::UID_COLUMN, $code );
			$count = $this->fetchCount( $query );
		}

		return $code;
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
	public function getUniqueColumnsExport(): ?array {
		return null;
	}
}