<?php
declare( strict_types=1 );

namespace ReadyPhp\Account\Login;

use ReadyPhp\Account\Access\AccessChecker;
use ReadyPhp\Account\GlobalAccount\GlobalUser;
use ReadyPhp\Account\GlobalAccount\GlobalUserRepository;
use ReadyPhp\Account\Login\Entity\LoginLogRepository;
use ReadyPhp\Account\Login\Entity\UserToken;
use ReadyPhp\Account\Session\SessionManager;
use ReadyPhp\Account\User;
use ReadyPhp\Account\UserRepository;
use ReadyPhp\Application\Request\HttpData;
use ReadyPhp\Common\Date\DateTime;
use ReadyPhp\Entity\Support\TCallback;
use ReadyPhp\Logger\TLog;

/**
 * Class LoginModule
 *
 * @package ReadyPhp\Account\Login
 */
class LoginModule {

	public const LOGIN_TOKEN_PARAM = 'token';

	private const USER_NO_LOG      = 'no_action_log';
	public const  USER_SESSION_KEY = 'logged_user_id';

	private const USER_QUERY_CALLBACK         = 'query';
	public const  USER_SESSION_START_CALLBACK = 'userSession';

	protected static $instance;

	use TCallback;
	use TLog;

	/**
	 * @param $login
	 * @param $password
	 *
	 * @return bool
	 * @throws LoginException
	 */
	public function login( $login, $password ): bool {
		$globalUser = GlobalUserRepository::getInstance()->fetchByLogin( $login );

		$loginLogRepository = LoginLogRepository::getInstance();

		if( $globalUser->isSaved() ) {
			$limitDate = new DateTime();
			$limitDate->subHour( 1 );

			// maximum login
			$query = $loginLogRepository->getQuery();
			$query->where()->equal( LoginLogRepository::IP_COLUMN, HttpData::getClientIP() )
				->equal( LoginLogRepository::ALLOWED_COLUMN, 0 )
				->equal( LoginLogRepository::USER_GLOBAL_ID_COLUMN, $globalUser->getId() )// for this user only
				->greater( LoginLogRepository::REQUEST_DATE_COLUMN, $limitDate );
			$loginCount = $loginLogRepository->fetchCount( $query );
			if( $loginCount > 5 ) {
				$loginLogRepository->addLog( $globalUser, 0, 'too much bad login' );
				throw new LoginException( 'Too much bad login for global user "' . $login . '"', LoginException::TOO_MUCH_BAD_LOGIN, null, 'login.try.maximum' );
			}

			if( PasswordEncoder::encodePassword( $globalUser, $password ) !== $globalUser->password ) {
				$loginLogRepository->addLog( $globalUser, 0, 'wrong password' );
				throw new LoginException( 'Invalid login: wrong password for global user "' . $login . '"', LoginException::WRONG_PASSWORD, null, 'login.failed' );
			}
			$this->checkDisabled( $globalUser );

			$userRepository = UserRepository::getInstance();
			$query = $userRepository->getQuery();
			$query->where()->equal( UserRepository::USER_GLOBAL_COLUMN, $globalUser->getId() );

			$this->callback()->callCallbacks( self::USER_QUERY_CALLBACK, $query );

			$user = $userRepository->fetchFirst( $query );
			if( $user->isSaved() ) {
				SessionManager::destroy();
				SessionManager::start();
				$this->setUserSession( $user );
				$this->updateGlobalUserData( $globalUser );
				$loginLogRepository->addLog( $globalUser, 1 );

				return true;
			}

			$loginLogRepository->addLog( $globalUser, 0, 'user not in app' );
			throw new LoginException( 'Invalid login: user "' . $login . '" does not exist in application', LoginException::NO_LOCAL_USER, null, 'login.failed' );
		}

		throw new LoginException( 'Invalid login: "' . $login . '" does not exist', LoginException::INVALID_LOGIN, null, 'login.failed' );
	}

	/**
	 * set logged user data
	 *
	 * @param User $user
	 */
	public function setUserSession( User $user ): void {
		UserRepository::setCurrentUser( $user );

		$this->callback()->callCallbacks( self::USER_SESSION_START_CALLBACK, $user );

		AccessChecker::getInstance()->setCurrentUserAccessData();
	}

	/**
	 * update last login date etc
	 *
	 * @param GlobalUser $globalUser
	 */
	protected function updateGlobalUserData( GlobalUser $globalUser ): void {
		$globalUser->last_ip = HttpData::getClientIP();
		$globalUser->last_time = new DateTime();
		$globalUser->save();
	}

	/**
	 * login as another user in the same group
	 * user login info is not updated in database
	 *
	 * @param User $user
	 */
	public function loginAsOtherUser( User $user ): void {
		$this->setUserSession( $user );
		$_SESSION[ self::USER_NO_LOG ] = 1;
	}

	/**
	 * login by token (must be UserToken object)
	 * no redirect, all session data must be set
	 *
	 * @param UserToken $token
	 *
	 * @return bool
	 * @throws LoginException
	 */
	public function loginByToken( UserToken $token ): bool { // todo omezit na app
		if( !$token->isSaved() ) {
			throw new LoginException( 'Invalid login: invalid token', LoginException::INVALID_TOKEN, null, 'token.invalid' );
		}

		$globalUser = $token->getUserGlobal();
		$user = $globalUser->getUser();
		if( $user->isSaved() ) {
			$this->checkDisabled( $globalUser );
			$this->setUserSession( $user );
			$token->incrementUseCount();

			return true;
		}

		throw new LoginException( 'Invalid login: invalid token ' . \substr( $token->getToken(), 0, 6 ), LoginException::INVALID_TOKEN, null, 'token.invalid' );
	}

	/**
	 * @param $globalUser
	 *
	 * @throws LoginException
	 */
	private function checkDisabled( GlobalUser $globalUser ): void {
		if( $globalUser->isDisabled() ) {
			$loginLogRepository = LoginLogRepository::getInstance();
			$login = $globalUser->getLogin();
			if( $globalUser->ban_reason ) {
				$loginLogRepository->addLog( $globalUser, 0, 'banned' );
				if( $globalUser->ban_reason === GlobalUser::BAN_DEBT ) {
					throw new LoginException( 'Account banned - user "' . $login . '"', LoginException::BANNED, null, 'login.disabled.debt' );
				}

				if( $globalUser->ban_reason === GlobalUser::BAN_SECURITY ) {
					throw new LoginException( 'Account banned - user "' . $login . '"', LoginException::BANNED, null, 'login.disabled.security' );
				}
			}
			throw new LoginException( 'Account disabled - user "' . $login . '"', LoginException::ACCOUNT_DISABLED, null, 'login.disabled' );
		}
	}

	public static function logout(): void {
		if( \session_status() === \PHP_SESSION_ACTIVE ) {
			\session_destroy();
		}
	}
}