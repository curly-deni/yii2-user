<?php

namespace aesis\user\models\resource;

use aesis\user\traits\hasOwnerFind;
use aesis\rest\traits\hasScenarios;
use Yii;
use yii\helpers\ArrayHelper;

class ApiKey extends \aesis\user\models\ApiKey
{
    use hasScenarios;
    use hasOwnerFind;

    public static function getMaskString($value, $visibleLength = 4)
    {
        if (strlen($value) > $visibleLength) {
            return str_repeat('*', strlen($value) - $visibleLength) . substr($value, -$visibleLength);
        }
        return $value;
    }

    public function scenarios(): array
    {
        return ArrayHelper::merge(parent::scenarios(), [
            self::SCENARIO_CREATE => ['name'],
            self::SCENARIO_UPDATE => ['name']
        ]);
    }

    public function getMaskedKey()
    {
        return self::getMaskString($this->key, 5);
    }

    public function fields(): array
    {
        $fields = parent::fields();

        if ($this->scenario !== self::SCENARIO_CREATE) {
            $fields['key'] = 'maskedKey';
        }

        return $fields;
    }

    public function beforeValidate()
    {
        if (!isset($this->key)) {
            $key = Yii::$app->security->generateRandomString();
            $user = \Yii::$app->user->getIdentity();

            $this->key = $key;
            $this->user_id = $user->id;
            $this->by_creds = false;
            $this->last_usage_at = time();
        }
        return parent::beforeValidate();
    }
}