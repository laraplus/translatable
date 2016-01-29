<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Model;

abstract class IntegrationTestCase extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->schema()->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        $this->schema()->create('users_i18n', function (Blueprint $table) {
            $table->integer('user_id')->unsigned();
            $table->string('locale', 2);
            $table->text('bio');
            $table->primary(['user_id', 'locale']);
        });

        $this->schema()->create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
        });

        $this->schema()->create('posts_i18n', function (Blueprint $table) {
            $table->integer('post_id')->unsigned();
            $table->string('locale', 2);
            $table->string('title');
            $table->text('body')->nullable();
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