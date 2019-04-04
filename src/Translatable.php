<?php namespace Laraplus\Data;

use Illuminate\Database\Eloquent\Relations\HasMany;

trait Translatable
{
    protected $overrideLocale = null;

    protected $overrideFallbackLocale = null;

    protected $overrideOnlyTranslated = null;

    protected $overrideWithFallback = null;

    protected $localeChanged = false;

    /**
     * Translated attributes cache
     *
     * @var array
     */
    protected static $i18nAttributes = [];

    /**
     * Boot the trait.
     */
    public static function bootTranslatable()
    {
        static::addGlobalScope(new TranslatableScope);
    }

    /**
     * Save a new model and return the instance.
     *
     * @param array $attributes
     * @param array|string $translations
     * @return static
     */
    public static function create(array $attributes = [], $translations = [])
    {
        $model = new static($attributes);

        if ($model->save() && is_array($translations)) {
            $model->saveTranslations($translations);
        }

        return $model;
    }

    /**
     * Save a new model in provided locale and return the instance.
     *
     * @param string $locale
     * @param array $attributes
     * @param array|string $translations
     * @return static
     */
    public static function createInLocale($locale, array $attributes = [], $translations = [])
    {
        $model = (new static($attributes))->setLocale($locale);

        if ($model->save() && is_array($translations)) {
            $model->saveTranslations($translations);
        }

        return $model;
    }

    /**
     * Save a new model and return the instance. Allow mass-assignment.
     *
     * @param array $attributes
     * @param array|string $translations
     * @return static
     */
    public static function forceCreate(array $attributes, $translations = [])
    {
        $model = new static;

        return static::unguarded(function () use ($model, $attributes, $translations){
            return $model->create($attributes, $translations);
        });
    }

    /**
     * Save a new model in provided locale and return the instance. Allow mass-assignment.
     *
     * @param array $attributes
     * @param array|string $translations
     * @return static
     */
    public static function forceCreateInLocale($locale, array $attributes, $translations = [])
    {
        $model = new static;

        return static::unguarded(function () use ($locale, $model, $attributes, $translations){
            return $model->createInLocale($locale, $attributes, $translations);
        });
    }

    /**
     * Reload a fresh model instance from the database.
     *
     * @param  array|string $with
     * @return static|null
     */
    public function fresh($with = [])
    {
        if (!$this->exists) {
            return;
        }

        $query = static::newQueryWithoutScopes()
            ->with(is_string($with) ? func_get_args() : $with)
            ->where($this->getKeyName(), $this->getKey());

        (new TranslatableScope())->apply($query, $this);

        return $query->first();
    }

    /**
     * @param array $translations
     * @return bool
     */
    public function saveTranslations(array $translations)
    {
        $success = true;
        $fresh = parent::fresh();

        foreach ($translations as $locale => $attributes) {
            $model = clone $fresh;
            $model->setLocale($locale);
            $model->fill($attributes);

            $success &= $model->save();
        }

        return $success;
    }

    /**
     * @param array $translations
     * @return bool
     */
    public function forceSaveTranslations(array $translations)
    {
        return static::unguarded(function () use ($translations){
            return $this->saveTranslations($translations);
        });
    }

    /**
     * @param $locale
     * @param array $attributes
     * @return bool
     */
    public function saveTranslation($locale, array $attributes)
    {
        return $this->saveTranslations([
            $locale => $attributes
        ]);
    }

    /**
     * @param $locale
     * @param array $attributes
     * @return bool
     */
    public function forceSaveTranslation($locale, array $attributes)
    {
        return static::unguarded(function () use ($locale, $attributes){
            return $this->saveTranslation($locale, $attributes);
        });
    }

    /**
     * @param array $attributes
     * @return $this
     * @throws MassAssignmentException
     */
    public function fill(array $attributes)
    {
        if (!isset(static::$i18nAttributes[$this->getTable()])) {
            $this->initTranslatableAttributes();
        }

        return parent::fill($attributes);
    }

    /**
     * Init translatable attributes.
     */
    protected function initTranslatableAttributes()
    {
        if (property_exists($this, 'translatable')) {
            $attributes = $this->translatable;
        }else {
            $attributes = $this->getTranslatableAttributesFromSchema();
        }

        static::$i18nAttributes[$this->getTable()] = $attributes;
    }

    /**
     * Get an array of translatable attributes from schema.
     *
     * @return array
     */
    protected function getTranslatableAttributesFromSchema()
    {
        if ((!$con = $this->getConnection()) || (!$builder = $con->getSchemaBuilder())) {
            return [];
        }

        if ($columns = TranslatableConfig::cacheGet($this->getI18nTable())) {
            return $columns;
        }

        $columns = $builder->getColumnListing($this->getI18nTable());
        unset($columns[array_search($this->getForeignKey(), $columns)]);

        TranslatableConfig::cacheSet($this->getI18nTable(), $columns);

        return $columns;
    }

    /**
     * Get a collection of translated attributes in provided locale.
     *
     * @param $locale
     * @return \Laraplus\Data\TranslationModel|null
     */
    public function translate($locale)
    {
        if (app()->getLocale() == $locale) {
            $found = $this;
        } else {
            $found = $this->translations->where($this->getLocaleKey(), $locale)->first();
        }

        if (!$found && $this->shouldFallback($locale)) {
            return $this->translate($this->getFallbackLocale());
        }

        return $found;
    }

