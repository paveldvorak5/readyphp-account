<?php
declare( strict_types=1 );

namespace ReadyPhp\Account\Session;

use ReadyPhp\Application\ApplicationException;
use ReadyPhp\Cache\RedisPool;
use ReadyPhp\Common\Date\DateTime;

/**
 * Class RedisSessionHandler
 */
class RedisSessionHandler implements \SessionHandlerInterface {

	protected $ttl = 3600 * 6;

	/**
	 * @inheritdoc
	 */
	public function open( $savePath, $sessionName ) {
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function close() {
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function read( $id ) {
		$cachePool = RedisPool::getInstance( $this );
		$database = $cachePool->getDatabase();

		$cachePool->setDatabase( 1 );
		$data = (string)$cachePool->getItem( $id )->get();
		$cachePool->setDatabase( $database );

		return $data;
	}

	/**
	 * @inheritdoc
	 */
	public function write( $id, $data ) {
		$cachePool = RedisPool::getInstance( $this );
		$database = $cachePool->getDatabase(); // todo ukladat jen pokud zmena nebo jednou za cas

		$cacheItem = $cachePool->getItem( $id );
		$cacheItem->expiresAt( ( new DateTime() )->addHour( 6 ) );
		$cacheItem->set( $data );

		$cachePool->setDatabase( 1 );
		$result = $cachePool->save( $cacheItem );
		$cachePool->setDatabase( $database );
		if( $result === false ) {
			throw new ApplicationException( 'Unable to write session' );
		}

		return $result;
	}

	/**
	 * @inheritdoc
	 */
	public function destroy( $id ) {
		$cachePool = RedisPool::getInstance( $this );
		$database = $cachePool->getDatabase();

		$cachePool->setDatabase( 1 );
		$result = $cachePool->deleteItem( $id );
		$cachePool->setDatabase( $database );

		return $result;
	}

	/**
	 * @inheritdoc
	 */
	public function gc( $maxlifetime ) {
		return true;
	}
}