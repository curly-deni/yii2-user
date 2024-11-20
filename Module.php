<?php

namespace aesis\user;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\Module as BaseModule;

class Module extends BaseModule
{
    const VERSION = '1.0.0';

    /** Email is changed right after user enter's new email address. */
    const STRATEGY_INSECURE = 0;

    /** Email is changed after user clicks confirmation link sent to his new email address. */
    const STRATEGY_DEFAULT = 1;

    /** Email is changed after user clicks both confirmation links sent to his old and new email addresses. */
    const STRATEGY_SECURE = 2;

    /** @var bool Whether to enable registration. */
    public bool $enableRegistration = true;

    /** @var bool Whether to remove password field from registration form. */
    public bool $enableGeneratingPassword = false;

    /** @var bool Whether user has to confirm his account. */
    public bool $enableConfirmation = true;

    /** @var bool Whether to allow logging in without confirmation. */
    public bool $enableUnconfirmedLogin = false;

    /** @var bool Whether to enable password recovery. */
    public bool $enablePasswordRecovery = true;

    /** @var bool Whether user can remove his account */
    public bool $enableAccountDelete = false;

    /** @var int Email changing strategy. */
    public int $emailChangeStrategy = self::STRATEGY_DEFAULT;

    /** @var int The time you want the user will be remembered without asking for credentials. */
    public int $rememberFor = 1209600; // two weeks

    /** @var int The time before a confirmation token becomes invalid. */
    public int $confirmWithin = 86400; // 24 hours

    /** @var int The time before a recovery token becomes invalid. */
    public int $recoverWithin = 21600; // 6 hours

    /** @var int Cost parameter used by the Blowfish hash algorithm. */
    public int $cost = 10;

    /** @var array An array of administrator's usernames. */
    public array $admins = [];

    /** @var string The Administrator permission name. */
    public string $adminPermission;

    /** @var array Mailer configuration */
    public array $mailer = [];

    /** @var array Model map */
    public array $modelMap = [];

    /**
     * @var string The prefix for user module URL.
     *
     * @See [[GroupUrlRule::prefix]]
     */
    public string $urlPrefix = 'user';

    /**
     * @var bool Is the user module in DEBUG mode? Will be set to false automatically
     * if the application leaves DEBUG mode.
     */
    public bool $debug = false;

    /** @var string The database connection to use for models in this module. */
    public string $dbConnection = 'db';

    /** @var array The rules to be used in URL management. */
    public array $urlRules = [
        '<action:(signin|signout)>' => 'guard/<action>',

        '<action:(check-username|check-email|registration-enabled|signup|resend|is-confirmed|user-confirm|email-confirm)>' => 'registration/<action>',

        'edit/<action>' => 'settings/<action>',

        'forgot-password' => 'recovery/request',
        'recover-password' => 'recovery/reset',

    ];

    public bool $useLocation = false;
    public string $locationDatabase = '/app/lib/location_db.bin';

    /**
     * @return string
     * @throws InvalidConfigException
     */
    public function getDb()
    {
        return Yii::$app->get($this->dbConnection);
    }
}
