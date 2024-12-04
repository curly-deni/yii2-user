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

class RecoveryForm extends Model
{
    use ModuleTrait;

    const SCENARIO_REQUEST = 'request';
    const SCENARIO_RESET = 'reset';

    public $email;

    public $password;

    public $signOutAll;

    protected $mailer;

    protected $finder;

    public function __construct(Mailer $mailer, Finder $finder, $config = [])
    {
        $this->mailer = $mailer;
        $this->finder = $finder;
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'email' => Yii::t('user', 'Email'),
            'password' => Yii::t('user', 'Password'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios(): array
    {
        return [
            self::SCENARIO_REQUEST => ['email'],
            self::SCENARIO_RESET => ['password', 'signOutAll'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            // email rules
            'emailTrim' => ['email', 'trim'],
            'emailRequired' => ['email', 'required'],
            'emailPattern' => ['email', 'email'],

            // password rules
            'passwordRequired' => ['password', 'required'],
            'passwordLength' => ['password', 'string', 'max' => 72, 'min' => 6],

            // flags rules
            'signOutAllRule' => ['signOutAll', 'boolean']
        ];
    }

    /**
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function sendRecoveryMessage()
    {
        if (!$this->validate()) {
            return false;
        }

        $user = $this->finder->findUserByEmail($this->email);

        if ($user instanceof User) {
            /** @var Token $token */
            $token = Yii::createObject([
                'class' => $this->module->modelMap['Token'],
                'user_id' => $user->id,
                'type' => $this->module->modelMap['Token']::TYPE_RECOVERY,
            ]);

            if (!$token->save(false)) {
                return false;
            }

            if (!$this->mailer->sendRecoveryMessage($user, $token)) {
                return false;
            }
            return $token;
        }

        return false;
    }

    /**
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function resetPassword($token)
    {
        if (!$this->validate() || $token->user === null) {
            return false;
        }

        if ($token->user->resetPassword($this->password, $this->signOutAll)) {
            $token->delete();
            return true;
        } else {
            return false;
        }
    }

    public function formName()
    {
        return 'recovery-form';
    }
}
