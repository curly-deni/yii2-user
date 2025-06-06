<?php

namespace aesis\user\models;

use aesis\traits\helpers\InternalChecker;
use aesis\user\Finder;
use aesis\user\helpers\Password;
use aesis\user\Mailer;
use aesis\user\Module;
use aesis\user\traits\EventTrait;
use aesis\user\traits\ModuleTrait;
use Exception;
use RuntimeException;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\StaleObjectException;
use yii\di\NotInstantiableException;
use yii\helpers\ArrayHelper;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "user".
 *
 * @property int $id
 * @property string $username
 * @property string $email
 * @property string $role
 * @property string|null $unconfirmed_email
 * @property string $password_hash
 * @property int|null $confirmed_at
 * @property int|null $blocked_at
 * @property int $created_at
 * @property int $updated_at
 * @property int|null $flags
 *
 * @property ApiKey[] $apiKeys
 * @property AuthKey[] $authKeys
 * @property Profile $profile
 * @property Token[] $tokens
 */
class User extends ActiveRecord implements IdentityInterface
{
    use ModuleTrait;
    use EventTrait;

    const BEFORE_CREATE = 'beforeCreate';
    const AFTER_CREATE = 'afterCreate';
    const BEFORE_REGISTER = 'beforeRegister';
    const AFTER_REGISTER = 'afterRegister';
    const BEFORE_CONFIRM = 'beforeConfirm';
    const AFTER_CONFIRM = 'afterConfirm';

    const ADMIN_ROLE = "admin";
    const USER_ROLE = "user";

    const OLD_EMAIL_CONFIRMED = 0b1;
    const NEW_EMAIL_CONFIRMED = 0b10;

    public $password;
    public $passwordGenerated = false;

    private $_profile;

    public static $usernameRegexp = '/^[-a-zA-Z0-9_\.@]+$/';

    /**
     * @throws NotInstantiableException
     * @throws InvalidConfigException
     */
    protected function getFinder()
    {
        return Yii::$container->get(Finder::class);
    }

    /**
     * @throws NotInstantiableException
     * @throws InvalidConfigException
     */
    protected function getMailer()
    {
        return Yii::$container->get(Mailer::class);
    }

    public function getIsConfirmed()
    {
        return $this->confirmed_at != null;
    }

    public function getIsBlocked()
    {
        return $this->blocked_at != null;
    }

    public function getProfile()
    {
        return $this->hasOne($this->module->modelMap['Profile'], ['id' => 'id']);
    }

    public function setProfile($profile)
    {
        $this->_profile = $profile;
    }

    public function getId()
    {
        return $this->getAttribute('id');
    }

    public function getAuthKey($session = false)
    {
        if (InternalChecker::isInternalApi()) {
            return $this->module->modelMap['AuthKey']::getNewKey($this->getId(), $session);
        }
        return $this->module->modelMap['ApiKey']::getNewKey($this->getId(), true);
    }

    public static function removeCurrentKey()
    {
        $authKey = self::getModuleStatic()->modelMap['AuthKey'];

        $model = $authKey::getCurrentKey();
        if ($model)
            return $model->delete();
        return true;
    }

