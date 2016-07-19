<?php

use Laraplus\Data\Translatable;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use Translatable;

    protected $translatable = ['title'];

    public function posts()
    {
        return $this->belongsToMany(Post::class);
    }
}