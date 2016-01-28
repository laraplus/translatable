<?php

class UnitTests extends TestCase
{
    public function testTranslationsAreJoined()
    {
        $expected =
            'select "posts".*, "posts_i18n"."title", "posts_i18n"."body" from "posts" '.
            'left join "posts_i18n" on "posts_i18n"."post_id" = "posts"."id" and "posts_i18n"."locale" = ?';

        $this->assertEquals(Post::toSql(), $expected);
        $this->assertEquals(['en'], Post::getBindings());
    }

    public function testFallbackTranslationsAreJoinedByDefault()
    {
        $query = Post::translate('de');

        $expected = $this->getJoinWithFallbackSql();

        $this->assertEquals($query->toSql(), $expected);
        $this->assertEquals(['de', 'en'], $query->getBindings());
    }

    public function testFallbackTranslationsCanBeDisabled()
    {
        $query = Post::translate('de')->withoutFallback();

        $expected =
            'select "posts".*, "posts_i18n"."title", "posts_i18n"."body" from "posts" '.
            'left join "posts_i18n" on "posts_i18n"."post_id" = "posts"."id" and "posts_i18n"."locale" = ?';


        $this->assertEquals($query->toSql(), $expected);
        $this->assertEquals(['de'], $query->getBindings());
    }

    public function testTranslationsAreJoinedOnBelongsToRelation()
    {
        $post = new Post();
        $post->user_id = 1;

        $expected =
            'select "users".*, "users_i18n"."bio" from "users" '.
            'left join "users_i18n" on "users_i18n"."user_id" = "users"."id" and "users_i18n"."locale" = ? '.
            'where "users"."id" = ?';

        $this->assertEquals($post->user()->toSql(), $expected);
        $this->assertEquals(['en', 1], $post->user()->getBindings());
    }

    public function testTranslationsAreJoinedOnHasManyRelation()
    {
        $user = new User();

        $expected =
            'select "posts".*, "posts_i18n"."title", "posts_i18n"."body" from "posts" '.
            'left join "posts_i18n" on "posts_i18n"."post_id" = "posts"."id" and "posts_i18n"."locale" = ? '.
            'where "posts"."user_id" is null and "posts"."user_id" is not null';

        $this->assertEquals($user->posts()->toSql(), $expected);
        $this->assertEquals(['en'], $user->posts()->getBindings());
    }

    public function testBasicWhereTranslated()
    {
        $queryAnd = Post::where('title', 'my title');
        $queryOr = Post::where('is_active', 1)->orWhere('title', 'my title');

        $expected =
            'select "posts".*, "posts_i18n"."title", "posts_i18n"."body" from "posts" '.
            'left join "posts_i18n" on "posts_i18n"."post_id" = "posts"."id" and "posts_i18n"."locale" = ? ';

        $this->assertEquals($queryAnd->toSql(), $expected . 'where "posts_i18n"."title" = ?');
        $this->assertEquals(['en', 'my title'], $queryAnd->getBindings());

        $this->assertEquals($queryOr->toSql(), $expected . 'where "is_active" = ? or "posts_i18n"."title" = ?');
        $this->assertEquals(['en', 1, 'my title'], $queryOr->getBindings());
    }

    public function testWhereTranslatedWithFallback()
    {
        $queryAnd = Post::translate('de')->where('title', 'my title');
        $queryOr = Post::translate('de')->where('is_active', 1)->orWhere('title', 'my title');

        $expected = $this->getJoinWithFallbackSql();

        $this->assertEquals($queryAnd->toSql(), $expected . ' where ifnull("posts_i18n"."title", "posts_i18n_fallback"."title") = ?');
        $this->assertEquals(['de', 'en', 'my title'], $queryAnd->getBindings());

        $this->assertEquals($queryOr->toSql(), $expected . ' where "is_active" = ? or ifnull("posts_i18n"."title", "posts_i18n_fallback"."title") = ?');
        $this->assertEquals(['de', 'en', 1, 'my title'], $queryOr->getBindings());
    }

    protected function getJoinWithFallbackSql()
    {
        return 'select "posts".*, '.
            'ifnull("posts_i18n"."title", "posts_i18n_fallback"."title") as "title", '.
            'ifnull("posts_i18n"."body", "posts_i18n_fallback"."body") as "body" from "posts" '.
        'left join "posts_i18n" on '.
            '"posts_i18n"."post_id" = "posts"."id" and "posts_i18n"."locale" = ? '.
        'left join "posts_i18n" as "posts_i18n_fallback" on '.
            '"posts_i18n_fallback"."post_id" = "posts"."id" and "posts_i18n_fallback"."locale" = ?';
    }
}