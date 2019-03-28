<?php
declare( strict_types=1 );

namespace ReadyPhp\Account\Login\Entity;

use ReadyPhp\Entity\EntityAbstract;

/**
 * Class LoginLog
 *
 * @package core\model\user
 * @property    int    user_global_id
 * @property    int    app_id
 * @property    int    allowed
 * @property    string error_message
 * @property    string request_date
 * @property    string ip
 * @property    string hostname
 */
class LoginLog extends EntityAbstract {

}