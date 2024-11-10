<?php

namespace aesis\user\models;

use aesis\user\Finder;
use aesis\user\Mailer;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\db\Exception;

class ResendForm extends Model
{
    public $email;

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
    public function rules(): array
    {
        return [
            'emailRequired' => ['email', 'required'],
            'emailPattern' => ['email', 'email'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'email' => Yii::t('user', 'Email'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function formName(): string
    {
        return 'resend-form';
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function resend()
    {
        if (!$this->validate()) {
            return false;
        }

        $user = $this->finder->findUserByEmail($this->email);

        if ($user instanceof User && !$user->isConfirmed) {
            /** @var Token $token */
            $token = Yii::createObject([
                'class' => Token::class,
                'user_id' => $user->id,
                'type' => Token::TYPE_CONFIRMATION,
            ]);
            $token->save(false);
            $this->mailer->sendConfirmationMessage($user, $token);
            return true;
        }
        return false;
    }
}
