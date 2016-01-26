<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Laraplus\Data\TranslatableConfig;

abstract class TestCase extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $capsule = new Capsule;

        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => __DIR__.'/stubs/database.sqlite',
            'prefix' => ''
        ]);

        $capsule->setAsGlobal();

        $capsule->bootEloquent();

        TranslatableConfig::setAvailable(['en','de']);
        TranslatableConfig::setCurrent('en');
        TranslatableConfig::setFallback('en');

        require_once __DIR__.'/stubs/Post.php';
    }
}