<?php
/**
 * Created by PhpStorm.
 * User: pedrosoares
 * Date: 05/08/2019
 * Time: 12:24
 */

namespace App\Services;

use App\Interfaces\ServiceLayer;
use App\Interfaces\SourceData;
use App\Lead;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class LeadServices implements ServiceLayer
{
    const  SHARP_SPRING = 'sharp-spring';

    public function processData(SourceData $sourceData): void
    {
        $sourceData->importDatabase();
    }

    public function processDataFile(SourceData $sourceData): void
    {
        $sourceData->importDatabaseFile();
    }

    /**
     * Filter leads
     * @param Request $request
     * @return array
     */
    public function filterLeads(Request $request): array
    {

        $dataChart = [
            "labels" => [],
            "data" => [],
            "total"=> 0
        ];
        $dateFormat = 'Y-m-d';
        if($request->has('group')){
            switch (Arr::first($request->group)){
                default:
                    $dateFormat = 'Y-m-d';
                    break;
                case 'monthly':
                    $dateFormat = 'Y-m';
                    break;
                case 'yearly':
                    $dateFormat = 'Y';
                    break;

            }
            ;
        }
        QueryBuilder::for(Lead::class)
            ->allowedFilters([
                AllowedFilter::scope('between_source_data_created'),
                AllowedFilter::scope('by_brand'),
                AllowedFilter::scope('owner'),
                AllowedFilter::exact('status_source_data')

            ])
            ->orderBy('created_source_data','ASC')
            ->get()
            ->groupBy(function ($date) use($dateFormat) {
                return Carbon::parse($date->created_source_data)->format($dateFormat);
            })->map(function ($leads, $key) use (&$dataChart) {
                $leadsCount = $leads->count();
                array_push($dataChart['labels'], $key);
                array_push($dataChart['data'],$leadsCount);
                $dataChart['total'] += $leadsCount;
                return [
                    'label' => $key,
                    'value' => $leads->count(),

                ];
            });


        return $dataChart;
    }

}
