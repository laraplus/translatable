<?php namespace Laraplus\Data;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class Builder extends EloquentBuilder
{
    /**
     * Update a record in the database.
     *
     * @param  array  $values
     * @return int
     */
    public function update(array $values)
    {
        $values = $this->addUpdatedAtColumn($values);

        list($values, $i18nValues) = $this->filterValues($values);


        if($this->query->update($values)) {
            return $this->updateI18n($i18nValues);
        }

        return false;
    }

    /**
     * Insert a new record into the database.
     *
     * @param  array  $values
     * @return bool
     */
    public function insert(array $values)
    {
        list($values, $i18nValues) = $this->filterValues($values);

        if($this->query->insert($values)) {
            return $this->insertI18n($i18nValues, $values[$this->model->getKeyName()]);
        }
    }

    /**
     * Insert a new record and get the value of the primary key.
     *
     * @param  array   $values
     * @param  string  $sequence
     * @return int
     */
    public function insertGetId(array $values, $sequence = null)
    {
        list($values, $i18nValues) = $this->filterValues($values);

        if($id = $this->query->insertGetId($values, $sequence)) {
            if($this->insertI18n($i18nValues, $id)) {
                return $id;
            }
            return false;
        }
    }

    /**
     * Filters translatable values from non-translatable.
     *
     * @param array $values
     * @return array
     */
    protected function filterValues(array $values)
    {
        $attributes = $this->model->translatableAttributes();

        $translatable = [];

        foreach($attributes as $key) {
            if(isset($values[$key])) {
                $translatable[$key] = $values[$key];

                unset($values[$key]);
            }
        }

        return [$values, $translatable];
    }

    /**
     * @param array $values
     * @param mixed $key
     * @return bool
     */
    protected function insertI18n(array $values, $key)
    {
        if(count($values) == 0) {
            return true;
        }

        $values[$this->model->getForeignKey()] = $key;
        $values[$this->model->getLocaleKey()] = $this->model->getLocale();

        return $this->i18nQuery()->insert($values);
    }

    /**
     * @param array $values
     * @return bool
     */
    protected function updateI18n(array $values)
    {
        if(count($values) == 0) {
            return true;
        }

        $updated = $this->i18nQuery()
            ->where($this->model->getForeignKey(), $this->model->getKey())
            ->where($this->model->getLocaleKey(), $this->model->getLocale())
            ->update($values);

        if(!$updated) {
            return $this->insertI18n($values, $this->model->getKey());
        }

        return true;
    }

    /**
     * Get the underlying query builder instance for translation table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function i18nQuery()
    {
        $query = $this->getModel()->newQueryWithoutScopes()->getQuery();

        $query->from($this->model->getI18nTable());

        return $query;
    }
}