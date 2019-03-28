<?php
declare( strict_types=1 );

namespace ReadyPhp\Account\Customer;

use ReadyPhp\Account\GlobalAccount\GlobalCustomer;
use ReadyPhp\Account\GlobalAccount\GlobalCustomerRepository;
use ReadyPhp\Entity\EntityAbstract;
use ReadyPhp\General\Database\Currency\Currency;
use ReadyPhp\General\Database\Currency\CurrencyRepository;

/** @noinspection CompositionAndInheritanceInspection */

/**
 * Class Customer
 *
 * @property string name
 * @property string description
 * @property int    distributor_id
 * @property int    active
 * @property string timezone
 * @property int    lang_id
 * @property int    currency_global_id
 * @property int    customer_global_id
 * @property int    disabled
 */
class Customer extends EntityAbstract {

	/**
	 * @return \ReadyPhp\General\Database\Currency\Currency
	 */
	public function getCurrency(): Currency {
		return CurrencyRepository::getInstance()->fetchById( $this->offsetExists( 'currency_global_id') ? $this->currency_global_id : $this->currency_id ); // todo
	}

	/**
	 * @return GlobalCustomer
	 */
	public function getGlobalCustomer(): GlobalCustomer {
		return GlobalCustomerRepository::getInstance()->fetchById( $this->customer_global_id );
	}

}