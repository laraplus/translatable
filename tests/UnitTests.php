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
        $query = Post::inLocale('de');

        $expected =
            'select "posts".*, '.
                'ifnull("posts_i18n"."title", "posts_i18n_fallback"."title") as "title", '.
                'ifnull("posts_i18n"."body", "posts_i18n_fallback"."body") as "body" '.
            'from "posts" '.
            'left join "posts_i18n" on '.
                '"posts_i18n"."post_id" = "posts"."id" and "posts_i18n"."locale" = ? '.
            'left join "posts_i18n" as "posts_i18n_fallback" on '.
                '"posts_i18n_fallback"."post_id" = "posts"."id" and "posts_i18n_fallback"."locale" = ?';

        $this->assertEquals($query->toSql(), $expected);
        $this->assertEquals(['de', 'en'], $query->getBindings());
    }

    public function testFallbackTranslationsCanBeDisabled()
    {
        $query = Post::inLocale('de')->withoutFallback();

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
}