<?php

namespace aesis\user\models\resource;

use aesis\rest\traits\hasScenarios;
use aesis\user\traits\hasOwnerFind;

class AuthKey extends \aesis\user\models\AuthKey
{
    use hasScenarios;
    use hasOwnerFind;

    public function fields(): array
    {
        $fields = parent::fields();
        unset($fields['key']);
        return $fields;
    }
}