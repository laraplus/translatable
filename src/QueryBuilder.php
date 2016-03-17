<?php namespace Laraplus\Data;

use Closure;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model as Eloquent;

class QueryBuilder extends Builder
{
    protected $model;

    /**
     * Set a model instance
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return $this
     */
    public function setModel(Eloquent $model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Add a where clause to the query.
     *
     * @param  string|\Closure  $column
     * @param  string  $operator
     * @param  mixed   $value
     * @param  string  $boolean
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        // If the column is an array, we will assume it is an array of key-value pairs
        // and can add them each as a where clause. We will maintain the boolean we
        // received when the method was called and pass it into the nested where.
        if (is_array($column)) {
            return $this->addArrayOfWheres($column, $boolean);
        }

        // Then we need to check if we are dealing with a translated column and defer
        // to the "whereTranslated" clause in that case. That way the user doesn't
        // need to worry about translated columns and let us handle the details.
        if(in_array($column, $this->model->translatableAttributes())) {
            return $this->whereTranslated($column, $operator, $value, $boolean);
        }

        return parent::where($column, $operator, $value, $boolean);
    }

    /**
     * Add a where clause to the query and don't modify it for i18n.
     *
     * @param  string|\Closure  $column
     * @param  string  $operator
     * @param  mixed   $value
     * @param  string  $boolean
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function whereOriginal($column, $operator = null, $value = null, $boolean = 'and')
    {
        return parent::where($column, $operator, $value, $boolean);
    }

    /**
     * Add a translation where clause to the query.
     *
     * @param  string|\Closure  $column
     * @param  string  $operator
     * @param  mixed   $value
     * @param  string  $boolean
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function whereTranslated($column, $operator = null, $value = null, $boolean = 'and')
    {
        // Here we will make some assumptions about the operator. If only 2 values are
        // passed to the method, we will assume that the operator is an equals sign
        // and keep going. Otherwise, we'll require the operator to be passed in.
        if (func_num_args() == 2) {
            list($value, $operator) = [$operator, '='];
        } elseif ($this->invalidOperatorAndValue($operator, $value)) {
            throw new InvalidArgumentException('Illegal operator and value combination.');
        }

        // If the given operator is not found in the list of valid operators we will
        // assume that the developer is just short-cutting the '=' operators and
        // we will set the operators to '=' and set the values appropriately.
        if (! in_array(strtolower($operator), $this->operators, true)) {
            list($value, $operator) = [$operator, '='];
        }

        $fallbackColumn = $this->qualifyTranslationColumn($column, true);
        $column = $this->qualifyTranslationColumn($column);

        // Finally we'll check whether we need to consider fallback translations. In
        // that case we need to create a complex "ifnull" clause, otherwise we can
        // just prepend the translation alias and add the where clause normally.
        if (!$this->model->shouldFallback() || $column instanceof Closure) {
            return $this->where($column, $operator, $value, $boolean);
        }

        $condition = $this->compileIfNull($column, $fallbackColumn);

        return $this->whereRaw("$condition $operator ?", [$value], $boolean);
    }

    /**
     * Add a translation or where clause to the query.
     *
     * @param  string|array|\Closure  $column
     * @param  string  $operator
     * @param  mixed   $value
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function orWhereTranslated($column, $operator = null, $value = null)
    {
        return $this->whereTranslated($column, $operator, $value, 'or');
    }

    /**
     * Add a full sub-select to the query.
     *
     * @param string $column
     * @param \Illuminate\Database\Query\Builder $query
     * @param string $boolean
     * @return $this
     */
    public function whereSubQuery($column, $query, $boolean = 'and')
    {
        list($type, $operator) = ['Sub', 'in'];

        $this->wheres[] = compact('type', 'column', 'operator', 'query', 'boolean');

        $this->addBinding($query->getBindings(), 'where');

        return $this;
    }

    /**
     * Add an "order by" clause by translated column to the query.
     *
     * @param  string  $column
     * @param  string  $direction
     * @return $this
     */
    public function orderBy($column, $direction = 'asc')
    {
        if(in_array($column, $this->model->translatableAttributes())) {
            return $this->orderByTranslated($column, $direction);
        }

        return parent::orderBy($column, $direction);
    }

    /**
     * Add an "order by" clause by translated column to the query.
     *
     * @param  string  $column
     * @param  string  $direction
     * @return $this
     */
    public function orderByTranslated($column, $direction = 'asc')
    {
        $fallbackColumn = $this->qualifyTranslationColumn($column, true);
        $column = $this->qualifyTranslationColumn($column);

        if (!$this->model->shouldFallback()) {
            return $this->orderBy($column, $direction);
        }

        $condition = $this->compileIfNull($column, $fallbackColumn);

        return $this->orderByRaw("{$condition} {$direction}");
    }

    /**
     * @param $column
     * @param $fallback
     * @return string
     */
    protected function qualifyTranslationColumn($column, $fallback = false)
    {
        $alias = $this->model->getI18nTable();
        $fallback = $fallback ? '_fallback' : '';

        if(Str::contains($column, '.')) {
            list($table, $field) = explode('.', $column);
            $suffix = $this->model->getTranslationTableSuffix();

            return Str::endsWith($alias, $suffix) ?
                "{$table}{$fallback}.{$field}" :
                "{$table}{$suffix}{$fallback}.{$field}";
        }

        return "{$alias}{$fallback}.{$column}";
    }

    /**
     * @param string $primary
     * @param string $fallback
     * @param string|null $alias
     * @return string
     */
    public function compileIfNull($primary, $fallback, $alias = null)
    {
        $ifNull = $this->grammar instanceof SqlServerGrammar ? 'isnull' : 'ifnull';

        $primary = $this->grammar->wrap($primary);
        $fallback = $this->grammar->wrap($fallback);
        $alias = $alias ? ' as ' . $this->grammar->wrap($alias) : '';

        return "{$ifNull}($primary, $fallback)" . $alias;
    }
}