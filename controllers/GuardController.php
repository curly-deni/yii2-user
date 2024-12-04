<?php

namespace aesis\user\controllers;

use aesis\traits\helpers\InternalChecker;
use aesis\user\models\LoginForm;
use aesis\user\traits\EventTrait;
use aesis\user\traits\ModuleTrait;
use Yii;
use yii\base\InvalidConfigException;
use yii\filters\VerbFilter;

class GuardController extends BaseController
{
    use ModuleTrait;
    use EventTrait;

    const EVENT_AFTER_SIGNIN = 'afterSignin';
    const EVENT_AFTER_SIGNOUT = 'afterSignout';

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['access']['only'] = ['signin', 'signout'];
        $behaviors['access']['rules'][] = ['allow' => true, 'actions' => ['signin'], 'roles' => ['?']];
        $behaviors['access']['rules'][] = ['allow' => true, 'actions' => ['signout'], 'roles' => ['@']];

        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'signin' => ['post'],
                'signout' => ['get'],
            ],
        ];

        return $behaviors;
    }

    /**
     * @throws InvalidConfigException
     */
    public function login($login, $password, $rememberMe = false)
    {

        /** @var LoginForm $model */
        $model = Yii::createObject([
            'class' => $this->getModule()->modelMap['LoginForm'],
            'login' => $login,
            'password' => $password,
            'rememberMe' => $rememberMe
        ]);

        if ($model->login()) {

            $event = $this->getUserEvent(Yii::$app->user->identity);
            $this->trigger(self::EVENT_AFTER_SIGNIN, $event);

            $data = [];
            $data['user'] = Yii::$app->user->identity;

            if (!InternalChecker::isInternalApi()) {
                $data['key'] = Yii::$app->user->identity->getAuthKey();
            }

            return [
                'code' => 200,
                'message' => Yii::t('user', 'You are logged in.'),
                'data' => $data
            ];
        }

        $errors = implode(". ", array_map(function ($error) {
            return implode(", ", $error);
        }, $model->getErrors()));

        return [
            'code' => 400,
            'message' => Yii::t('user', 'Login failed.')
                . (empty($errors) ? "" : " ") . $errors,
            'data' => []
        ];
    }


    /**
     * @throws InvalidConfigException
     */
    public function actionSignin()
    {
        $data = Yii::$app->getRequest()->post();
        $login = $data['login'] ?? '';
        $password = $data['password'] ?? '';
        $rememberMe = $data['rememberMe'] ?? false;

        if (empty($login) || empty($password)) {
            return $this->makeResponse('', Yii::t('user', 'All fields are required.'), 400);
        }

        $loginResult = $this->login($login, $password, $rememberMe);

        return $this->makeResponse($loginResult['data'], $loginResult['message'], $loginResult['code']);
    }

    public function actionSignout()
    {
        $user = Yii::$app->user->identity;
        $event = $this->getUserEvent($user);

        $key = Yii::$app->user->identity->getCurrentKey();
        Yii::$app->getUser()->logout();
        $key->processDelete();

        $this->trigger(self::EVENT_AFTER_SIGNOUT, $event);

        return $this->makeResponse(
            '',
            Yii::t('user', 'You are logged out.')
        );
    }

}