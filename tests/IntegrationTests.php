<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Model;

class IntegrationTests extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->schema()->create('users', function(Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        $this->schema()->create('users_i18n', function(Blueprint $table) {
            $table->integer('user_id')->unsigned();
            $table->string('locale', 2);
            $table->text('bio');
            $table->primary(['user_id', 'locale']);
        });

        $this->schema()->create('posts', function(Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
        });

        $this->schema()->create('posts_i18n', function(Blueprint $table) {
            $table->integer('post_id')->unsigned();
            $table->string('locale', 2);
            $table->string('title');
            $table->text('body');
            $table->primary(['post_id', 'locale']);
        });
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->schema()->drop('users');
        $this->schema()->drop('users_i18n');
        $this->schema()->drop('posts');
        $this->schema()->drop('posts_i18n');
    }

    public function testModelCanBeStoredAndRetrieved()
    {
        User::forceCreate([
            'name' => 'John Doe',
            'bio' => 'Lorem ipsum'
        ]);

        $user = User::first();
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('Lorem ipsum', $user->bio);
    }

    public function testModelCanBeStoredAndRetrievedInDifferentLocales()
    {
        $user = new User;
        $user->name = 'John Doe';
        $user->bio = 'Lorem ipsum';
        $user->save();

        $user->setLocale('de');
        $user->bio = 'DE Lorem ipsum';
        $user->save();

        $user = User::first();
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('Lorem ipsum', $user->bio);

        $user = User::inLocale('de')->first();
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('DE Lorem ipsum', $user->bio);
    }

    public function testFallbackLocaleIsUsedWhenNoMatchingLocaleIsFound()
    {
        User::forceCreate([
            'name' => 'John Doe',
            'bio' => 'Lorem ipsum'
        ]);

        $user = User::inLocale('de')->first();
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('Lorem ipsum', $user->bio);
    }

    public function testFallbackCanBeDisabled()
    {
        User::forceCreate([
            'name' => 'John Doe',
            'bio' => 'Lorem ipsum'
        ]);

        $user = User::inLocale('de')->withoutFallback()->first();
        $this->assertEquals('John Doe', $user->name);
        $this->assertNull($user->bio);
    }

    public function testModelCanBeUpdated()
    {
        $user = User::forceCreate([
            'name' => 'John Doe',
            'bio' => 'Lorem ipsum'
        ]);

        $user->name = 'Jane Doe';
        $user->save();

        $user->bio = 'Lorem ipsum 2';
        $user->save();

        $user = $user->first();
        $this->assertEquals('Jane Doe', $user->name);
        $this->assertEquals('Lorem ipsum 2', $user->bio);

        $user->name = 'John Doe';
        $user->bio = 'Lorem ipsum';
        $user->save();

        $user = $user->first();
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('Lorem ipsum', $user->bio);
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection($connection = 'default')
    {
        return Model::getConnectionResolver()->connection($connection);
    }
    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema($connection = 'default')
    {
        return $this->connection($connection)->getSchemaBuilder();
    }
}