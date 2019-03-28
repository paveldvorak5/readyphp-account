<?php
declare( strict_types=1 );

namespace ReadyPhp\Account\Login\Entity;

use ReadyPhp\Account\GlobalAccount\GlobalUser;
use ReadyPhp\Common\Date\DateTime;
use ReadyPhp\Entity\Query\IEntityResultSet;
use ReadyPhp\Entity\Query\SelectQuery;
use ReadyPhp\Entity\RepositoryMySqlAbstract;
use ReadyPhp\Support\Text\TextUtil;

/**
 * Class UserTokenRepository
 *
 * @package ReadyPhp\Account\Login\Entity
 * @method UserToken fetchFirst( SelectQuery $query = null )
 * @method UserToken[]|IEntityResultSet fetch( SelectQuery $query = null )
 */
class UserTokenRepository extends RepositoryMySqlAbstract {

	const DATE_VALID_FROM_COLUMN = 'date_valid_from';
	const DATE_VALID_TO_COLUMN   = 'date_valid_to';
	const MAX_USE_COLUMN         = 'max_use';
	const TOKEN_COLUMN           = 'token';

	/**
	 * @inheritDoc
	 */
	public function getDatabaseName(): string {
		return $this->getConfig()->getDbGlobal();
	}

	/**
	 * @inheritdoc
	 */
	public function getQuery(): SelectQuery {
		$now = new DateTime();

		$query = $this->getCleanQuery();

		$query->where()
			->addWhereParts(
				$query->createWherePartOr()
					->isNull( self::DATE_VALID_FROM_COLUMN )
					->lowerEqual( self::DATE_VALID_FROM_COLUMN, $now ),
				$query->createWherePartOr()
					->isNull( self::DATE_VALID_TO_COLUMN )
					->greaterEqual( self::DATE_VALID_TO_COLUMN, $now ),
				$query->createWherePartOr()
					->isNull( self::MAX_USE_COLUMN )
					->greaterEqualColumn( self::MAX_USE_COLUMN, self::MAX_USE_COLUMN, $this )
			);

		return $query;
	}

	/**
	 * @return string
	 */
	public function makeUniqueToken(): string {
		$key = TextUtil::randomString( 32 );
		$query = $this->getCleanQuery();
		$query->where()->equal( self::TOKEN_COLUMN, $key );
		$count = $this->fetchCount( $query );
		while( $count > 0 ) {
			$key = TextUtil::randomString( 32 );
			$query->clearWhere()
				->where()
				->equal( self::TOKEN_COLUMN, $key );
			$count = $this->fetchCount( $query );
		}

		return $key;
	}

	/**
	 * @param string $token
	 * @param bool   $includeInactiveAndExpired
	 *
	 * @return UserToken
	 */
	public function fetchByToken( string $token, bool $includeInactiveAndExpired = null ): UserToken {
		$query = $this->getQuery();
		if( $includeInactiveAndExpired ) {
			$query->clearWhere();
		}
		$query->where()->equal( self::TOKEN_COLUMN, $token );

		return $this->fetchFirst( $query );
	}

	/**
	 * @param GlobalUser $globalUser
	 *
	 * @return UserToken
	 */
	public function createToken( $globalUser ): UserToken {
		$userToken = new UserToken();
		$userToken->user_global_id = $globalUser->getId();
		$userToken->token = $this->makeUniqueToken();

		return $userToken;
	}
}