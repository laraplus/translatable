<?php namespace Laraplus\Data;

use Illuminate\Database\Eloquent\Relations\Relation;
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
        $updated = 0;
        $values = $this->addUpdatedAtColumn($values);

        list($values, $i18nValues) = $this->filterValues($values);

        if($values) {
            $updated += $this->toBase()->update($values);
        }

        if($i18nValues) {
            $updated += $this->updateI18n($i18nValues);
        }

        return $updated;
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
        }

        return false;
    }

    /**
     * Delete a record from the database.
     *
     * @return mixed
     */
    public function delete()
    {
        if (isset($this->onDelete)) {
            return call_user_func($this->onDelete, $this);
        }

        return $this->i18nDeleteQuery()->delete() && $this->toBase()->delete();
    }

    /**
     * Run the default delete function on the builder.
     *
     * @return mixed
     */
    public function forceDelete()
    {
        return $this->i18nDeleteQuery(false)->delete() && $this->query->delete();
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
            if(array_key_exists($key, $values)) {
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
            ->whereOriginal($this->model->getForeignKey(), $this->model->getKey())
            ->whereOriginal($this->model->getLocaleKey(), $this->model->getLocale())
            ->update($values);

        if(!$updated) {
            return $this->insertI18n($values, $this->model->getKey());
        }

        return true;
    }

    /**
     * Get the query builder instance for translation table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function i18nQuery()
    {
        $query = $this->getModel()->newQueryWithoutScopes()->getQuery();

        $query->from($this->model->getI18nTable());

        return $query;
    }

    /**
     * Get the delete query instance for translation table.
     *
     * @param bool $withGlobalScopes
     * @return \Illuminate\Database\Query\Builder
     */
    protected function i18nDeleteQuery($withGlobalScopes = true)
    {
        $subQuery = $withGlobalScopes ? $this->toBase() : $this->getQuery();
        $subQuery->select($this->model->getQualifiedKeyName());

        $query = $this->i18nQuery();
        $query->whereSubQuery($this->model->getForeignKey(), $subQuery);

        return $query;
    }

    /**
     * Merge the "wheres" from a relation query to a has query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $hasQuery
     * @param  \Illuminate\Database\Eloquent\Relations\Relation  $relation
     */
    protected function mergeModelDefinedRelationWheresToHasQuery(EloquentBuilder $hasQuery, Relation $relation)
    {
        // Here we have the "has" query and the original relation. We need to copy over any
        // where clauses the developer may have put in the relationship function over to
        // the has query, and then copy the bindings from the "has" query to the main.
        $relationQuery = $relation->toBase();

        $hasQuery = $hasQuery->withoutGlobalScopes();
        $bindings = $relationQuery->getRawBindings();

        $hasQuery->mergeWheres(
            $relationQuery->wheres, $bindings['where']
        );

        // In addition to merging where clauses we should also merge select and join clauses
        // since we utilize them to retrieve translated attributes. That way we'll ensure
        // that has() and whereHas() sub-queries have access to translated attributes.
        $hasQuery->addSelect($relationQuery->columns);

        $relationJoins = (array) $relationQuery->joins;
        $hasQueryJoins = (array) $hasQuery->getQuery()->joins;

        $hasQuery->addBinding($bindings['join'], 'join');
        $hasQuery->getQuery()->joins = array_merge($relationJoins, $hasQueryJoins);
    }
}