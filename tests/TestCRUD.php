<?php

class TestCRUD extends IntegrationTestCase
{
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

        $user = User::translateInto('de')->first();
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('DE Lorem ipsum', $user->bio);
    }

    public function testModelWithIdenticalTranslationsIsSaved()
    {
        User::forceCreate([
            'name' => 'John Doe'
        ], [
            'en' => ['bio' => 'Sample bio'],
            'de' => ['bio' => 'Sample bio'],
        ]);

        $this->assertEquals('Sample bio', User::withoutFallback()->onlyTranslated('en')->first()->bio);
        $this->assertEquals('Sample bio', User::withoutFallback()->onlyTranslated('de')->first()->bio);
    }

    public function testFallbackLocaleIsUsedWhenNoMatchingLocaleIsFound()
    {
        User::forceCreate([
            'name' => 'John Doe',
            'bio' => 'Lorem ipsum'
        ]);

        $user = User::translateInto('de')->first();
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('Lorem ipsum', $user->bio);
    }

    public function testFallbackCanBeDisabled()
    {
        User::forceCreate([
            'name' => 'John Doe',
            'bio' => 'Lorem ipsum'
        ]);

        $user = User::translateInto('de')->withoutFallback()->first();
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

        $queryWithFallback = Post::translateInto('de')->where('title', 'Title');
        $queryWithoutFallback = Post::translateInto('de')->withoutFallback()->where('title', 'Title');

        $this->assertEquals(2, $queryWithFallback->count());
        $this->assertEquals(1, $queryWithoutFallback->count());
    }

    public function testWhereTranslatedWithArray()
    {
        Post::forceCreateInLocale('de', ['title'  => 'Title']);
        Post::forceCreateInLocale('en', ['title'  => 'Title']);

        $result = Post::translateInto('de')->where(['title' => 'Title'])->get();

        $this->assertEquals(2, $result->count());
    }

    public function testOrderByTranslated()
    {
        Post::forceCreate(['title' => 'Title 1']);
        Post::forceCreate(['title' => 'Title 2']);

        $this->assertEquals('Title 2', Post::orderBy('title', 'desc')->first()->title);
    }

    public function testOrderByTranslatedWithFallback()
    {
        Post::forceCreateInLocale('de', ['title'  => 'Title 1']);
        Post::forceCreateInLocale('en', ['title'  => 'Title 2']);

        $queryWithFallback = Post::translateInto('de')->orderBy('title', 'desc');
        $queryWithoutFallback = Post::translateInto('de')->withoutFallback()->orderBy('title', 'desc');

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

        $this->assertEquals(4, Post::translateInto('en')->withFallback('de')->withUntranslated()->count());
        $this->assertEquals(3, Post::translateInto('en')->withFallback('de')->onlyTranslated()->count());
        $this->assertEquals(2, Post::onlyTranslated('en')->withoutFallback()->count());
        $this->assertEquals(3, Post::onlyTranslated('en')->withFallback('de')->count());
        $this->assertEquals(2, Post::onlyTranslated('en')->withFallback('de')->where('title', 'LIKE', '%EN')->count());
    }

    public function testDelete()
    {
        $post = Post::forceCreateInLocale('de', ['title'  => 'Title DE']);
        $post->forceSaveTranslation('en', ['title'  => 'Title EN']);
        Post::forceCreateInLocale('en', ['title'  => 'Title 2 EN']);

        $post->delete();

        $this->assertCount(1, Post::all());
        $this->assertCount(1, Post::i18nQuery()->get());
    }

    public function testIncrementDecrement()
    {
        $user = User::forceCreate([
            'name' => 'John Doe',
            'bio' => 'Lorem ipsum ...',
            'age' => 20
        ]);

        $user->increment('age');
        $this->assertEquals(21, User::first()->age);

        $user->decrement('age');
        $this->assertEquals(20, User::first()->age);
    }
}