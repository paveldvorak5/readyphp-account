<?php
declare( strict_types=1 );

namespace ReadyPhp\Account\GlobalAccount;

use ReadyPhp\Account\Login\LoginModule;
use ReadyPhp\Entity\RepositoryGlobalAbstract;
use ReadyPhp\Logger\Request\ConsoleMode;

/**
 * Class GlobalCustomerRepository
 *
 * @package ReadyPhp\Account\GlobalAccount
 * @method GlobalCustomer fetchById( int $id )
 */
final class GlobalCustomerRepository extends RepositoryGlobalAbstract {

	private const SESSION_KEY = 'customer_global_id';

	/**
	 * @var GlobalCustomer
	 */
	public static $cachedCustomer;

	/**
	 * @inheritDoc
	 */
	public function fetchByIdIfExists( int $id ): ?GlobalCustomer {
		static $cache;

		$cache[ $id ] = $cache[ $id ] ?? parent::fetchByIdIfExists( $id ) ?? false;

		return $cache[ $id ] ?: null;
	}

	/**
	 * @inheritDoc
	 */
	public function getTableName(): string {
		return 'customer_global';
	}

	/**
	 * @return GlobalCustomer
	 */
	public static function getCurrentCustomer(): GlobalCustomer {
		if( self::$cachedCustomer ) {
			return self::$cachedCustomer;
		}
		self::$cachedCustomer = static::getInstance()->fetchById( self::getCurrentCustomerId() );

		return self::$cachedCustomer;
	}

	/**
	 * get customer id from session
	 *
	 * @return int
	 */
	public static function getCurrentCustomerId(): int {
		if( isset( $_SESSION[ self::SESSION_KEY ] ) === false ) { // safety check, incomplete session
			LoginModule::logout();

			return 0;
		}

		return $_SESSION[ self::SESSION_KEY ];
	}

	/**
	 * @param GlobalCustomer $customer
	 */
	public static function setCurrentCustomer( GlobalCustomer $customer ): void {
		self::$cachedCustomer = $customer;
		if( ConsoleMode::isConsole() === false ) {
			$_SESSION[ self::SESSION_KEY ] = $customer->getId();
		}
	}
}