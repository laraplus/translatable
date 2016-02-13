# Translatable Eloquent models (Laravel 5.2+)

**Still in development - stable release will be available soon!**

This package provides a powerful and extremely easy way of managing multilingual models in Eloquent.

It makes use of Laravel's 5.2 enhanced global scopes to join translated attributes to every query rather than utilizing
relations as some alternative packages. As a result, only a single query is required to fetch translated attributes and
there is no need to create separate models for translation tables, making this package easier to use.

## Quick demo

To enable translations in your models, you first need to prepare your schema according to the
[convention](#creating-migrations). Then you can pull in the ``Translatable`` trait:

```php
use Laraplus\Data\Translatable;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use Translatable;
}
```

And that's it! No other configuration is required. The translated attributes will be automatically cached and all your
queries will start returning translated attributes:

```php
Post::first();
$post->title; // title in the current locale

Post::translateInto('de')->first();
$post->title; // title in 'de' locale

Post::translateInto('de')->withFallback('en')->first();
$post->title; // title in 'de' if available, otherwise in 'en'
```

Since translations are joined to the query, it is also very easy to filter and order by translated attributes:

```php
Post::where('body', 'LIKE', '%Laravel%')->orderBy('title', 'desc');
```

Or even return only translated records:

```php
Post::onlyTranslated()->all()
```

Multiple [helpers](#crud-operations) are available for all basic CRUD operations. For all available options, read the
[full documentation](#crud-operations) below.


## Installation

This package can be used within Laravel or Lumen applications as well as any other application that utilizes Laravel's
database component https://github.com/illuminate/database. The package can be installed through composer:

```
composer require laraplus/translatable
```

### Configuration in Laravel

To configure the package, add a service provider to your ``app.php`` configuration file, under the ``providers`` key:

```php
'providers' => [
    // other providers
    Laraplus\Data\TranslatableServiceProvider::class
];
```

Optionally you can also configure some other options by publishing the ``translatable.php`` configuration file:

```
php artisan vendor:publish --provider="Laraplus\Data\TranslatableServiceProvider" --tag="config"
```

Open the configuration file to check all available settings:
https://github.com/laraplus/translatable/blob/master/config/translatable.php

### Configuration outside Laravel

When using this package outside Laravel, you can configure it using ``TranslatableConfig`` class:

```php
TranslatableConfig::currentLocaleGetter(function() {
    // return the current locale of the application
});
TranslatableConfig::fallbackLocaleGetter(function() {
    // return the fallback locale of the application
});
```

You can optionally adjust some other settings as well. To see all available options inspect Laravel's Service Provider:
https://github.com/laraplus/translatable/blob/master/src/TranslatableServiceProvider.php

## Creating migrations

To utilize multilingual models you need to prepare your database tables in a certain was. Each translatable table
consists of translatable and non translatable attributes. While non translatable attributes can be added to your table
normally, translatable fields need to be in their own table named according to the convention.

Below you can see a sample migration for the ``posts`` table:

```php
Schema::create('posts', function(Blueprint $table)
{
    $table->increments('id');
    $table->datetime('published_at');
    $table->timestamps();
});

Schema::create('posts_i18n', function(Blueprint $table)
{
    $table->integer('post_id')->unsigned();
    $table->string('locale', 6);
    $table->string('title');
    $table->string('body');
    
    $table->primary('post_id', 'locale');
});
```

By default, translation tables must end with ``_i18`` suffix but that can be changed in the configuration file. Also,
translation table must contain a foreign key to the parent table as well as a ``locale`` field (also configurable) 
which will store the locale of translated attributes. Incrementing keys are not allowed on translation models. A
composite key containing ``locale`` and foreign key reference to the parent model needs to be defined instead.
Optionally you may also define foreign key constraints, but the package will work without them as well.

**Important: make sure that no translated attributes are named the same as any non translated attribute since that will
break the queries. This also applies to timestamps (which should not be added to the translation tables but to primary
tables only) and for incrementing keys (not allowed on translation tables).**

## Configuring models

To make your models aware of translated attributes you need to pull in the ``Translatable`` trait:

```php
use Laraplus\Data\Translatable;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use Translatable;
}
```

Optionally you may also define an array of ``$translatable`` attributes, but the package is designed to work without it.
In that case translatable attributes will be automatically determined from the database schema and cached indefinitely.
If you are using the cache approach don't forget to clear the cache every time the schema changes.

By default, if the model is not translated into the current locale, fallback translations will be selected instead.
If no translations are available, null will be returned for all translatable attributes. If you wish to change that
behavior you can either modify the ``translatable.php`` configuration file or adjust the behavior on "per model" basis:

```php
class Post extends Model
{
    use Translatable;
    
    protected $withFallback = false;
    
    protected $onlyTranslated = true;
}
```

## CRUD operations

### Selecting rows

To select rows from your translatable models, you can use all of the usual Eloquent query helpers. The translatable
attributes will be returned in your current locale. To learn more about how to configure localization settings in
Laravel, refer to the official documentation: https://laravel.com/docs/5.2/localization

```php
Post::where('active', 1)->orderBy('title')->get();
```

#### Query helpers

The query above will by default also return records that don't have any translations in the current or fallback locale.
To return only translated rows, you can change the ``defaults.only_translated`` config option to ``true``, or use the
``onlyTranslated()`` query helper:

```php
Post::onlyTranslated()->get();
```

Sometimes you may want to disable fallback translations altogether. To do this, you may either change the
``defaults.with_fallback`` configuration option to ``false`` or use the ``withoutFallback()`` query helper:

```php
Post::withoutFallback()->get();
```

Both of the helpers above also have their opposite forms: ``withUntranslated()`` and ``withFallback()``. You may also
provide an optional ``$locale`` argument to the ``withFallback()`` helper, where you can change the default fallback locale:

```php
Post::withUntranslated()->withFallback()->get();
Post::withUntranslated()->withFallback('de')->get();
```

Sometimes you may wish to retrieve translations in a locale different from the current one. To achieve that, you may use
the ``translateInto($locale)`` helper:

```php
Post::translateInto('de')->get();
```

In case you don't need the translated attributes at all, you may use the ```withoutTranslation()``` helper, which will
remove the translatable global scope from your query

```php
Post::withoutTranslations()->get();
```

#### Filtering and ordering by translated attributes

Often you may wish to filter query results by translated attributes. This package allows you to use all of the usual
Eloquent ``where`` clauses normally. This will work even with fallback translations since all of the columns within
where clauses will be automatically wrapped in ``ifnull`` statements and prefixed with the appropriate table names:

```php
Post::where('title', 'LIKE', '%Laravel%')->orWhere('description', 'LIKE', '%Laravel%)->get();
```

The same is true for ``order by`` clauses, which will also be automatically transformed to the correct format:

```php
Post::orderBy('title')->get();
```

**Notice: if you are using ``whereRaw`` clauses, we will not be able to format your expressions automatically since
we do not parse whereRaw expressions. Instead you will need to include the appropriate table prefix manually.**

### Inserting new rows

When creating new models in the current locale, you may use the normal Laravel syntax, as if you were inserting rows in
a single table:

```php
Post::create([
    'title' => 'My title',
    'published_at' => Carbon::now()
]);
```

If you want to store the record in an alternative locale, you may use the ``createInLocale($locale, $attributes)`` helper:

```php
Post::createInLocale('de', [
    'title' => 'My title',
    'published_at' => Carbon::now()
]);
```

Often you will need to store a new row together with all translations. To do that, you may list translatable attributes
as a second argument of the ``create()`` method:

```php
Post::createInLocale('de', [
    'published_at' => Carbon::now()
], [
    'en' => ['title' => 'Title in EN'],
    'de' => ['title' => 'Title in DE'],
]);
```

All of the helpers above also have their ``force`` forms that let you bust the mass assignment protection.

```php
Post::forceCreate([/*attributes*/], [/*translations*/]);
Post::forceCreateInLocale($locale, [/*attributes*/]);
```

### Updating existing rows

Updating records in the current locale is as easy as updating a single table:

```php
$user = User::first();
$user->title = 'New title';
$user->save();
```

If you wish to update record in another locale, you may use a ``saveTranslation($locale, $attributes)`` helper:

```php
$user = User::first();
$user->saveTranslation('en', [
    'title' => 'Title in EN'
]);
$user->saveTranslation('de', [
    'title' => 'Title in DE'
]);
```

A ``forceSaveTranslation($locale, $attributes)`` helper is also available to bust mass assignment protection.

To update multiple rows at once, you may also use a query builder approach:

```php
User::where('published_at, '>', Carbon::now())->update(['title' => 'New title']);
```

To updatre a different locale using query builder, you can still use the ``transleteInto($locale)`` helper:

```php
User::where('published_at, '>', Carbon::now())->translateInto('de')->update(['title' => 'New title']);
```

### Deleting rows

Deleting rows couldn't be easier. Do it as you were always doing it and translations will automatically be deleted
together with the parent row:

```php
$user = User::first();
$user->delete();
```

To delete multiple rows at once, you may also use a query builder. Translations will be cleaned up automatically:

```php
User::where('published_at, '>', Carbon::now())->delete();
```

## Translations as a relation

Sometimes you may wish to retrieve all translations of a model. Luckily the package implements a ``hasMany`` relation
which will help us do just that:

```php
$user = $user->first();

foreach($user->translations as $translation) {
    echo "Title in {$translation->locale} is {$translation->title}";
}
```

Whe using the translation approach, you may often wish to preload the relation without joining translated attributes to
the query. There is a ``withAllTranslations()`` helper available to do just that:

```php
User::withAllTranslations()->get();
```

**Notice: it is currently not possible to update or insert new records using the relation. Instead you can use the
helpers described above.**
