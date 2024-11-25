<?php

namespace aesis\user\models\resource;

use aesis\rest\traits\hasScenarios;
use yii\helpers\ArrayHelper;

class User extends \aesis\user\models\User
{
    use hasScenarios;

    public function scenarios(): array
    {
        return ArrayHelper::merge(parent::scenarios(), [
            self::SCENARIO_CREATE => ['username', 'email'],
            self::SCENARIO_UPDATE => ['username', 'email']
        ]);
    }

}