<?php
declare( strict_types=1 );

namespace ReadyPhp\Account;

use ReadyPhp\Account\Customer\CustomerRepository;

/**
 * Class User
 */
final class User extends UserAbstract {

	/**
	 * @return Customer\Customer
	 * @deprecated
	 */
	public function getCustomer(): Customer\Customer {
		return CustomerRepository::getInstance()->fetchById( $this->customer_id );
	}

	/**
	 * @return GlobalAccount\GlobalCustomer
	 */
	public function getGlobalCustomer(): GlobalAccount\GlobalCustomer {
		return $this->getCustomer()->getGlobalCustomer();
	}

}