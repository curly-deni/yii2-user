<?php

namespace aesis\user;

use aesis\traits\helpers\InternalChecker;
use aesis\user\helpers\Location;
use DeviceDetector\ClientHints;
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\AbstractParser;
use DeviceDetector\Parser\Device\AbstractDeviceParser;
use Yii;
use yii\base\BootstrapInterface;
use yii\base\InvalidConfigException;
use yii\console\Application as ConsoleApplication;
use yii\db\ActiveRecord;
use yii\di\NotInstantiableException;
use yii\i18n\PhpMessageSource;


class Bootstrap implements BootstrapInterface
{
    private $_modelMap = [
        'ApiKey' => 'aesis\user\models\ApiKey',
        'AuthKey' => 'aesis\user\models\AuthKey',
        'User' => 'aesis\user\models\User',
        'Profile' => 'aesis\user\models\Profile',
        'Token' => 'aesis\user\models\Token',
        'RegistrationForm' => 'aesis\user\models\RegistrationForm',
        'ResendForm' => 'aesis\user\models\ResendForm',
        'LoginForm' => 'aesis\user\models\LoginForm',
        'SettingsForm' => 'aesis\user\models\SettingsForm',
        'RecoveryForm' => 'aesis\user\models\RecoveryForm',
        'DeleteForm' => 'aesis\user\models\DeleteForm',
        'ApiKeyResource' => 'aesis\user\models\resource\ApiKey',
        'AuthKeyResource' => 'aesis\user\models\resource\AuthKey',
        'UserResource' => 'aesis\user\models\resource\User',
        'ProfileResource' => 'aesis\user\models\resource\Profile',
    ];

    /**
     * @throws NotInstantiableException
     * @throws InvalidConfigException
     */
    public function bootstrap($app): void
    {
        /** @var Module $module */
        /** @var ActiveRecord $modelName */
        if ($app->hasModule('user') && ($module = $app->getModule('user')) instanceof Module) {
            $this->_modelMap = array_merge($this->_modelMap, $module->modelMap);
            foreach ($this->_modelMap as $name => $definition) {
                $class = "aesis\\user\\models\\" . $name;
                Yii::$container->set($class, $definition);
                $modelName = is_array($definition) ? $definition['class'] : $definition;
                $module->modelMap[$name] = $modelName;
                if (in_array($name, ['User', 'Profile', 'Token'])) {
                    Yii::$container->set($name . 'Query', function () use ($modelName) {
                        return $modelName::find();
                    });
                }
            }

            Yii::$container->setSingleton(Finder::class, [
                'userQuery' => Yii::$container->get('UserQuery'),
                'profileQuery' => Yii::$container->get('ProfileQuery'),
                'tokenQuery' => Yii::$container->get('TokenQuery'),
            ]);

            if ($app instanceof ConsoleApplication) {
                $module->controllerNamespace = 'aesis\user\commands';
            } else {

                AbstractDeviceParser::setVersionTruncation(AbstractParser::VERSION_TRUNCATION_NONE);

                $userAgent = $_SERVER['HTTP_USER_AGENT'];
                $clientHints = ClientHints::factory($_SERVER);

                $dd = new DeviceDetector($userAgent, $clientHints);
                $dd->parse();

                $app->set('dd', $dd);

                if ($module->useLocation) {
                    Location::setup($module->locationDatabase);
                    $app->params['location'] = Location::class::getLocationRequest();
                }

                $appUrlPrefix = $app->params['urlPrefix'] ?? '/';
                $moduleUrlPrefix = $module->urlPrefix;

                Yii::$container->set('yii\web\User',
                    [
                        'class' => 'aesis\user\rewrite_yii\User',
                        'enableSession' => InternalChecker::isInternalApi(),
                        'enableAutoLogin' => true,
                        'loginUrl' => [$appUrlPrefix . $moduleUrlPrefix . '/signin'],
                        'identityClass' => $module->modelMap['User']
                    ]
                );

                $configUrlRule = [
                    'prefix' => $moduleUrlPrefix,
                    'rules' => $module->urlRules,
                ];

                if ($module->urlPrefix != 'user') {
                    $configUrlRule['routePrefix'] = 'user';
                }

                $configUrlRule['class'] = 'yii\web\GroupUrlRule';
                $rule = Yii::createObject($configUrlRule);

                $app->urlManager->addRules($rule->rules, false);
            }

            if (!isset($app->get('i18n')->translations['user*'])) {
                $app->get('i18n')->translations['user*'] = [
                    'class' => PhpMessageSource::class,
                    'basePath' => __DIR__ . '/messages',
                    'sourceLanguage' => 'en-US'
                ];
            }

            Yii::$container->set('aesis\user\Mailer', $module->mailer);

            $module->debug = $this->ensureCorrectDebugSetting();
        }
    }

    public function ensureCorrectDebugSetting()
    {
        if (!defined('YII_DEBUG')) {
            return false;
        }
        if (!defined('YII_ENV')) {
            return false;
        }
        if (defined('YII_ENV') && YII_ENV !== 'dev') {
            return false;
        }
        if (defined('YII_DEBUG') && YII_DEBUG !== true) {
            return false;
        }

        return Yii::$app->getModule('user')->debug;
    }
}
