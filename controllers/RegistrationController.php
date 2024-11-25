<?php

namespace aesis\user\controllers;

use aesis\user\controllers\BaseController as Controller;
use aesis\user\models\User;
use aesis\user\traits\EventTrait;
use aesis\user\traits\ModuleTrait;
use Yii;
use yii\base\InvalidConfigException;
use yii\filters\VerbFilter;


class RegistrationController extends Controller
{
    use ModuleTrait;
    use EventTrait;

    const EVENT_AFTER_SIGNUP = 'afterSignup';

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {

        $behaviors = parent::behaviors();

        $behaviors['access']['only'] = ['index'];
        $behaviors['access']['rules'][] = ['allow' => true, 'actions' => ['index'], 'roles' => ['?']];

        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'index' => ['post'],
                'check-username' => ['get'],
                'check-email' => ['get'],
                'is-enabled' => ['get'],
            ],
        ];

        return $behaviors;
    }


    /**
     * @throws InvalidConfigException
     */
    protected function loginIfNeed($username, $password)
    {
        if (($this->module->enableConfirmation ?? false) && !($this->module->enableUnconfirmedLogin ?? false)) {
            return [
                'code' => 403,
            ];
        }

        return Yii::createObject(GuardController::class)->login($username, $password, true);
    }

    public function actionCheckUsername($username)
    {
        $user = User::find()->where(['username' => $username])->one();
        return $this->makeResponse([
            'free' => $user === null
        ]);
    }

    public function actionCheckEmail($email)
    {
        $user = User::find()->where(['email' => $email])->one();
        return $this->makeResponse([
            'free' => $user === null
        ]);
    }

    public function actionIsEnabled()
    {
        return $this->makeResponse([
            'enabled' => ($this->module->enableRegistration ?? false)
        ]);
    }

    /**
     * @throws InvalidConfigException
     */
    public function actionIndex()
    {
        $data = Yii::$app->getRequest()->post();

        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        $email = $data['email'] ?? '';

        if (empty($username) || empty($password) || empty($email)) {
            return $this->makeResponse('', Yii::t('user', 'All fields are required.'), 400);
        }

        $registerResult = $this->register($email, $username, $password);

        return $this->makeResponse($registerResult['data'], $registerResult['message'], $registerResult['code']);
    }

    /**
     * @throws InvalidConfigException
     */
    public function register($email, $username, $password)
    {
        if (!($this->module->enableRegistration ?? false)) {
            return [
                'data' => '',
                'message' => Yii::t('user', 'Registration is disabled.'),
                'code' => 503
            ];
        }

        $model = Yii::createObject($this->module->modelMap['RegistrationForm']);

        $model->username = $username;
        $model->password = $password;
        $model->email = $email;
        $result = $model->register();

        if ($result) {
            $event = $this->getUserEvent($result);
            $this->trigger(self::EVENT_AFTER_SIGNUP, $event);

            $loginResult = $this->loginIfNeed($model->username, $model->password);

            if ($loginResult['code'] != 200) {
                return [
                    'data' => ['authorized' => false],
                    'message' => Yii::t('user', 'Your account has been created, please confirm your email to log in.'),
                    'code' => 201
                ];

            }

            return [
                'data' => array_merge($loginResult['data'], [
                    'authorized' => true,
                ]),
                'message' => Yii::t('user', 'Your account has been created and signed in.'),
                'code' => 201
            ];
        }

        $errors = implode(". ", array_map(function ($error) {
            return implode(", ", $error);
        }, $model->getErrors()));

        return [
            'data' => [],
            'message' => Yii::t('user', 'Your account has not been created.')
                . (empty($errors) ? "" : " ") . $errors,
            'code' => 400
        ];
    }
}
