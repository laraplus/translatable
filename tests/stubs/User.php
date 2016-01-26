<?php

use Laraplus\Data\Translatable;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use Translatable;

    protected $translatable = ['bio'];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}