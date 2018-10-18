<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    public function quote()
    {
        return $this->hasMany(Quote::class);
    }
}
