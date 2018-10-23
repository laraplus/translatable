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
            $table->integer('age')->nullable();
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
            $table->string('image')->nullable();
            $table->timestamps();
        });

        $this->schema()->create('posts_i18n', function (Blueprint $table) {
            $table->integer('post_id')->unsigned();
            $table->string('locale', 2);
            $table->string('title');
            $table->text('body')->nullable();
            $table->primary(['post_id', 'locale']);
        });

        $this->schema()->create('tags', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('parent_id')->unsigned()->nullable();
            $table->timestamps();
        });

        $this->schema()->create('tags_i18n', function (Blueprint $table) {
            $table->integer('tag_id')->unsigned();
            $table->string('locale', 2);
            $table->string('title');
        });

        $this->schema()->create('post_tag', function (Blueprint $table) {
            $table->integer('post_id')->unsigned()->index();
            $table->integer('tag_id')->unsigned()->index();
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
