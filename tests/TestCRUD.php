<?php

class TestCRUD extends IntegrationTestCase
{
    public function testModelCanBeStoredAndRetrieved()
    {
        User::forceCreate([
            'name' => 'John Doe',
            'bio' => 'Lorem ipsum',
        ]);

        $user = User::first();
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('Lorem ipsum', $user->bio);
    }

    public function testModelCanBeStoredAndRetrievedInDifferentLocales()
    {
        $user = User::forceCreate([
            'name' => 'John Doe',
            'bio'  => 'Lorem ipsum',
        ]);

        $user->forceSaveTranslation('de', [
            'bio' => 'DE Lorem ipsum',
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
            'name' => 'John Doe',
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
            'bio' => 'Lorem ipsum',
        ]);

        $user = User::translateInto('de')->first();
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('Lorem ipsum', $user->bio);
    }

    public function testFallbackCanBeDisabled()
    {
        User::forceCreate([
            'name' => 'John Doe',
            'bio' => 'Lorem ipsum',
        ]);

        $user = User::translateInto('de')->withoutFallback()->first();
        $this->assertEquals('John Doe', $user->name);
        $this->assertNull($user->bio);
    }

    public function testModelCanBeUpdated()
    {
        $user = User::forceCreate([
            'name' => 'John Doe',
            'bio' => 'Lorem ipsum',
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

    public function testModelCanBeMassUpdated()
    {
        Post::forceCreate([
            'id' => 1,
            'title' => 'Lorem ipsum',
        ]);

        Post::forceCreate([
            'id' => 2,
            'title' => 'Lorem ipsum',
        ]);

        Post::where('title', 'Lorem ipsum')->update([
            'title' => 'Lorem ipsum 2'
        ]);

        $this->assertEquals('Lorem ipsum 2', Post::find(1)->title);
        $this->assertEquals('Lorem ipsum 2', Post::find(2)->title);
    }

    public function testSaveTranslationHelper()
    {
        User::forceCreate([
            'name' => 'John Doe',
            'bio' => 'Lorem ipsum',
        ]);

        $user = User::first();
        $user->forceSaveTranslation('de', ['bio' => 'Lorem ipsum DE']);
        $user->forceSaveTranslation('fr', ['bio' => 'Lorem ipsum FR']);

        $this->assertEquals('Lorem ipsum', User::translateInto('en')->first()->bio);
        $this->assertEquals('Lorem ipsum DE', User::translateInto('de')->first()->bio);
        $this->assertEquals('Lorem ipsum FR', User::translateInto('fr')->first()->bio);
    }

    public function testSaveTranslationWithoutSideEffectsDependingOrderOperations()
    {
        Post::forceCreate([]);

        $post = Post::first();
        $post->forceSaveTranslation('de', ['title' => 'Title DE']);
        $post->forceSaveTranslation('fr', ['title' => 'Title FR']);
        $post->forceSaveTranslation('de', ['body' => 'Body DE']);
        $post->forceSaveTranslation('fr', ['body' => 'Body FR']);

        $this->assertEquals('Title DE', Post::translateInto('de')->first()->title);
        $this->assertEquals('Body DE', Post::translateInto('de')->first()->body);
        $this->assertEquals('Title FR', Post::translateInto('fr')->first()->title);
        $this->assertEquals('Body FR', Post::translateInto('fr')->first()->body);
    }

    public function testSaveTranslationWithoutSideEffectsWhenUpdateModel()
    {
        Post::forceCreate([]);

        $post = Post::first();

        $post->forceSaveTranslation('en', [
            'title' => 'Title EN v2',
            'body' => 'Body EN v2',
        ]);
        $post->forceSaveTranslation('de', [
            'title' => 'Title DE v2',
            'body' => 'Body DE v2',
        ]);

        $post->update(['image' => 'blog_cover.png']);

        $this->assertEquals('Title DE v2', Post::translateInto('de')->first()->title);
        $this->assertEquals('Body DE v2', Post::translateInto('de')->first()->body);
        $this->assertEquals('Title EN v2', Post::translateInto('en')->first()->title);
        $this->assertEquals('Body EN v2', Post::translateInto('en')->first()->body);
        $this->assertEquals('blog_cover.png', Post::first()->image);
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

    public function testDeleteWithoutTranslations()
    {
        Post::forceCreate(['title'  => 'Title']);
        Post::i18nQuery()->truncate();

        $post = Post::first();
        $post->delete();

        $this->assertCount(0, Post::all());
    }

    public function testIncrementDecrement()
    {
        $user = User::forceCreate([
            'name' => 'John Doe',
            'bio' => 'Lorem ipsum ...',
            'age' => 20,
        ]);

        $user->increment('age');
        $this->assertEquals(21, User::first()->age);

        $user->decrement('age');
        $this->assertEquals(20, User::first()->age);
    }
}
