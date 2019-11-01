<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Metadata extends Model
{
    protected $fillable = ['data','origin','config'];
    protected $casts  = [
        'config'=>'array'
    ];

    public function getDataJsonAttribute(){
        return json_decode($this->data,true);
    }
    protected $table = 'metadatas';
}
