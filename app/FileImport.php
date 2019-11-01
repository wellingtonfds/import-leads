<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FileImport extends Model
{
    protected $fillable = ['id','status','origin','name','config'];
    protected $casts = ['config'=>'array'];
}
