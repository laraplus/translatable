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
        $user = User::forceCreate([
            'name' => 'John Doe',
            'bio'  => 'Lorem ipsum'
        ]);

        $user->forceSaveTranslation('de', [
            'bio' => 'DE Lorem ipsum'
        ]);

        $user = User::first();
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('Lorem ipsum', $user->bio);

        $user = User::translate('de')->first();
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('DE Lorem ipsum', $user->bio);
    }

    public function testFallbackLocaleIsUsedWhenNoMatchingLocaleIsFound()
    {
        User::forceCreate([
            'name' => 'John Doe',
            'bio' => 'Lorem ipsum'
        ]);

        $user = User::translate('de')->first();
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('Lorem ipsum', $user->bio);
    }

    public function testFallbackCanBeDisabled()
    {
        User::forceCreate([
            'name' => 'John Doe',
            'bio' => 'Lorem ipsum'
        ]);

        $user = User::translate('de')->withoutFallback()->first();
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

    public function testWhereTranslated()
    {
        Post::forceCreate(['title' => 'Title 1']);
        Post::forceCreate(['title' => 'Title 2']);

        $this->assertEquals(1, Post::where('title', 'Title 1')->count());
    }

    public function testWhereTranslatedWithFallback()
    {
        Post::forceCreateInLocale('de', ['title'  => 'Title']);
        Post::forceCreateInLocale('en', ['title'  => 'Title']);

        $queryWithFallback = Post::translate('de')->where('title', 'Title');
        $queryWithoutFallback = Post::withoutFallback()->translate('de')->where('title', 'Title');

        $this->assertEquals(2, $queryWithFallback->count());
        $this->assertEquals(1, $queryWithoutFallback->count());
    }

    public function testOrderByTranslated()
    {
        Post::forceCreate(['title' => 'Title 1']);
        Post::forceCreate(['title' => 'Title 2']);

        $this->assertEquals('Title 2', Post::orderByTranslated('title', 'desc')->first()->title);
    }

    public function testOrderByTranslatedWithFallback()
    {
        Post::forceCreateInLocale('de', ['title'  => 'Title 1']);
        Post::forceCreateInLocale('en', ['title'  => 'Title 2']);

        $queryWithFallback = Post::translate('de')->orderBy('title', 'desc');
        $queryWithoutFallback = Post::withoutFallback()->translate('de')->orderBy('title', 'desc');

        $this->assertEquals('Title 2', $queryWithFallback->first()->title);
        $this->assertEquals('Title 1', $queryWithoutFallback->first()->title);
    }

    public function testMacros()
    {
        $post = Post::forceCreateInLocale('de', ['title'  => 'Title DE']);
        $post->forceSaveTranslation('en', ['title'  => 'Title EN']);
        Post::forceCreateInLocale('en', ['title'  => 'Title 2 EN']);
        Post::forceCreateInLocale('de', ['title'  => 'Title 2 DE']);
        Post::forceCreate(['id'  => 10]); // no translations

        $this->assertEquals(4, Post::translate('en')->withFallback('de')->withUntranslated()->count());
        $this->assertEquals(3, Post::translate('en')->withFallback('de')->onlyTranslated()->count());
        $this->assertEquals(2, Post::onlyTranslated('en')->withoutFallback()->count());
        $this->assertEquals(3, Post::onlyTranslated('en')->withFallback('de')->count());
        $this->assertEquals(2, Post::onlyTranslated('en')->withFallback('de')->where('title', 'LIKE', '%EN')->count());
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