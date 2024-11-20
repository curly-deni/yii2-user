<?php

namespace aesis\user\controllers;

use aesis\user\controllers\BaseController as Controller;
use aesis\user\models\RegistrationForm;
use aesis\user\models\ResendForm;
use aesis\user\models\User;
use aesis\user\Module;
use aesis\user\traits\ModuleTrait;
use Yii;
use yii\base\InvalidConfigException;
use yii\filters\VerbFilter;


class RegistrationController extends Controller
{
    use ModuleTrait;

    const EVENT_BEFORE_SIGNUP = 'beforeSignup';
    const EVENT_AFTER_SIGNUP = 'afterSignup';
    const EVENT_BEFORE_USER_CONFIRM = 'beforeUserConfirm';
    const EVENT_AFTER_USER_CONFIRM = 'afterUserConfirm';
    const EVENT_BEFORE_EMAIL_CONFIRM = 'beforeEmailConfirm';
    const EVENT_AFTER_EMAIL_CONFIRM = 'afterEmailConfirm';
    const EVENT_BEFORE_RESEND = 'beforeResend';
    const EVENT_AFTER_RESEND = 'afterResend';

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {

        $behaviors = parent::behaviors();

        $behaviors['access']['only'] = ['signup', 'user-confirm', 'resend', 'email-confirm'];
        $behaviors['access']['rules'][] = ['allow' => true, 'actions' => ['signup'], 'roles' => ['?']];
        $behaviors['access']['rules'][] = [
            'allow' => true,
            'actions' => ['user-confirm', 'resend', 'email-confirm'],
            'roles' => ['?', '@']
        ];

        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'signup' => ['post'],
                'user-confirm' => ['get'],
                'email-confirm' => ['get'],
                'resend' => ['post'],
                'is-confirmed' => ['get'],
                'check-username' => ['get'],
                'check-email' => ['get'],
                'registration-enabled' => ['get'],
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

    public function actionRegistrationEnabled()
    {
        return $this->makeResponse([
            'enabled' => ($this->module->enableRegistration ?? false)
        ]);
    }

    /**
     * @throws InvalidConfigException
     */
    public function actionSignup()
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

        $this->trigger(self::EVENT_BEFORE_SIGNUP);

        $model->username = $username;
        $model->password = $password;
        $model->email = $email;

        if ($model->register()) {
            $loginResult = $this->loginIfNeed($model->username, $model->password);

            if ($loginResult['code'] != 200) {
                return [
                    'data' => ['authorized' => false],
                    'message' => Yii::t('user', 'Your account has been created, please confirm your email to log in.'),
                    'code' => 201
                ];

            }

            $this->trigger(self::EVENT_AFTER_SIGNUP);

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

    public function actionUserConfirm($id, $code)
    {
        $user = $this->finder->findUserById($id);

        if ($user === null || !($this->module->enableConfirmation ?? false)) {
            return $this->redirect([
                '/auth/confirm',
                'success' => false,
                'message' => Yii::t('user', 'Account confirmation is disabled.')
            ]);
        }

        $this->trigger(self::EVENT_BEFORE_USER_CONFIRM);

        $result = $user->attemptConfirmation($code);

        if ($result['success']) {
            $this->trigger(self::EVENT_AFTER_USER_CONFIRM);
        }

        return $this->redirect([
            '/auth/confirm',
            'success' => $result['success'],
            'message' => $result['message']
        ]);
    }

    public function actionIsConfirmed($email)
    {
        $user = $this->finder->findUserByEmail($email);
        return $this->makeResponse(['confirmed' => $user instanceof User && $user->isConfirmed]);
    }

    /**
     * @throws InvalidConfigException
     */
    public function actionResend()
    {
        if (!($this->module->enableConfirmation ?? false)) {
            return $this->makeResponse(
                '',
                Yii::t('user', 'User confirmation is disabled.'),
                503
            );
        }

        $model = Yii::createObject(ResendForm::class);

        $this->trigger(self::EVENT_BEFORE_RESEND);

        $data = Yii::$app->getRequest()->post();
        $model->email = $data['email'] ?? null;

        if ($model->resend()) {
            $this->trigger(self::EVENT_AFTER_RESEND);
            return $this->makeResponse(
                '',
                Yii::t('user', 'A new confirmation link has been sent')
            );
        }

        $errors = implode(". ", array_map(function ($error) {
            return implode(", ", $error);
        }, $model->getErrors()));

        return $this->makeResponse(
            '',
            Yii::t('user', 'An error occurred. Please try again later.')
            . (empty($errors) ? "" : " ") . $errors,
            500
        );
    }

    public function actionEmailConfirm($id, $code)
    {
        if (($this->module->emailChangeStrategy ?? 0) == Module::STRATEGY_INSECURE) {
            return $this->makeResponse(
                '',
                Yii::t('user', 'Email change confirmation is disabled.'),
                503
            );
        }

        $user = $this->finder->findUserById($id);

        if ($user === null) {
            return $this->makeResponse(
                '',
                Yii::t('user', 'User not found.'),
                404
            );
        }

        $this->trigger(self::EVENT_BEFORE_EMAIL_CONFIRM);

        $result = $user->attemptEmailChange($code);

        if ($result['status']) {
            $this->trigger(self::EVENT_AFTER_EMAIL_CONFIRM);
            return $this->makeResponse(
                '',
                Yii::t('user', 'Email has been changed.')
            );
        }
        return $this->makeResponse(
            '',
            $result['message'],
            400
        );
    }
}
