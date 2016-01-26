<?php namespace Laraplus\Data;

trait Translatable
{
    protected $locale = null;

    protected $fallbackLocale = null;

    protected static $i18nAttributes = [];

    /**
     * Boot the trait.
     */
    public static function bootTranslatable()
    {
        static::addGlobalScope(new TranslatableScope);
    }

    /**
     * @param array $attributes
     * @return $this
     * @throws MassAssignmentException
     */
    public function fill(array $attributes)
    {
        if(!isset(static::$i18nAttributes[$this->getTable()])) {
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
        } else {
            $attributes = $this->getTranslatableAttributesFromSchema();
        }

        static::$i18nAttributes[$this->getTable()] = $attributes;
    }

    /**
     * Get an array of translatable attributes from schema
     *
     * @return array
     */
    protected function getTranslatableAttributesFromSchema()
    {
        if ((!$con = $this->getConnection()) || (!$builder = $con->getSchemaBuilder())) {
            return [];
        }

        return $builder->getColumnListing($this->getTable());
    }

    /**
     * Get an array of translatable attributes.
     *
     * @return array
     */
    public function translatableAttributes()
    {
        return static::$i18nAttributes[$this->getTable()];
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Get name of the locale key.
     *
     * @return string
     */
    public function getLocaleKey()
    {
        return LocaleSettings::dbKey();
    }

    /**
     * Get current locale
     *
     * @return string
     */
    public function getLocale()
    {
        if($this->locale) {
            return $this->locale;
        }

        return LocaleSettings::current();
    }

    /**
     * Get current locale
     *
     * @param $locale
     * @return string
     */
    public function setFallbackLocale($locale)
    {
        $this->fallbackLocale = $locale;

        return $this;
    }

    /**
     * Get current locale
     *
     * @return string
     */
    public function getFallbackLocale()
    {
        if(!is_null($this->fallbackLocale)) {
            return $this->fallbackLocale;
        }

        return LocaleSettings::fallback();
    }

    /**
     * Get current locale
     *
     * @param $locale
     * @return string
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get the i18n table associated with the model.
     *
     * @return string
     */
    public function getI18nTable()
    {
        return $this->getTable() . '_i18n';
    }
}