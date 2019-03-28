<?php
declare( strict_types=1 );

namespace ReadyPhp\Account\GlobalAccount;

interface  IUserGlobalId {

	/**
	 * @return int
	 */
	public function getUserGlobalId(): ?int;
}