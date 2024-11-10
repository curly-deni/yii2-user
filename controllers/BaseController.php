<?php

namespace aesis\user\controllers;

use aesis\user\Finder;
use aesis\user\traits\ApiTrait;
use aesis\user\traits\hasResponseTrait;
use yii\rest\Controller;

abstract class BaseController extends Controller
{

    use hasResponseTrait;
    use ApiTrait;

    public $allRoutesNeedAuth = false;

    protected $finder;

    public function __construct($id, $module, Finder $finder, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->finder = $finder;
    }

}