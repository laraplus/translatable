<?php

use Laraplus\Data\Translatable;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use Translatable;

    protected $translatable = ['title', 'body'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }
}
