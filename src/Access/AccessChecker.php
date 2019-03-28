<?php
declare( strict_types=1 );

namespace ReadyPhp\Account\Access;

use ReadyPhp\Account\Access\Cache\AccessCacheRepository;
use ReadyPhp\Account\UserAbstract;
use ReadyPhp\Account\UserRepository;
use ReadyPhp\Application\Controller\ControllerAbstract;
use ReadyPhp\Cache\RedisPool;
use ReadyPhp\Lang\LangRepository;

/**
 * Class AccessChecker
 * check access by url
 *
 * @package ReadyPhp\Access
 */
class AccessChecker {

	/**
	 * @var string
	 */
	protected static $instance;

	private $connection;

	/**
	 * Array of access rights
	 *
	 * @var array
	 */
	public $data;

	private $actionKey = 'actions';

	/**
	 * AccessChecker constructor.
	 */
	public function __construct() {
		$this->connection = LangRepository::getInstance()->getConnection();
	}

	/**
	 * @return AccessChecker
	 */
	public static function getInstance(): self {
		if( !self::$instance instanceof self ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @return array
	 */
	public function & getAccessData(): array {
		return $this->data;
	}

	/**
	 * @return string
	 */
	public function getActionKey(): string {
		return $this->actionKey;
	}

	/**
	 * loads user access data and sets a cache
	 * napred prava skupin potom uzivatele
	 *                       Array
	 *                       (
	 *                       [login] => Array
	 *                       (
	 *                       [name] => Přihlášení
	 *                       [description] => Přihlašovací formulář
	 *                       [visible] => 1
	 *                       [is_default] => 0
	 *                       [actions] => Array
	 *                       (
	 *                       [login_check] => Array
	 *                       (
	 *                       [allow] => 1
	 *                       [name] => Přihlášení
	 *                       [description] =>
	 *                       [visible] => 0
	 *                       [is_default] => 0
	 *                       [rank] =>
	 *                       )
	 *                       [login_form] => Array
	 *                       (
	 *                       [allow] => 1
	 *                       [name] => Přihlašovací formulář
	 *                       [description] =>
	 *                       [visible] =>
	 *                       [is_default] => 1
	 *                       [rank] =>
	 *                       )
	 *                       )
	 *                       }
	 */
	public function setCurrentUserAccessData(): void {
		$user = UserRepository::getCurrentUser();
		if( $user->reload_access_cache ) { // delete cache
			$this->deleteCache( $user );
			$user->reload_access_cache = 0;
			UserRepository::getInstance()->update( $user );
			AccessCacheRepository::getInstance()->updateUserCache( $user );
		}
		$userId = $user->getId();

		$cachePool = RedisPool::getInstance( $this, (string)$userId, (string)LangRepository::getCurrentLang()->getId() );
		$cacheItem = $cachePool->getItem( 'rights' );

		if( $cacheItem->isHit() === false ) {
			//$cachePool->save(
				//$cacheItem->set( $this->loadData( $user, LangRepository::getCurrentLang()->getId() ) )
			//); // todo
		}

		$this->data = $cacheItem->get();
	}

	/**
	 * @param UserAbstract $user
	 */
	public function deleteCache( UserAbstract $user ): void {
		$cachePool = RedisPool::getInstance( $this, (string)$user->getId() );

		foreach( LangRepository::getInstance()->fetchUsed() as $lang ) {
			$cachePool->deleteItem( (string)$lang->getId() );
		}
	}

	/**
	 * load access data from database
	 *
	 * @param UserAbstract $user
	 * @param int          $langId
	 *
	 * @return array[] of allowed plugins and actions
	 */
	public function loadData( UserAbstract $user, int $langId = 0 ): array {
		if( $langId < 1 ) {
			$langId = LangRepository::getCurrentLang()->getId();
		}
		$userId = $user->getId();

		try {
			$rs = $this->connection->sqlQuery( 'SELECT p.id AS plugin_id, p.url AS plugin_url, p.is_user AS user_plugin, pl.name AS plugin_name, pl.description AS plugin_description, p.visible AS plugin_visible, p.rank AS plugin_rank, p.is_default AS plugin_default, p.submenu AS plugin_submenu, p.set_access AS plugin_access,
				a.id AS action_id, a.url AS action_url, al.name AS action_name, al.description AS action_description, a.visible AS action_visible, a.rank AS action_rank, a.is_default AS action_default, a.type AS action_type, a.hr AS action_hr, a.access_parent_id AS action_access_parent_id, a.url_validation AS action_url_validation,
				CASE WHEN pu.allow IS NOT NULL THEN pu.allow ELSE pg.allow END AS allow
				FROM user_group ug
				JOIN plugin_group pg ON pg.group_id = ug.group_id
				JOIN plugin p ON pg.plugin_id = p.id
				LEFT JOIN plugin_lang pl ON p.id=pl.plugin_id AND pl.lang_id=' . $langId . '
				JOIN action a ON a.plugin_id = p.id
				LEFT JOIN action_lang al ON a.id=al.action_id AND al.lang_id=' . $langId . '
				LEFT JOIN plugin_user pu ON p.id=pu.plugin_id AND pu.user_id = ' . $userId . '
			WHERE p.active=1 AND a.active=1 AND ug.user_id = ' . $userId . '
			ORDER BY p.rank, a.rank
			' );
		} /** @noinspection BadExceptionsProcessingInspection */ catch( \Exception $e ) {
			exit( 1 );
		}
		$data = [];
		foreach( $rs as $row ) {
			$plugin = $row[ 'plugin_url' ];
			$action = $row[ 'action_url' ];
			if( !isset( $data[ $plugin ] ) && $row[ 'allow' ] ) {
				$data[ $plugin ] = [
					'name'           => $row[ 'plugin_name' ],
					'url'            => $row[ 'plugin_url' ],
					'description'    => $row[ 'plugin_description' ],
					'visible'        => $row[ 'plugin_visible' ],
					'is_default'     => $row[ 'plugin_default' ],
					'is_user'        => $row[ 'user_plugin' ],
					'id'             => $row[ 'plugin_id' ],
					'rank'           => $row[ 'plugin_rank' ],
					'submenu'        => $row[ 'plugin_submenu' ],
					'set_access'     => $row[ 'plugin_access' ],
					$this->actionKey => []
				];
			}
			if( $row[ 'allow' ] && !isset( $data[ $plugin ][ $this->actionKey ][ $action ] ) ) {
				$data[ $plugin ][ $this->actionKey ][ $action ] = [
					'allow'            => $row[ 'allow' ],
					'name'             => $row[ 'action_name' ],
					'description'      => $row[ 'action_description' ],
					'visible'          => $row[ 'action_visible' ],
					'is_default'       => $row[ 'action_default' ],
					'rank'             => $row[ 'action_rank' ],
					'submenu'          => $row[ 'plugin_submenu' ],
					'id'               => $row[ 'action_id' ],
					'type'             => $row[ 'action_type' ],
					'hr'               => $row[ 'action_hr' ],
					'access_parent_id' => $row[ 'action_access_parent_id' ],
					'url_validation'   => $row[ 'action_url_validation' ]
				];
			}
		}

		$rs = $this->connection->sqlQuery( 'SELECT p.id AS plugin_id, p.url AS plugin_url, p.is_user AS user_plugin, pl.name AS plugin_name, pl.description AS plugin_description, p.visible AS plugin_visible, p.rank AS plugin_rank, p.is_default AS plugin_default, p.submenu AS plugin_submenu, p.set_access AS plugin_access,
			a.id AS action_id, a.url AS action_url, al.name AS action_name, al.description AS action_description, a.visible AS action_visible, a.rank AS action_rank, a.is_default AS action_default, a.type AS action_type, a.hr AS action_hr, a.access_parent_id AS action_access_parent_id, a.url_validation AS action_url_validation,
			CASE WHEN au.allow IS NOT NULL THEN au.allow ELSE ag.allow END AS allow
			FROM user_group ug
			JOIN action_group ag ON ag.group_id = ug.group_id
			JOIN action a ON ag.action_id = a.id
			LEFT JOIN action_lang al ON a.id=al.action_id AND al.lang_id=' . $langId . '
			JOIN plugin p ON a.plugin_id = p.id
			LEFT JOIN plugin_lang pl ON p.id=pl.plugin_id AND pl.lang_id=' . $langId . '
			LEFT JOIN action_user au ON a.id=au.action_id AND au.user_id = ' . $userId . '
		WHERE p.active=1 AND a.active=1 AND ug.user_id = ' . $userId . '
		ORDER BY p.rank, a.rank
		' );
		foreach( $rs as $row ) {
			$plugin = $row[ 'plugin_url' ];
			$action = $row[ 'action_url' ];
			if( !isset( $data[ $plugin ] ) && $row[ 'allow' ] ) {
				$data[ $plugin ] = [
					'name'           => $row[ 'plugin_name' ],
					'url'            => $row[ 'plugin_url' ],
					'description'    => $row[ 'plugin_description' ],
					'visible'        => $row[ 'plugin_visible' ],
					'is_default'     => $row[ 'plugin_default' ],
					'is_user'        => $row[ 'user_plugin' ],
					'id'             => $row[ 'plugin_id' ],
					'rank'           => $row[ 'plugin_rank' ],
					'submenu'        => $row[ 'plugin_submenu' ],
					'set_access'     => $row[ 'plugin_access' ],
					$this->actionKey => []
				];
			}
			if( $row[ 'allow' ] && !isset( $data[ $plugin ][ $this->actionKey ][ $action ] ) ) {
				$data[ $plugin ][ $this->actionKey ][ $action ] = [
					'allow'            => $row[ 'allow' ],
					'name'             => $row[ 'action_name' ],
					'description'      => $row[ 'action_description' ],
					'visible'          => $row[ 'action_visible' ],
					'is_default'       => $row[ 'action_default' ],
					'rank'             => $row[ 'action_rank' ],
					'submenu'          => $row[ 'plugin_submenu' ],
					'id'               => $row[ 'action_id' ],
					'type'             => $row[ 'action_type' ],
					'hr'               => $row[ 'action_hr' ],
					'access_parent_id' => $row[ 'action_access_parent_id' ],
					'url_validation'   => $row[ 'action_url_validation' ]
				];
			}

			// vyhozeni na ktere akce neni pravo
			if( !$row[ 'allow' ] ) {
				unset( $data[ $plugin ][ $this->actionKey ][ $action ] );
			}
		}

		$rs = $this->connection->sqlQuery( 'SELECT p.id AS plugin_id, p.url AS plugin_url, p.is_user AS user_plugin, pl.name AS plugin_name, pl.description AS plugin_description, p.visible AS plugin_visible, p.rank AS plugin_rank, p.is_default AS plugin_default, p.submenu AS plugin_submenu, p.set_access AS plugin_access,
			a.id AS action_id, a.url AS action_url, al.name AS action_name, al.description AS action_description, a.visible AS action_visible, a.rank AS action_rank, a.is_default AS action_default, a.type AS action_type, a.hr AS action_hr, a.access_parent_id AS action_access_parent_id, a.url_validation AS action_url_validation,
			au.allow AS allow
			FROM  action_user au
			JOIN action a ON au.action_id = a.id
			LEFT JOIN action_lang al ON a.id=al.action_id AND al.lang_id=' . $langId . '
			JOIN plugin p ON a.plugin_id = p.id
			LEFT JOIN plugin_lang pl ON p.id=pl.plugin_id AND pl.lang_id=' . $langId . '
		WHERE p.active=1 AND a.active=1 AND au.user_id = ' . $userId . '
		ORDER BY p.rank, a.rank
		' );
		foreach( $rs as $row ) {
			$plugin = $row[ 'plugin_url' ];
			$action = $row[ 'action_url' ];
			if( !isset( $data[ $plugin ] ) && $row[ 'allow' ] ) {
				$data[ $plugin ] = [
					'name'           => $row[ 'plugin_name' ],
					'url'            => $row[ 'plugin_url' ],
					'description'    => $row[ 'plugin_description' ],
					'visible'        => $row[ 'plugin_visible' ],
					'is_default'     => $row[ 'plugin_default' ],
					'is_user'        => $row[ 'user_plugin' ],
					'id'             => $row[ 'plugin_id' ],
					'rank'           => $row[ 'plugin_rank' ],
					'submenu'        => $row[ 'plugin_submenu' ],
					'set_access'     => $row[ 'plugin_access' ],
					$this->actionKey => []
				];
			}
			if( $row[ 'allow' ] && !isset( $data[ $plugin ][ $this->actionKey ][ $action ] ) ) {
				$data[ $plugin ][ $this->actionKey ][ $action ] = [
					'allow'            => $row[ 'allow' ],
					'name'             => $row[ 'action_name' ],
					'description'      => $row[ 'action_description' ],
					'visible'          => $row[ 'action_visible' ],
					'is_default'       => $row[ 'action_default' ],
					'rank'             => $row[ 'action_rank' ],
					'submenu'          => $row[ 'plugin_submenu' ],
					'id'               => $row[ 'action_id' ],
					'type'             => $row[ 'action_type' ],
					'hr'               => $row[ 'action_hr' ],
					'access_parent_id' => $row[ 'action_access_parent_id' ],
					'url_validation'   => $row[ 'action_url_validation' ]
				];
			}

			// vyhozeni na ktere akce neni pravo
			if( !$row[ 'allow' ] ) {
				unset( $data[ $plugin ][ $this->actionKey ][ $action ] );
			}
		}

		// set access according to parents
		$actionHandler = ActionRepository::getInstance();
		$query = $actionHandler->getQuery();
		$query->where()
			->greater( ActionRepository::ACCESS_PARENT_COLUMN, 0 );

		foreach( $actionHandler->fetch( $query ) as $action ) {
			$parentAction = $action->getAccessParent();
			$parentUrl = $parentAction->getUrl();
			$actionUrl = $action->getUrl();
			$pluginUrl = $action->getPlugin()->getUrl();

			// if parent allowed
			if( isset( $data[ $pluginUrl ][ $this->actionKey ][ $parentUrl ] ) ) {
				$row = $action->toArray();
				$row[ 'allow' ] = $data[ $pluginUrl ][ $this->actionKey ][ $parentUrl ][ 'allow' ];
				$data[ $pluginUrl ][ $this->actionKey ][ $actionUrl ] = $row;
			} else {
				unset( $data[ $pluginUrl ][ $this->actionKey ][ $actionUrl ] );
			}
		}

		//		\uasort( $data, [ $this, 'pluginSort' ] );

		return $data;
	}

	///**
	// * @param array $a
	// * @param array $b
	// *
	// * @return int
	// */
	//protected function pluginSort( array $a, array $b ) {
	//	if( $a[ 'rank' ] === $b[ 'rank' ] ) {
	//		return 0;
	//	}
	//
	//	return ( $a[ 'rank' ] < $b[ 'rank' ] ) ? -1 : 1;
	//}

	/**
	 * reloads and set data for current user
	 */
	public function reloadData(): void {
		$user = UserRepository::getCurrentUser();
		$this->deleteCache( $user );
		$this->setCurrentUserAccessData();
	}

	/**
	 * check rights
	 * use loaded access cache
	 *
	 * @param Plugin $plugin
	 * @param Action $action
	 *
	 * @return boolean
	 */
	protected function check( Plugin $plugin, Action $action ): bool {
		return isset( $this->data[ $plugin->getUrl() ][ $this->actionKey ][ $action->getUrl() ] ) && $this->data[ $plugin->getUrl() ][ $this->actionKey ][ $action->getUrl() ][ 'allow' ];
	}

	/**
	 * check action access rights
	 * use database if action access exists
	 *
	 * @param Plugin $plugin
	 * @param Action $action
	 *
	 * @return bool
	 * @throws AccessException
	 */
	public function checkAccess( Plugin $plugin, Action $action ): bool { // todo cache a apcu a php podle vsech akci
		if( $plugin->isAccessibleForAll() ) {
			return true;
		}

		if( UserRepository::isUserLogged() === \false ) {
			throw new AccessException( 'User has to be signed in to access ' . $plugin->getUrl() . ':' . $action->getUrl(), AccessException::USER_NOT_LOGGED );
		}

		if( !$plugin->getId() ) { // todo je potreba?
			throw new AccessException( 'Invalid plugin ' . $plugin->getUrl() );
		}

		//$action = $plugin->getActionByUrl( $actionUrl );
		if( $action->isEmpty() ) { // action doesn't exist - no access set // todo jak?
			return true;
		}

		//if( $action->hasAccessParent() ) { // todo melo by byt uz v poli, zkontrolovat
		//	$action = $action->getAccessParent(); // switch to parent
		//	$actionUrl = $action->toString();
		//}

		$result = $this->check( $plugin, $action );
		if( $result === false ) {
			AccessLog::getInstance()->info( 'Access denied for ' . $plugin->getUrl() . ':' . $action->getUrl() );
		}

		return $result;
	}

	/**
	 * @param ControllerAbstract $controller
	 *
	 * @return array
	 */
	public function getControllerPermissions( ControllerAbstract $controller ): array {
		return [];//TODo jp dodelat
	}
}