    public function getCurrentKey()
    {
        if (InternalChecker::isInternalApi()) {
            return $this->module->modelMap['AuthKey']::getCurrentKey();
        }
        return $this->module->modelMap['ApiKey']::getCurrentKey();
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'username' => 'Username',
            'email' => 'Email',
            'unconfirmed_email' => 'Unconfirmed Email',
            'password_hash' => 'Password Hash',
            'confirmed_at' => 'Confirmed At',
            'blocked_at' => 'Blocked At',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'flags' => 'Flags',
            'role' => 'Role'
        ];
    }

    /** @inheritdoc */
    public function behaviors(): array
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /** @inheritdoc */
    public function scenarios(): array
    {
        $scenarios = parent::scenarios();
        return ArrayHelper::merge($scenarios, [
            'register' => ['username', 'email', 'password'],
            'create' => ['username', 'email', 'password', 'role'],
            'update' => ['username', 'email', 'password'],
            'settings' => ['username', 'email', 'password'],
        ]);
    }

    public function fields(): array
    {
        $fields = parent::fields();

        unset($fields['password_hash']);

        return $fields;
    }

    /** @inheritdoc */
    public function rules(): array
    {
        return [
            // username rules
            'usernameTrim' => ['username', 'trim'],
            'usernameRequired' => ['username', 'required', 'on' => ['register', 'create', 'connect', 'update']],
            'usernameMatch' => ['username', 'match', 'pattern' => static::$usernameRegexp],
            'usernameLength' => ['username', 'string', 'min' => 3, 'max' => 255],
            'usernameUnique' => [
                'username',
                'unique',
                'message' => Yii::t('user', 'This username has already been taken')
            ],

            // email rules
            'emailTrim' => ['email', 'trim'],
            'emailRequired' => ['email', 'required', 'on' => ['register', 'connect', 'create', 'update']],
            'emailPattern' => ['email', 'email'],
            'emailLength' => ['email', 'string', 'max' => 255],
            'emailUnique' => [
                'email',
                'unique',
                'message' => Yii::t('user', 'This email address has already been taken')
            ],

            // password rules
            'passwordRequired' => ['password', 'required', 'on' => ['register']],
            'passwordLength' => ['password', 'string', 'min' => 6, 'max' => 72, 'on' => ['register', 'create']],

            // role rule
            'roleRequired' => ['role', 'required', 'on' => ['register', 'create', 'update', 'settings']],
        ];
    }

    public function validateAuthKey($authKey)
    {
        return $this->module->modelMap['AuthKey']::validateKey($this->getId(), $authKey, InternalChecker::isIgnoreUserAgent());
    }

    /**
     * @throws \yii\db\Exception
     */
    public function create()
    {
        if (!$this->getIsNewRecord()) {
            throw new RuntimeException('Calling "' . __CLASS__ . '::' . __METHOD__ . '" on existing user');
        }

        $transaction = $this->getDb()->beginTransaction();

        try {
            if (empty($this->password)) {
                $this->password = Password::generate(12);
                $this->passwordGenerated = true;
            }

            $this->trigger(self::BEFORE_CREATE);

            if (!$this->save()) {
                $transaction->rollBack();
                return false;
            }

            $event = $this->getUserEvent($this);
            $this->trigger(self::AFTER_CREATE, $event);

            $this->confirm();
            $this->mailer->sendWelcomeMessage($this, null, true);

            $transaction->commit();

            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            Yii::warning($e->getMessage());
            throw $e;
        }
    }

    /**
     * @throws \yii\db\Exception
     * @throws InvalidConfigException
     */
    public function register()
    {
        if (!$this->getIsNewRecord()) {
            throw new RuntimeException('Calling "' . __CLASS__ . '::' . __METHOD__ . '" on existing user');
        }

        $transaction = $this->getDb()->beginTransaction();

        try {
            $this->confirmed_at = $this->module->enableConfirmation ? null : time();
            $this->password = $this->password ?? Password::generate(8);

            $this->trigger(self::BEFORE_REGISTER);

            if (!$this->save()) {
                $transaction->rollBack();
                return false;
            }

            if ($this->module->enableConfirmation) {
                /** @var Token $token */
                $token = Yii::createObject(['class' => $this->module->modelMap['Token'], 'type' => $this->module->modelMap['Token']::TYPE_CONFIRMATION]);
                $token->link('user', $this);
            }
            $event = $this->getUserEvent($this);
            $this->mailer->sendWelcomeMessage($this, $token ?? null);

            $this->trigger(self::AFTER_REGISTER, $event);

            $transaction->commit();

            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            Yii::warning($e->getMessage());
            throw $e;
        }
    }

    /**
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function attemptConfirmation($code)
    {
        $token = $this->finder->findTokenByParams($this->id, $code, $this->module->modelMap['Token']::TYPE_CONFIRMATION);

        if ($token instanceof Token && !$token->isExpired) {
            $token->delete();
            if (($success = $this->confirm())) {
                Yii::$app->user->login($this, $this->module->rememberFor);
                $message = Yii::t('user', 'Thank you, registration is now complete.');
            } else {
                $message = Yii::t('user', 'Something went wrong and your account has not been confirmed.');
            }
        } else {
            $success = false;
            $message = Yii::t('user', 'The confirmation link is invalid or expired. Please try requesting a new one.');
        }

        return ['success' => $success, 'message' => $message];
    }

    /**
     * @throws StaleObjectException
     * @throws \yii\db\Exception
     * @throws Throwable
     */
    public function attemptEmailChange($code)
    {
        // TODO refactor method

        /** @var Token $token */
        $token = $this->finder->findToken([
            'user_id' => $this->id,
            'code' => $code,
        ])->andWhere(['in', 'type', [$this->module->modelMap['Token']::TYPE_CONFIRM_NEW_EMAIL, $this->module->modelMap['Token']::TYPE_CONFIRM_OLD_EMAIL]])->one();

        if (empty($this->unconfirmed_email) || $token === null || $token->isExpired) {
            return [
                'status' => false,
                'message' => Yii::t('user', 'Your confirmation token is invalid or expired')
            ];
        } else {
            $token->delete();

            if (empty($this->unconfirmed_email)) {
                return [
                    'status' => false,
                    'message' => Yii::t('user', 'An error occurred processing your request')
                ];
            } elseif (!$this->finder->findUser(['email' => $this->unconfirmed_email])->exists()) {

                $status = false;
                $message = '';

                if ($this->module->emailChangeStrategy == Module::STRATEGY_SECURE) {
                    switch ($token->type) {
                        case $this->module->modelMap['Token']::TYPE_CONFIRM_NEW_EMAIL:
                            $this->flags |= self::NEW_EMAIL_CONFIRMED;
                            $status = true;
                            $message = Yii::t('user', 'Awesome, almost there. Now you need to click the confirmation link sent to your old email address');
                            break;
                        case $this->module->modelMap['Token']::TYPE_CONFIRM_OLD_EMAIL:
                            $this->flags |= self::OLD_EMAIL_CONFIRMED;
                            $status = true;
                            $message = Yii::t('user', 'Awesome, almost there. Now you need to click the confirmation link sent to your new email address');
                            break;
                    }
                }
                if ($this->module->emailChangeStrategy == Module::STRATEGY_DEFAULT
                    || ($this->flags & self::NEW_EMAIL_CONFIRMED && $this->flags & self::OLD_EMAIL_CONFIRMED)) {
                    $this->email = $this->unconfirmed_email;
                    $this->unconfirmed_email = null;
                    $status = true;
                    $message = Yii::t('user', 'Your email address has been changed');
                }
                $this->save(false);

                return ['status' => $status, 'message' => $message];
            }
        }

        return ['status' => false, 'message' => Yii::t('user', 'Something went wrong and your email address has not been changed.')];
    }

    public function confirm()
    {
        $this->trigger(self::BEFORE_CONFIRM);
        $result = (bool)$this->updateAttributes(['confirmed_at' => time()]);
        $this->trigger(self::AFTER_CONFIRM);
        return $result;
    }

    /**
     * @throws \yii\base\Exception
     */
    public function resetPassword($password, $signOutAll = false)
    {
        $result = (bool)$this->updateAttributes(['password_hash' => Password::hash($password)]);
        if ($result && $signOutAll) {
            $this->removeAllKeys();
        }
        return $result;
    }


    /**
     * @throws \yii\base\Exception
     */
    public function block()
    {
        return (bool)$this->updateAttributes([
            'blocked_at' => time(),
            'auth_key' => Yii::$app->security->generateRandomString(),
        ]);
    }

    public function unblock()
    {
        return (bool)$this->updateAttributes(['blocked_at' => null]);
    }

    public function generateUsername()
    {
        // try to use name part of email
        $username = explode('@', $this->email)[0];
        $this->username = $username;
        if ($this->validate(['username'])) {
            return $this->username;
        }

        // valid email addresses are less restricitve than our
        // valid username regexp so fallback to 'user123' if needed:
        if (!preg_match(self::$usernameRegexp, $username)) {
            $username = 'user';
        }
        $this->username = $username;

        $max = $this->finder->userQuery->max('id');

        // generate username like "user1", "user2", etc...
        do {
            $this->username = $username . ++$max;
        } while (!$this->validate(['username']));

        return $this->username;
    }

    public function beforeValidate()
    {
        if (empty($this->username)) {
            $this->generateUsername();
        }

        if (empty($this->role)) {
            $this->role = self::USER_ROLE;
        }

        return parent::beforeValidate();
    }

    /** @inheritdoc
     * @throws \yii\base\Exception
     */
    public function beforeSave($insert)
    {
        if (!empty($this->password)) {
            $this->setAttribute('password_hash', Password::hash($this->password));
        }

        return parent::beforeSave($insert);
    }

    /** @inheritdoc
     * @throws InvalidConfigException
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($insert) {
            if (!isset($this->_profile)) {
                /** @var Profile $this ->_profile */
                $this->_profile = Yii::createObject([
                    'class' => $this->module->modelMap['Profile'],
                    'id' => $this->id
                ]);
            }
            $this->_profile->link('user', $this);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user';
    }

    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        $key = self::getModuleStatic()->modelMap['ApiKey'];

        return $key::findIdentity($token);
    }

    public function getApiKeys()
    {
        return $this->hasMany($this->module->modelMap['ApiKey'], ['user_id' => 'id']);
    }

    public function getApiKeysByCreds()
    {
        return $this->hasMany($this->module->modelMap['ApiKey'], ['user_id' => 'id', 'by_creds' => true]);
    }

    public function getAuthKeys()
    {
        return $this->hasMany($this->module->modelMap['AuthKey'], ['user_id' => 'id']);
    }

    public function removeAllKeys()
    {
        $this->module->modelMap['ApiKey']::deleteAll([
            'by_creds' => true,
            'user_id' => $this->id
        ]);

        $this->module->modelMap['AuthKey']::deleteAll(['user_id' => $this->id]);
    }

    public function getTokens()
    {
        return $this->hasMany($this->module->modelMap['Token'], ['user_id' => 'id']);
    }

    public function getIsAdmin()
    {
        return $this->role === self::ADMIN_ROLE;
    }

    public function canAccessViaRole($role): bool
    {
        return $this->role === $role;
    }
}