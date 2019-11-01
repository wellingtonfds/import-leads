<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\QueryBuilder;


class Lead extends Model
{
    protected $fillable = [
        'origin_source_data', 'status_source_data', 'id_source_data', 'created_source_data',
        'updated_source_data', 'first_name', 'last_name', 'email', 'data'
    ];

    protected $casts = [
        'data' => 'array'
    ];

    public function opportunities()
    {
        return $this->hasMany(Opportunity::class);
    }

    public function scopeWon($query)
    {
        return $query->where('dados->status', '=', 'contactWithOpp')->limit(10)->get();
    }

    public function sharpspring()
    {
        return $this->hasMany('App\Sharpspring', 'lead_id', 'id');
    }

    public function user()
    {
        return $this->hasMany('App\Lead');
    }

    public function schedule()
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * @param QueryBuilder $query
     * @param String $dateInit
     * @param String $dateEnd
     * @return QueryBuilder
     */
    public function scopeBetweenSourceDataCreated(QueryBuilder $query, $dateInit, $dateEnd): QueryBuilder
    {
        return $query->whereBetween('created_source_data', [$dateInit, $dateEnd]);
    }

    /**
     * @param QueryBuilder $query
     * @param String $brand
     * @return QueryBuilder
     */
    public function scopeByBrand(QueryBuilder $query, $brand): QueryBuilder
    {
        return $query->where('data->marca_de_interesse_5d14c0a4b9c67', $brand);
    }
    public function scopeOwner(QueryBuilder $query, $owner): QueryBuilder
    {
        return $query->where('data->ownerID',$owner);
    }

    public function call(){
        return $this->hasOne(Call::class);
    }

    public function scopeQualified($query){
        return $query->where('status_source_data', 'qualified');
    }

    public function callHistory(){
        return $this->hasManyThrough(CallHistory::class, Call::class);
    }

    public function lastCallHistory(){
        return $this->hasManyThrough(CallHistory::class, Call::class)->latest();
    }

}
