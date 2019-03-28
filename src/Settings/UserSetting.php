<?php
declare( strict_types=1 );

namespace ReadyPhp\Account\Settings;

use ReadyPhp\Common\Date\DateTime;
use ReadyPhp\Entity\EntityAbstract;
use ReadyPhp\Entity\RepositoryAbstract;

/**
 * Class UserSetting
 *
 * @package ReadyPhp\Account\Settings
 * @property int    user_id
 * @property string data
 * @property string last_modified
 */
class UserSetting extends EntityAbstract {

	/**
	 * @var array variables
	 */
	protected $variables = [];

	/**
	 * UserSetting constructor.
	 *
	 * @param array|null              $row
	 * @param RepositoryAbstract|null $repository
	 */
	public function __construct( array $row = null, RepositoryAbstract $repository = null ) {
		parent::__construct( $row, $repository );
		if( $this->data ) {
			$this->variables = \unserialize( $this->data, [ false ] );
		}
	}

	public function __destruct() {
		if( $this->user_id < 1 ) { // don't save guest
			return;
		}

		if( $this->isDirty() ) {
			$this->data = \serialize( $this->variables );
			$this->last_modified = new DateTime();
			$this->save();
		}
	}

	/**
	 * @param string                 $section
	 * @param string                 $key
	 * @param int|float|string|array $value
	 */
	public function setSetting( string $section, string $key, $value ): void {
		$this->variables[ $section ][ $key ] = $value;
	}

	/**
	 * can have 2 or 3 parameters, depends on if deleting whole section or section variable
	 *
	 * @param string $section
	 * @param string $name
	 */
	public function deleteSetting( string $section, string $name = null ) {
		if( $name ) {
			unset( $this->variables[ $section ][ $name ] );
		} else {
			unset( $this->variables[ $section ] );
		}
	}

	/**
	 * @param string $section
	 * @param string $name
	 *
	 * @return int|float|string|array|null
	 */
	public function getSetting( string $section, string $name ) {
		return $this->variables[ $section ][ $name ] ?? null;
	}
}