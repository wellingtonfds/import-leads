<?php

namespace App\Jobs;

use App\FileImport;
use App\Metadata;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ImportFiles implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    /**
     * Create a new job instance.
     *
     * @return void
     */

    private $fileName = '';
    public function __construct($file)
    {
        $this->fileName = $file;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $file = FileImport::where('name',$this->fileName)->first();

        try {
            $file->status = 'processing';
            $file->save();
            $filePath = storage_path() . '/app/public/import/' . $file->name;
            $processFile = fopen($filePath, 'r');
            $head = explode(',', fgets($processFile));
            while (($line = fgets($processFile)) !== false) {
                $this->makeJsonLead(str_getcsv($line), $head, $file->origin,$file->config);
            }
            fclose($processFile);
            if($file->origin == 'sharp-spring-file'){
                DB::table('leads')->truncate();
                ProcessMetaData::dispatch($file, $file->origin);
            }elseif($file->origin == 'sharp-spring-opp'){
                if($file->config['deleteAll']){
                    DB::table('opportunities')->truncate();
                }
                ProcessMetaData::dispatch($file,'sharp-spring-opp',$file->config['brand']);
            }

        } catch (\Exception $e) {

            $file = FileImport::where('name',$this->fileName)->first();
            if($file){
                $file->status = 'fail';
                $file->save();
            }
            echo $e->getMessage();
        }

    }

    private function makeJsonLead($lead, $head, $origin = 'sharp-spring', $config = null)
    {
        $newLead = [];
        foreach ($head as $key => $value) {
            if ($value != "\n") {
                $newLead[$this->normalizeHead($value)] = $lead[$key];
            }
        }
        Metadata::create([
            'data' => json_encode($newLead),
            'origin' => $origin,
            'config'=> $config
        ]);


    }

    private function normalizeHead($head)
    {

        $head = preg_replace([
            "/(á|à|ã|â|ä)/", "/(Á|À|Ã|Â|Ä)/", "/(é|è|ê|ë)/", "/(É|È|Ê|Ë)/",
            "/(í|ì|î|ï)/", "/(Í|Ì|Î|Ï)/", "/(ó|ò|õ|ô|ö)/", "(Ç|ç)",
            "/(Ó|Ò|Õ|Ô|Ö)/", "/(ú|ù|û|ü)/", "/(Ú|Ù|Û|Ü)/", "/(ñ)/", "/(Ñ)/"],
            explode(" ", "a A e E i I o O u U n N ç Ç"), $head);
        $head = strtolower($head);
        $head = str_replace(' ', '_', $head);
        $head = str_replace('-', '_', $head);
        return $head;
    }
}
