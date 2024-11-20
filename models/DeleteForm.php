<?php

namespace aesis\user\models;

use aesis\user\Finder;
use aesis\user\Mailer;
use aesis\user\traits\ModuleTrait;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\db\Exception;
use yii\db\StaleObjectException;

class DeleteForm extends Model
{
    use ModuleTrait;

    protected $mailer;

    public function __construct(Mailer $mailer, Finder $finder, $config = [])
    {
        $this->mailer = $mailer;
        parent::__construct($config);
    }

    /**
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function sendDeleteMessage()
    {

        $user = \Yii::$app->user->identity;

        if ($user instanceof User) {
            /** @var Token $token */
            $token = Yii::createObject([
                'class' => $this->module->modelMap['Token'],
                'user_id' => $user->id,
                'type' => $this->module->modelMap['Token']::TYPE_ACCOUNT_DELETE,
            ]);

            if (!$token->save(false)) {
                return false;
            }

            if (!$this->mailer->sendDeleteMessage($user, $token)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function deleteAccount($token)
    {
        if ($token->user === null) {
            return false;
        }

        return $token->user->delete();
    }

    public function formName()
    {
        return 'delete-form';
    }
}
