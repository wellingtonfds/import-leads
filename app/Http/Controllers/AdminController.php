<?php

namespace App\Http\Controllers;

use App\Metadata;
use App\FileImport;
use App\Jobs\ImportFiles;
use Illuminate\Support\Str;
use Illuminate\Http\Request;


class AdminController
{

    public function index()
    {
        return view('admin.marketing.index');
    }

    public function funcoes()
    {
        $sharpSpringFile = Metadata::where('origin','sharp-spring-file')->count();
        $sharpSpring = Metadata::where('origin','sharp-spring')->count();
        return view('admin.marketing.funcoes',['metaDataFile'=> $sharpSpringFile, 'metaData'=>$sharpSpring]);
    }

    public function importFile(Request $request)
    {

        $fileName = Str::random(10) . "." . $request->fileImport->extension();
        $request->fileImport->storeAs('public/import', $fileName);
        $insertFile = [
            'name' => $fileName,
            'origin' => $request->get('type-import-file')
        ];

        if($request->get('type-import-file') == 'sharp-spring-opp'){
            $config = [
                'brand'=>$request->get('brand'),
                'deleteAll'=>$request->has('deleteAllOpp') ?? false
            ];
            $insertFile['config'] = $config;
        }
        FileImport::create($insertFile);
        ImportFiles::dispatch($fileName);
        return redirect(route('leads'))->with('status','O arquivo será processado');
     }

    /**
     * Import CSV wherehaver
     *
     */
    public function processFile()
    {
        $file = FileImport::where('status', 'wait_process')->first();
        try {
            $file->status = 'processing';
            $file->save();
            $filePath = storage_path() . '/app/public/import/' . $file->name;
            $processFile = fopen($filePath, 'r');
            $head = explode(',', fgets($processFile));
            while (($line = fgets($processFile)) !== false) {
                $this->makeJsonLead(explode(',', $line), $head);
            }
            fclose($processFile);
            $file->status = 'processed';
            $file->save();
            echo 'File imported with success!';
        } catch (\Exception $e) {

            $file = FileImport::where('status', 'processing')->first();
            if($file){
                $file->status = 'fail';
                $file->save();
            }
            echo 'Nothing to import';
        }

    }

    private function makeJsonLead($lead, $head)
    {
        $newLead = [];
        foreach ($head as $key => $value) {
            if ($value != "\n") {
                $newLead[$this->normalizeHead($value)] = $lead[$key];
            }
        }
        Metadata::create([
            'data' => json_encode($newLead),
            'origin' => 'sharp-spring-file'
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
