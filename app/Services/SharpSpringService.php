<?php

namespace App\Services;


use App\Brand;
use App\Interfaces\ServiceLayer;
use App\Interfaces\SourceData;
use App\Lead;
use App\Metadata;
use App\Sources\SharpSpringSource;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use phpDocumentor\Reflection\Types\Array_;

class SharpSpringService implements ServiceLayer
{
    /**
     * @param $method
     * @param $params
     * @return Array_
     * @throws \Exception
     */
    private function requestApi($method, $params): Array
    {
        $sharpUrl = env('SHARP_SPRING_URL');
        $accountId = env('SHARP_SPRING_ACCOUNT_ID');
        $secretKey = env('SHARP_SPRING_SECRET_KEY');
        $url = "$sharpUrl?accountID=$accountId&secretKey=$secretKey";
        $data = array(
            'method' => $method,
            'params' => $params,
            'id' => session_id(),
        );
        $data = json_encode($data);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data)
        ));
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result, true);
        if(is_array($result)){
            return $result;
        }
        throw new \Exception('Please check your credentials or query or number of request ');
    }

    /**
     * @param $id
     * @return array
     * @throws \Exception
     */
    public function getLead($id): array
    {
        $lead = $this->requestApi('getLeads', array('where' => array('id' => $id)));
        if (isset($lead['result']['lead'])) {
            return Arr::first($lead['result']['lead']);
        }
        return [];

    }

    /**
     * @param $dealStageID
     * @return Array
     * @throws \Exception
     */
    public function getDealStage($dealStageID)
    {
        $createList = $this->requestApi('getDealStage', array('id' => $dealStageID));
        if (isset($createList['result']['dealStage'])) {
            return $createList['result']['dealStage'];
        }
        return [];
    }

    /**
     * get
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate]
     * @throws \Exception
     * @return array
     */
    public function getOpportunitiesList(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        $opportunities = [];
        $startDate->startOfDay();
        $endDate->endOfDay();
        $query = [
            'startDate' => $startDate->format('Y-m-d H:i:s'),
            'endDate' => $endDate->format('Y-m-d H:i:s'),
            'timestamp' => 'update',
        ];

        $update = $this->requestApi('getOpportunitiesDateRange', $query);
        $query['timestamp'] = 'create';
        $create = $this->requestApi('getOpportunitiesDateRange', $query);
        if (isset($create['result']['opportunity'])) {
            $opportunities = array_merge($opportunities, $create['result']['opportunity']);
        }
        if (isset($update['result']['opportunity'])) {
            $opportunities = array_merge($opportunities, $update['result']['opportunity']);
        }
        return $opportunities;
    }

    /**
     * Assoc lead to opportunity and opportunity to dealStage
     * This method spending tree requests
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @throws \Exception
     */
    public function assocOpportunityToLead(Carbon $startDate, Carbon $endDate): void
    {
        $sharpSpringSource = new SharpSpringSource();
        $opportunities = $this->getOpportunitiesList($startDate, $endDate);
        foreach ($opportunities as $opportunity) {
            $opportunityId = $opportunity['originatingLeadID'];
            if ($opportunity['originatingLeadID'] == "0" || $opportunity['originatingLeadID'] == null) {
                $opportunityId = $opportunity['primaryLeadID'];
            }
            $lead = Lead::where('id_source_data', $opportunityId)->where('data->id')->first();
            if (empty($lead)) {
                $lead = $this->getLead($opportunityId);
                $lead = $sharpSpringSource->updateOrCreateLead($lead);
            }
            //$opportunityCheck = Opportunity::where('id_source_data', $opportunity['id'])->first();
            $brand = Brand::where('name', $opportunity['marca_da_franquia_5d2384fae9451'])->first();
            $lead->opportunities()->updateOrCreate([
                'id_source_data' => $opportunity['id'],
            ], [
                'id_source_data' => $opportunity['id'],
                'status_negotiation' => $opportunity['status_da_negocia____o_5d14bb56b9a0b'],
                'brand_id' => isset($brand->id) ? $brand->id : null,
                'amount' => $opportunity['amount'],
                'name' => $opportunity['opportunityName'],
                'is_active' => $opportunity['isActive'],
                'is_closed' => $opportunity['isClosed'],
                'is_won' => $opportunity['isWon'],
                'close_date' => $opportunity['closeDate'],
                'id_campaign' => $opportunity['campaignID'],
                'data' => $opportunity,
            ]);

        }
    }

    /**
     * @param Metadata $metadata
     * @param SourceData $sourceData
     * @return void
     */
    public function processMetaData(Metadata $metadata, SourceData $sourceData): void
    {
        $sourceData->processOneMetaData($metadata);
    }

    /**
     * Get all dealStages between range date
     * The method spend a request
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     * @throws \Exception
     */
    public function getDealStagesDateRange(Carbon $startDate, Carbon $endDate)
    {
        $createDealStage = $this->requestApi('getDealStagesDateRange', [
            'startDate' => $startDate->format('Y-m-d H:i:s'),
            'endDate' => $endDate->format('Y-m-d H:i:s'),
            'timestamp' => 'create',
        ]);
        if (empty($createDealStage['error'])) {
            if (isset($createDealStage['result']['dealStage'])) {
                return $createDealStage['result']['dealStage'];
            }
        }
        return [];
    }

}
