<?php
declare( strict_types=1 );

namespace ReadyPhp\Account\Access\Entity;

use ReadyPhp\Account\User;
use ReadyPhp\Account\UserRepository;
use ReadyPhp\Entity\IRepositoryExportApp;
use ReadyPhp\Entity\IRepositoryExportDataCustom;
use ReadyPhp\Entity\Query\SelectQuery;
use ReadyPhp\Entity\RepositoryMySqlAbstract;

/**
 * Class GroupRepository
 *
 * @package ReadyPhp\Access\Entity
 * @method static GroupRepository getInstance()
 */
final class GroupRepository extends RepositoryMySqlAbstract implements IRepositoryExportDataCustom, IRepositoryExportApp {

	public const CODE_COLUMN  = 'code';
	public const LEVEL_COLUMN = 'level';
	public const CUSTOMER_ID  = 'customer_id';

	/**
	 * RepositoryAbstract constructor.
	 */
	public function __construct() {
		$this->multiLang = true;
		parent::__construct();
	}

	/**
	 * fetch users with this group level or lower
	 *
	 * @param int $level
	 *
	 * @return \ReadyPhp\Entity\Query\IEntityResultSet|User[]
	 */
	public function fetchUsersBySufficientLevel( int $level ) {
		$userRepository = UserRepository::getInstance();

		$query = $userRepository->getQuery();
		$query->where()->lowerEqual( self::LEVEL_COLUMN, $level );

		return $userRepository->fetch( $query );
	}

	/**
	 * can user edit a group?
	 * group level must be greater then current user group
	 *
	 * @param Group $group
	 *
	 * @return boolean
	 */
	public static function isGroupVisible( Group $group ): bool {
		return $group->getLevel() >= UserRepository::getCurrentUser()->getGroup()->getLevel();
	}

	/**
	 * @return SelectQuery
	 */
	public function getQueryExport(): SelectQuery {
		$query = $this->getQuery()
			->clearWhere();

		$query->where()
			->equal( self::CUSTOMER_ID, 0 );

		return $query;
	}

	/**
	 * @inheritdoc
	 */
	public function getUniqueColumnsExport(): array {
		return [];
	}
}