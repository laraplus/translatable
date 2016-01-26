<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Laraplus\Data\LocaleSettings;

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

        LocaleSettings::setAvailable(['en','de']);
        LocaleSettings::setCurrent('en');
        LocaleSettings::setFallback('en');

        require_once __DIR__.'/stubs/Post.php';
    }
}