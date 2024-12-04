<?php

namespace aesis\user\controllers;

use aesis\user\Finder;
use aesis\traits\ApiTrait;
use yii\rest\Controller;

abstract class BaseController extends Controller
{

    use ApiTrait;
    public $allRoutesNeedAuth = false;

    protected $finder;
    public $module;

    public function __construct($id, $module, Finder $finder, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->finder = $finder;
        $this->module = $module;
    }

}