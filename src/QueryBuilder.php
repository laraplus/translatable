<?php namespace Laraplus\Data;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class QueryBuilder extends Builder
{
    protected $model;

    /**
     * Set a model instance
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return $this
     */
    public function setModel(Model $model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Add a translation where clause to the query.
     *
     * @param  string|array|\Closure  $column
     * @param  string  $operator
     * @param  mixed   $value
     * @param  string  $boolean
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function whereTranslated($column, $operator = null, $value = null, $boolean = 'and')
    {
        // If the column is an array, we will assume it is an array of key-value pairs
        // and can add them each as a where clause. We will maintain the boolean we
        // received when the method was called and pass it into the nested where.
        if (is_array($column)) {
            return $this->addArrayOfWheres($column, $boolean);
        }

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