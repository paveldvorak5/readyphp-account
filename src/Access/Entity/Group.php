<?php
declare( strict_types=1 );

namespace ReadyPhp\Account\Access\Entity;

use ReadyPhp\Entity\EntityAbstract;
use ReadyPhp\Lang\MultiLangTrait;

/**
 * Class Group
 *
 * @property string name
 * @property string code
 * @property int    level
 * @package ReadyPhp\Access\Entity
 */
final class Group extends EntityAbstract {

	use MultiLangTrait;

	public const SUPER_ADMIN_LEVEL = 1;
	public const DISTRIBUTOR_LEVEL = 10;
	public const ADMIN_LEVEL       = 20;
	public const USER_LEVEL        = 30;

	/**
	 * @return string
	 */
	public function getCode(): ?string {
		return $this->code;
	}

	/**
	 * 1 = superAdmin, 100 = guest
	 *
	 * @return int
	 */
	public function getLevel(): int {
		return $this->level;
	}
}