    /**
     * Get a collection of translated attributes in provided locale or create new one.
     *
     * @param $locale
     * @return \Laraplus\Data\TranslationModel
     */
    public function translateOrNew($locale)
    {

        if (is_null($instance = $this->translate($locale))) {
            return $this->newModelInstance();
        }

        return $instance;

    }

    /**
     * Translations relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function translations()
    {
        $localKey = $this->getKeyName();
        $foreignKey = $this->getForeignKey();
        $instance = $this->translationModel();

        return new HasMany($instance->newQuery(), $this, $instance->getTable() . '.' . $foreignKey, $localKey);
    }

    /**
     * Returns the default translation model instance.
     *
     * @return DefaultTranslationModel
     */
    public function translationModel()
    {
        $translation = new TranslationModel();
        $translation->setConnection($this->getI18nConnection());
        $translation->setTable($this->getI18nTable());
        $translation->setKeyName($this->getForeignKey());
        $translation->setLocaleKey($this->getLocaleKey());

        if ($attributes = $this->translatableAttributes()) {
            $translation->fillable(array_intersect($attributes, $this->getFillable()));
        }

        return $translation;
    }

    /**
     * Get an array of translatable attributes.
     *
     * @return array
     */
    public function translatableAttributes()
    {
        if(!isset(static::$i18nAttributes[$this->getTable()])) {
            return [];
        }

        return static::$i18nAttributes[$this->getTable()];
    }

    /**
     * Get name of the locale key.
     *
     * @return string
     */
    public function getLocaleKey()
    {
        return TranslatableConfig::dbKey();
    }

    /**
     * Get current locale
     *
     * @param $locale
     * @return string
     */
    public function setLocale($locale)
    {
        $this->overrideLocale = $locale;

        $this->localeChanged = true;

        return $this;
    }

    /**
     * Get current locale
     *
     * @return string
     */
    public function getLocale()
    {
        if ($this->overrideLocale) {
            return $this->overrideLocale;
        }

        if (property_exists($this, 'locale')) {
            return $this->locale;
        }

        return TranslatableConfig::currentLocale();
    }

    /**
     * Get current locale
     *
     * @param $locale
     * @return string
     */
    public function setFallbackLocale($locale)
    {
        $this->overrideFallbackLocale = $locale;

        return $this;
    }

    /**
     * Get current locale
     *
     * @return string
     */
    public function getFallbackLocale()
    {
        if ($this->overrideFallbackLocale) {
            return $this->overrideFallbackLocale;
        }

        if (property_exists($this, 'fallbackLocale')) {
            return $this->fallbackLocale;
        }

        return TranslatableConfig::fallbackLocale();
    }

    /**
     * Set if model should select only translated rows
     *
     * @param bool $onlyTranslated
     * @return $this
     */
    public function setOnlyTranslated($onlyTranslated)
    {
        $this->overrideOnlyTranslated = $onlyTranslated;

        return $this;
    }

    /**
     * Get current locale
     *
     * @return bool
     */
    public function getOnlyTranslated()
    {
        if (!is_null($this->overrideOnlyTranslated)) {
            return $this->overrideOnlyTranslated;
        }

        if (property_exists($this, 'onlyTranslated')) {
            return $this->onlyTranslated;
        }

        return TranslatableConfig::onlyTranslated();
    }

    /**
     * Set if model should select only translated rows
     *
     * @param bool $withFallback
     * @return $this
     */
    public function setWithFallback($withFallback)
    {
        $this->overrideWithFallback = $withFallback;

        return $this;
    }

    /**
     * Get current locale
     *
     * @return bool
     */
    public function getWithFallback()
    {
        if (!is_null($this->overrideWithFallback)) {
            return $this->overrideWithFallback;
        }

        if (property_exists($this, 'withFallback')) {
            return $this->withFallback;
        }

        return TranslatableConfig::withFallback();
    }

    /**
     * Get the i18n connection name associated with the model.
     *
     * @return string
     */
    public function getI18nConnection()
    {
        return $this->getConnectionName();
    }

    /**
     * Get the i18n table associated with the model.
     *
     * @return string
     */
    public function getI18nTable()
    {
        return $this->getTable() . $this->getTranslationTableSuffix();
    }

    /**
     * Get the i18n table suffix.
     *
     * @return string
     */
    public function getTranslationTableSuffix()
    {
        return TranslatableConfig::dbSuffix();
    }

    /**
     * Should fallback to a primary translation.
     *
     * @param string|null $locale
     * @return bool
     */
    public function shouldFallback($locale = null)
    {
        if (!$this->getWithFallback() || !$this->getFallbackLocale()) {
            return false;
        }

        $locale = $locale ?: $this->getLocale();

        return $locale != $this->getFallbackLocale();
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Get a new query builder instance for the connection.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newBaseQueryBuilder()
    {
        $conn = $this->getConnection();

        $grammar = $conn->getQueryGrammar();

        $builder = new QueryBuilder($conn, $grammar, $conn->getPostProcessor());

        return $builder->setModel($this);
    }

    /**
     * Get the attributes that have been changed since last sync.
     *
     * @return array
     */
    public function getDirty()
    {
        $dirty = parent::getDirty();

        if (!$this->localeChanged) {
            return $dirty;
        }

        foreach ($this->translatableAttributes() as $key) {
            if (isset($this->attributes[$key])) {
                $dirty[$key] = $this->attributes[$key];
            }
        }

        return $dirty;
    }

    /**
     * Sync the original attributes with the current.
     *
     * @return $this
     */
    public function syncOriginal()
    {
        $this->localeChanged = false;

        return parent::syncOriginal();
    }
}
