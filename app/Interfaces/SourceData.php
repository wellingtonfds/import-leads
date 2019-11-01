<?php
/**
 * Created by PhpStorm.
 * User: pedrosoares
 * Date: 07/08/2019
 * Time: 17:04
 */

namespace App\Interfaces;


use App\Metadata;
use Illuminate\Database\Eloquent\Collection;

interface SourceData
{
    public function getMetaDataInDatabase():Collection;
    public function processOneMetaData(Metadata $metadata):void;
    public function processData():void;
    public function importDatabaseFile():void;
    public function importDatabase():void;

}