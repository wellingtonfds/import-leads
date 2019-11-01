<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\QueryBuilder;

class Opportunity extends Model
{
    protected $fillable = [
        'amount', 'name', 'status_negotiation', 'is_active', 'is_closed', 'is_won',
        'close_date', 'lead_id', 'brand_id', 'id_source_data', 'id_campaign', 'data'
    ];
    protected $casts = [
        'data' => 'array'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function dealStage()
    {
        return $this->hasMany(DealStage::class);
    }
    /**
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    protected function castAttribute($key, $value)
    {
        if ($key == 'data') {
            return json_decode(utf8_encode($value), true);
        }
    }
    /**
     * @param QueryBuilder $query
     * @param String $dateInit
     * @param String $dateEnd
     * @return QueryBuilder
     */
    public function scopeBetweenSourceDataCreated(QueryBuilder $query, $dateInit, $dateEnd): QueryBuilder
    {
        return $query->whereBetween('data->createTimestamp', [$dateInit, $dateEnd]);
    }

    /**
     * @param QueryBuilder $query
     * @param String $brand
     * @return QueryBuilder
     */
    public function scopeByBrand(QueryBuilder $query, $brand): QueryBuilder
    {
        return $query->where('data->marca_da_franquia_5d2384fae9451', $brand);
    }

}
