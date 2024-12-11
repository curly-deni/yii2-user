<?php

namespace aesis\user;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\Module as BaseModule;
use yii\web\Cookie;
use yii\web\Response;

class Module extends BaseModule
{
    /** Email is changed right after user enter's new email address. */
    const STRATEGY_INSECURE = 0;

    /** Email is changed after user clicks confirmation link sent to his new email address. */
    const STRATEGY_DEFAULT = 1;

    /** Email is changed after user clicks both confirmation links sent to his old and new email addresses. */
    const STRATEGY_SECURE = 2;

    /** @var bool Whether to enable registration. */
    public bool $enableRegistration = true;

    /** @var bool Whether user has to confirm his account. */
    public bool $enableConfirmation = true;

    /** @var bool Whether to allow logging in without confirmation. */
    public bool $enableUnconfirmedLogin = false;

    /** @var bool Whether to enable password recovery. */
    public bool $enablePasswordRecovery = true;

    /** TODO @var bool Whether user can remove his account */
    public bool $enableAccountDelete = false;

    /** @var int Email changing strategy. */
    public int $emailChangeStrategy = self::STRATEGY_DEFAULT;

    /** @var int The time you want the user will be remembered without asking for credentials. */
    public int $rememberFor = 1209600; // two weeks

    /** @var int The time before a confirmation token becomes invalid. */
    public int $confirmWithin = 86400; // 24 hours

    /** @var int The time before a recovery token becomes invalid. */
    public int $recoverWithin = 21600; // 6 hours

    /** @var int The time before a deletion token becomes invalid. */
    public int $deleteWithin = 1800; // 30 minutes

    /** @var int Cost parameter used by the Blowfish hash algorithm. */
    public int $cost = 10;

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

    public bool $useLocation = false;

    public string $locationDatabase = '/app/lib/location_db.bin';

    /** @var array The rules to be used in URL management. */
    public array $urlRules = [
        "<action:(signin|signout)>" => "guard/<action>",

        'signup' => 'registration/index',
        "signup/<action:(check-username|check-email|is-enabled)>" => "registration/<action>",

        'edit' => 'settings/account',
        "edit/profile" => "settings/profile",

        'delete' => 'delete/index',
        "delete/<action:(confirm)>" => "delete/<action>",

        'forgot/password' => 'recovery/request',
        "forgot/password/<action:(reset)>" => "recovery/<action>",

        'confirm' => 'confirmation/index',
        "confirm/<action:(status|email|resend)>" => "confirmation/<action>",

        // Узкие правила выше
        "<action:(me|verify-password|get-last-activity-time)>" => "resource/user/<action>",
        '' => 'resource/user/index',
        "<id:\d+>" => "resource/user/index",

        // Правила для controller/action идут ниже
        "<controller:[\w\-]+>" => "resource/<controller>/index",
        "<controller:[\w\-]+>/<id:\d+>" => "resource/<controller>/index",
        "<controller:[\w\-]+>/<action:[\w\-]+>" => "resource/<controller>/<action>",
        "<controller:[\w\-]+>/<action:[\w\-]+>/<id:\d+>" => "resource/<controller>/<action>",
    ];


    public function init()
    {
        parent::init();
    }

    /**
     * @return string
     * @throws InvalidConfigException
     */
    public function getDb()
    {
        return Yii::$app->get($this->dbConnection);
    }
}
