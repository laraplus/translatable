<?php namespace Laraplus\Data;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class TranslationModel extends Eloquent
{
    /**
     * Translation model does not include timestamps by default
     *
     * @var boolean
     */
    public $timestamps = false;

    /**
     * Name of the table (will be set dynamically)
     *
     * @var string
     */
    protected $table = null;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var string
     */
    protected $localeKey = 'locale';

    /**
     * Set the keys for a save update query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function setKeysForSaveQuery(EloquentBuilder $query)
    {
        $query->where($this->getKeyName(), '=', $this->getKeyForSaveQuery());
        $query->where($this->localeKey, '=', $this->{$this->localeKey});

        return $query;
    }

    /**
     * @param $localeKey
     * @return $this
     */
    public function setLocaleKey($localeKey) {
        $this->localeKey = $localeKey;

        return $this;
    }
}