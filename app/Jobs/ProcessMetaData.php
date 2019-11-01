<?php

namespace App\Jobs;

use App\FileImport;
use App\Services\LeadServices;
use App\Services\OpportunityServices;
use App\Services\SharpSpringService;
use App\Sources\SharpSpringSource;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessMetaData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $brand = null;
    private $leadServices = null;
    public $type = null;
    public $fileImport = null;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(FileImport $fileImport,$type = 'sharp-spring', $brand = null)
    {
        $this->type = $type;
        $this->brand = $brand;
        $this->fileImport = $fileImport;
        $this->leadServices = new LeadServices();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->type == 'sharp-spring-file') {
            $this->leadServices->processDataFile(new SharpSpringSource());
        } else {
            $opportunityService = new OpportunityServices();
            $opportunityService->processeMetaData();
        }
        $this->fileImport->status = 'processed';
        $this->fileImport->save();

    }
}
