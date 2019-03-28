<?php
declare( strict_types=1 );

namespace ReadyPhp\Account\Customer;

use ReadyPhp\Entity\Query\IEntityResultSet;
use ReadyPhp\Entity\Query\SelectQuery;
use ReadyPhp\Entity\RepositoryMySqlAbstract;

/** @noinspection CompositionAndInheritanceInspection */
/**
 * Class CustomerRepository
 * @method Customer fetchFirst( SelectQuery $query = null )
 * @method Customer fetchFirstIfExists( SelectQuery $query = null )
 * @method Customer fetchById( int $id )
 * @method IEntityResultSet|Customer[] fetch( SelectQuery $query = null )
 */
class CustomerRepository extends RepositoryMySqlAbstract {

	/**
	 * @var Customer
	 */
	public static $cachedCustomer;

	/**
	 * @return string
	 */
	public function getTableName(): string {
		return 'customer';
	}

	/**
	 * get logged user
	 *
	 * @return Customer
	 */
	public static function getCurrentCustomer(): Customer { // todo staci global?
		if( self::$cachedCustomer ) {
			return self::$cachedCustomer;
		}
		self::$cachedCustomer = static::getInstance()->fetchById( self::getCurrentCustomerId() );

		return self::$cachedCustomer;
	}
}