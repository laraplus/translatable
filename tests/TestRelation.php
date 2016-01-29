<?php

class TestRelation extends IntegrationTestCase
{
    public function testTranslationsCanBeRetrievedByRelation()
    {
        $this->createTwoPostsWithOneTranslatedInThreeLocales();

        $model = Post::find(1);
        $this->assertCount(3, $model->translations);
    }

    public function testTranslationsCanBeUpdatedByRelation()
    {
        $this->createTwoPostsWithOneTranslatedInThreeLocales();

        $model = Post::find(1);
        $model->translations()->where('locale', 'en')->update(['title' => 'New Title']);

        $this->assertEquals('New Title', Post::find(1)->title);
    }

    public function testTranslatedAttributesCanBeRetrievedInDifferentLocales()
    {
        $this->createTwoPostsWithOneTranslatedInThreeLocales();

        $model = Post::find(1);
        $model->translations()->where('locale', 'en')->update(['title' => 'New Title']);

        $this->assertEquals('New Title', Post::find(1)->title);
    }

    protected function createTwoPostsWithOneTranslatedInThreeLocales()
    {
        $post = Post::forceCreateInLocale('de', ['title'  => 'Title DE']);
        $post->forceSaveTranslation('en', ['title'  => 'Title EN']);
        $post->forceSaveTranslation('fr', ['title'  => 'Title FR']);
        Post::forceCreateInLocale('de', ['title'  => 'Title 2']);
    }
}