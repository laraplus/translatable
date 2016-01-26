<?php namespace Laraplus\Data;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\SqlServerGrammar;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Grammars\Grammar;

class TranslatableScope implements Scope
{
    protected $table;

    protected $i18nTable;

    protected $locale;

    protected $fallback;

    protected $joinType = 'leftJoin';

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $builder
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $this->table = $model->getTable();
        $this->locale = $model->getLocale();
        $this->i18nTable = $model->getI18nTable();
        $this->fallback = $model->getFallbackLocale();
        $this->joinType = 'leftJoin';

        $this->createJoin($builder, $model);
        $this->createSelect($builder, $model);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder $builder
     * @param  \Illuminate\Database\Eloquent\Model $model
     */
    protected function createJoin(Builder $builder, Model $model)
    {
        $joinType = $this->joinType;

        $clause = $this->getJoinClause($model, $this->locale, $this->i18nTable);
        $builder->$joinType($this->i18nTable, $clause);

        if($this->shouldFallback()) {
            $clause = $this->getJoinClause($model, $this->fallback, $this->i18nTable . '_fallback');
            $builder->$joinType("{$this->i18nTable} as {$this->i18nTable}_fallback", $clause);
        }
    }

    /**
     * @param Model $model
     * @return callable
     */
    private function getJoinClause(Model $model, $locale, $alias)
    {
        return function (JoinClause $join) use ($model, $locale, $alias) {
            $primary = $model->getKeyName();
            $foreign = $model->getForeignKey();
            $langKey = $model->getLocaleKey();

            $join->on($alias . '.' . $foreign, '=', $this->table . '.' . $primary)
                ->where($alias . '.' . $langKey, '=', $locale);
        };
    }

    /**
     * @return bool
     */
    protected function shouldFallback()
    {
        return $this->fallback && $this->locale != $this->fallback && $this->joinType != 'join';
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder $builder
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    protected function createSelect(Builder $builder, Model $model)
    {
        if($builder->getQuery()->columns) {
            return;
        }

        $select = $this->formatColumns($model, $builder->getQuery()->getGrammar());

        $builder->select(array_merge([$this->table . '.*'], $select));
    }

    /**
     * @param Model $model
     * @param Grammar $grammar
     * @return array
     */
    protected function formatColumns(Model $model, Grammar $grammar)
    {
        $map = function ($field) use ($grammar) {
            if (!$this->shouldFallback()) {
                return "{$this->i18nTable}.{$field}";
            }

            $primary = $grammar->wrap("{$this->i18nTable}.{$field}");
            $fallback = $grammar->wrap("{$this->i18nTable}_fallback.{$field}");
            $ifNull = $this->getIfNull($grammar);
            $alias = $grammar->wrap($field);

            return new Expression("{$ifNull}({$primary}, {$fallback}) as {$alias}");
        };

        return array_map($map, $model->translatableAttributes());
    }


    /**
     * @param Grammar $grammar
     * @return string
     */
    protected function getIfNull(Grammar $grammar)
    {
        return $grammar instanceof SqlServerGrammar ? 'isnull' : 'ifnull';
    }

    /**
     * Extend the builder.
     * @param Builder $builder
     */
    public function extend(Builder $builder)
    {
        $builder->macro('onlyTranslated', function (Builder $builder) {
            $this->joinType = 'join';

            return $builder;
        });

        $builder->macro('withoutFallback', function (Builder $builder) {
            $builder->getModel()->setFallbackLocale(false);

            return $builder;
        });

        $builder->macro('inLocale', function (Builder $builder, $locale) {
            $builder->getModel()->setLocale($locale);

            return $builder;
        });
    }
}