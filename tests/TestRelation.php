<?php

class TestRelation extends IntegrationTestCase
{
    public function testTranslationsCanBeRetrievedByRelation()
    {
        $this->createTwoPostsWithOneTranslatedInThreeLocales();

        $model = Post::find(1);
        $this->assertCount(3, $model->translations);
        $this->assertEquals('Title EN', $model->translate('en')->title);
        $this->assertEquals('Title FR', $model->translate('fr')->title);
        $this->assertEquals('Title DE', $model->translate('de')->title);
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

    public function testSyncInManyToManyRelationship()
    {
        $post = Post::forceCreate(['title'  => 'Post title']);
        $tag = Tag::forceCreate(['title'  => 'Tag title']);

        $post->tags()->sync([$tag->id]);

        $this->assertEquals(1, Post::first()->tags->count());
        $this->assertEquals('Tag title', Post::first()->tags()->first()->title);
    }

    public function testHasRelationQueries()
    {
        $post1 = Post::forceCreate(['title'  => 'Post 1 title']);
        $post2 = Post::forceCreate(['title'  => 'Post 2 title']);
        $tag = Tag::forceCreate(['title'  => 'Tag title']);

        $post1->tags()->sync([$tag->id]);

        $results = Tag::has('posts')->get();

        $this->assertEquals(1, $results->count());
        $this->assertEquals('Tag title', $results->first()->title);

    }

    protected function createTwoPostsWithOneTranslatedInThreeLocales()
    {
        $post = Post::forceCreateInLocale('de', ['title'  => 'Title DE']);
        $post->forceSaveTranslation('en', ['title'  => 'Title EN']);
        $post->forceSaveTranslation('fr', ['title'  => 'Title FR']);
        Post::forceCreateInLocale('de', ['title'  => 'Title 2']);
    }
}