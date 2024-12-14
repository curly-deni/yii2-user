<?php

namespace aesis\user\controllers;

use aesis\user\controllers\BaseController as Controller;
use aesis\user\models\User;
use aesis\user\Module;
use aesis\user\traits\EventTrait;
use aesis\user\traits\ModuleTrait;
use Yii;
use yii\base\InvalidConfigException;
use yii\filters\VerbFilter;


class ConfirmationController extends Controller
{
    use ModuleTrait;
    use EventTrait;

    const EVENT_AFTER_USER_CONFIRM = 'afterUserConfirm';
    const EVENT_AFTER_EMAIL_CONFIRM = 'afterEmailConfirm';
    const EVENT_AFTER_RESEND = 'afterResend';

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {

        $behaviors = parent::behaviors();

        $behaviors['access']['only'] = ['index', 'email', 'resend', 'status'];
        $behaviors['access']['rules'][] = ['allow' => true, 'actions' => ['index', 'email', 'resend', 'status'], 'roles' => ['?']];
        $behaviors['access']['rules'][] = ['allow' => true, 'actions' => ['index', 'email', 'resend', 'status'], 'roles' => ['@']];

        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'index' => ['get'],
                'email' => ['get'],
                'resend' => ['post'],
                'status' => ['get'],
            ],
        ];

        return $behaviors;
    }

    public function sendResponse($success, $message)
    {
        if ($this->module->useFrontendConfirmPage && $this->module->frontendConfirmPage) {
            return $this->redirect([
                $this->module->frontendConfirmPage,
                'success' => $success,
                'message' => $message
            ]);
        }
        return $this->makeResponse('', $message, $success ? 200 : 400);
    }

    public function actionIndex($id, $code)
    {
        $user = $this->finder->findUserById($id);

        if ($user === null || !($this->module->enableConfirmation ?? false)) {
            return $this->sendResponse(false, Yii::t('user', 'Account confirmation is disabled.'));
        }

        $event = $this->getUserEvent($user);
        $result = $user->attemptConfirmation($code);

        if ($result['success']) {
            $this->trigger(self::EVENT_AFTER_USER_CONFIRM, $event);
        }

        return $this->sendResponse($result['success'], $result['message']);
    }

    public function actionStatus($email)
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

        $model = Yii::createObject($this->module->modelMap['ResendForm']);

        $data = Yii::$app->getRequest()->post();
        $model->email = $data['email'] ?? null;
        $result = $model->resend();

        if ($result) {
            $event = $this->getTokenEvent($result->user, $result);

            $this->trigger(self::EVENT_AFTER_RESEND, $event);
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

    public function actionEmail($id, $code)
    {
        if (($this->module->emailChangeStrategy ?? 0) == Module::STRATEGY_INSECURE) {
            return $this->sendResponse(false, Yii::t('user', 'Email change confirmation is disabled.'));
        }

        $user = $this->finder->findUserById($id);

        if ($user === null) {
            return $this->sendResponse(false, Yii::t('user', 'User not found.'));
        }

        $result = $user->attemptEmailChange($code);

        if ($result['status']) {
            $this->trigger(self::EVENT_AFTER_EMAIL_CONFIRM);
            return $this->sendResponse(true, Yii::t('user', 'Email has been changed.'));
        }

        return $this->sendResponse(false, $result['message']);
    }
}
