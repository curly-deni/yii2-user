<?php

namespace aesis\user\models;

use aesis\user\traits\ModuleTrait;
use RuntimeException;
use Yii;
use yii\base\Exception;
use yii\db\ActiveRecord;
use yii\helpers\Url;

/**
 * Token Active Record model.
 *
 * @property integer $user_id
 * @property string $code
 * @property integer $created_at
 * @property integer $type
 * @property string $url
 * @property bool $isExpired
 * @property User $user
 *
 */
class Token extends ActiveRecord
{
    use ModuleTrait;

    const TYPE_CONFIRMATION = 0;
    const TYPE_RECOVERY = 1;
    const TYPE_CONFIRM_NEW_EMAIL = 2;
    const TYPE_CONFIRM_OLD_EMAIL = 3;

    public function getUser()
    {
        return $this->hasOne($this->module->modelMap['User'], ['id' => 'user_id']);
    }

    public function getUrl()
    {
        $route = match ($this->type) {
            self::TYPE_CONFIRMATION => '/api/auth/user-confirm',
            self::TYPE_RECOVERY => '/auth/recover',
            self::TYPE_CONFIRM_NEW_EMAIL, self::TYPE_CONFIRM_OLD_EMAIL => '/api/auth/email-confirm',
            default => throw new RuntimeException(),
        };

        return Url::to([$route, 'id' => $this->user_id, 'code' => $this->code], true);
    }

    public function getIsExpired()
    {
        $expirationTime = match ($this->type) {
            self::TYPE_CONFIRMATION, self::TYPE_CONFIRM_NEW_EMAIL, self::TYPE_CONFIRM_OLD_EMAIL => $this->module->confirmWithin,
            self::TYPE_RECOVERY => $this->module->recoverWithin,
            default => throw new RuntimeException(),
        };

        return ($this->created_at + $expirationTime) < time();
    }

    /**
     * @throws Exception
     */
    public function beforeSave($insert)
    {
        if ($insert) {
            static::deleteAll(['user_id' => $this->user_id, 'type' => $this->type]);
            $this->setAttribute('created_at', time());
            $this->setAttribute('code', Yii::$app->security->generateRandomString());
        }

        return parent::beforeSave($insert);
    }

    /** @inheritdoc */
    public static function tableName()
    {
        return '{{%token}}';
    }

    /** @inheritdoc */
    public static function primaryKey()
    {
        return ['user_id', 'code', 'type'];
    }
}
