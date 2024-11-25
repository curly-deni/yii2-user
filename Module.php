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
        '<action:(signin|signout)>' => 'guard/<action>',
        'signup/<action:(check-username|check-email|is-enabled|index)>' => 'registration/<action>',
        'edit/<action:(index|confirm)>' => 'settings/<action>',
        'delete/<action:(index|confirm)>' => 'delete/<action>',
        'forgot/password/<action:(index|enter)>' => 'recovery/<action>',
        'confirm/<action:(index|status|email|resend)>' => 'confirmation/<action>',

        // Узкие правила выше
        '<action:(index|me|verify-password|get-last-activity-time)' => 'resource/user/<action>',
        'index/<id:\d+>' => 'resource/user/<action>',

        // Правила для controller/action идут ниже
        '<controller:[\w\-]+>' => 'resource/<controller>/index',
        '<controller:[\w\-]+>/<action:[\w\-]+>' => 'resource/<controller>/<action>',
        '<controller:[\w\-]+>/<action:[\w\-]+>/<id:\d+>' => 'resource/<controller>/<action>',
    ];


    public function init()
    {
        parent::init();

        Yii::$app->response->on(Response::EVENT_BEFORE_SEND, function ($event) {
            $request = Yii::$app->request;
            $response = $event->sender;
            $cookies = $request->cookies;
            $responseCookies = $response->cookies;

            // Получаем значение aesis_id из куки
            $aesisId = $cookies->getValue('aesis_id', null);

            // Проверяем актуальность aesis_id
            if (!$aesisId || !$this->isAesisIdInvalid($aesisId)) {
                // Генерируем новое значение aesis_id
                $newAesisId = $this->generateNewAesisId();

                // Добавляем новое значение в куки
                $responseCookies->add(new Cookie([
                    'name' => 'aesis_id',
                    'value' => $newAesisId,
                    'httpOnly' => true,
                ]));
            }
        });
    }

    /**
     * Проверяет, является ли aesis_id актуальным.
     *
     * @param string $aesisId
     * @return bool
     */
    private function isAesisIdInvalid($aesisId)
    {
        \Yii::debug('here1');

        if ($aesisId === 'unauthenticated') {
            return \Yii::$app->getUser()->isGuest;
        }

        $values = json_decode($aesisId, true);
        if (\Yii::$app->getUser()->isGuest) {
            return false;
        }

        $identity = \Yii::$app->getUser()->getIdentity();
        $data = [
            'user' => [
                'id' => $identity->id,
                'username' => $identity->username,
                'email' => $identity->email,
                'role' => $identity->role
            ],
            'profile' => $identity->profile->toArray()
        ];

        return count(array_udiff_assoc($values, $data, function ($a, $b) { return is_array($a) ? array_diff($a, $b) : strcmp($a, $b); })) !== 0;
    }

    /**
     * Генерирует новое значение aesis_id.
     *
     * @return string
     */
    private function generateNewAesisId()
    {
        if (\Yii::$app->getUser()->isGuest) {
            return 'unauthenticated';
        }
        $identity = \Yii::$app->getUser()->getIdentity();
        $data = [
            'user' => [
                'id' => $identity->id,
                'username' => $identity->username,
                'email' => $identity->email,
                'role' => $identity->role
            ],
            'profile' => $identity->profile->toArray()
        ];
        return json_encode($data);
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
