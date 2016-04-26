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
            'database' => ':memory:',
            'prefix' => ''
        ]);

        $capsule->setAsGlobal();

        $capsule->bootEloquent();

        TranslatableConfig::currentLocaleGetter(function(){
            return 'en';
        });

        TranslatableConfig::fallbackLocaleGetter(function(){
            return 'en';
        });

        require_once __DIR__.'/stubs/Post.php';
    }
}