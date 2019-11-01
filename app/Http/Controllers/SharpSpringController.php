<?php

namespace App\Http\Controllers;

use App\Campanha;
use App\Evento;
use App\Lead;
use App\Leadtimeline;
use App\Metadata;
use App\Oportunidade;
use App\Services\LeadServices;
use App\Services\OpportunityServices;
use App\Services\SharpSpringService;
use App\Sharpspring;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SharpSpringController extends Controller
{
    protected $idMySystem;
    protected $idSharpSpring;
    protected $ownerID;
    protected $url;
    protected $accountID;
    protected $secretKey;
    protected $chaves;

    public function __construct()
    {
        #$dadosSharp = Metadata::where('name','=','sharpspring')->first();
        #if(empty($dadosSharp->dados) or !isset($dadosSharp->dados)){
        #	echo "Please check, data of sharpspring keys";
        #}
        #$dadosSharp = json_decode($dadosSharp->dados,true);
        $this->url = env('SHARP_SPRING_URL');
        $this->accountID = env('SHARP_SPRING_ACCOUNT_ID');
        $this->secretKey = env('SHARP_SPRING_SECRET_KEY');
        $this->chaves = "accountID=" . $this->accountID . "&secretKey=" . $this->secretKey;
        $this->url = $this->url . '?' . $this->chaves;
    }


    public function sharpSpring($metodo, $paramsWhere)
    {
        $data = array(
            'method' => $metodo,
            'params' => $paramsWhere,
            'id' => session_id(),
        );
        $data = json_encode($data);
        $ch = curl_init($this->url);
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
        return $result;
    }


    public function getPecuniaLeads($start, $end)
    {
    }

    public function saveTimeLineLead($data, $leadId)
    {
        if (isset($data['result']['leadTimeline']) and !empty($data['result']['leadTimeline'])) {
            foreach ($data['result']['leadTimeline'] as $i => $v) {
                Leadtimeline::updateOrCreate(
                    [
                        'createTimestamp' => $v['createTimestamp'],
                        'eventSource' => $v['eventSource']
                    ],
                    [
                        'lead_id' => $leadId,
                        'whatType' => $v['whatType'],
                        'whoID' => $v['whoID'],
                        'eventName' => $v['eventName'],
                        'eventSource' => $v['eventSource'],
                        'createTimestamp' => $v['createTimestamp']
                    ]
                );
            }
        }
    }


    public function getLeadTimeline($whoID = null)
    {
        $getLeadTimeLine = $this->sharpspring(
            'getLeadTimeline',
            array(
                //'where'=>array('whoID'=>$whoID)
                'where' => array('whoID' => $whoID, 'eventSource' => 'oppCreate')
            )
        );
        return $getLeadTimeLine;
        #$this->saveTimeLineLead($getLeadTimeLine,$leadId);
    }

    public function saveOppCreate()
    {
        #466
        $leads = Lead::where('dados->leadStatus', '=', 'contactWithOpp')->whereNull('dados->data_oportunidade')->orderBy("created_at", "DESC")->get();
        foreach ($leads as $i => $v) {
            $data_oportunidade = $this->getLeadTimeline($v->dados['id']);
            if (!empty($data_oportunidade['result']['leadTimeline']) and isset($data_oportunidade['result']['leadTimeline'][0])) {
                $data_oportunidade = $data_oportunidade['result']['leadTimeline'][0]['createTimestamp'];
                $dados = $v->dados;
                $dados['data_oportunidade'] = $data_oportunidade;
                $lead = Lead::find($v->id);
                $lead->dados = $dados;
                $lead->save();
            }
        }
    }


    /*SALVA NO BANCO DE DADOS AS INFORMACOES*/


    public function getAccount($id)
    {
    }


    public function getActiveLists()
    {
        $getActiveList = $this->sharpspring(
            'getActiveLists',
            array(
                'where' => array()
            )
        );
        $getActiveList = str_replace("'", "\'", $getActiveList);
        dump($getActiveList['result']['activeList']);
    }


    public function getLeadsDateRange($start, $end)
    {
        if (!empty($start)) {
            $start = str_replace("_", ' ', $start);
        }
        if (!empty($end)) {
            $end = str_replace('_', ' ', $end);
        }
        $getLeadsDateRange = $this->sharpspring(
            'getLeadsDateRange',
            array(
                'startDate' => $start,
                'endDate' => $end,
                'timestamp' => 'create'
            )
        );
        $getLeadsDateRange = $this->sharpspring(
            'getLeadsDateRange',
            array(
                'startDate' => $start,
                'endDate' => $end,
                'timestamp' => 'update'
            )
        );
        Metadata::create(['dados' => json_encode($getLeadsDateRange, true), 'name' => 'leads_update']);
    }

    public function getFieldsByEmail($email)
    {
        $email = $this->sharpspring(
            'getLeads',
            array(
                'where' => array('emailAddress' => $email),
                'limit' => '1'
            )
        );
        return $email;
    }

    public function getUserProfiles($email)
    {
        $email = $this->sharpspring(
            'getUserProfiles',
            array(
                'where' => array('emailAddress' => $email),
                'limit' => '1'
            )
        );
        return $email;
        //return response()->json($email);
    }

    public function createList($nome, $description = null)
    {
        $createList = $this->sharpspring('createList', array('name' => $nome, 'description' => $description));
        return response()->json($createList);
    }

    public function getAccounts($ownerID)
    {
        $createList = $this->sharpspring('getAccounts', array('where' => array()));
        return response()->json($createList);
    }

    public function insertLeadIdInLeadTimeline()
    {
        $timeline = Leadtimeline::whereNull('lead_id')->get();
        foreach ($timeline as $key => $value) {
            $lead = Lead::where('json', 'like', '%"SharpSpringID":"' . $value->whoID . '"%')->first();
            $time = Leadtimeline::find($value->id);
            $time->lead_id = $lead->id;
            $time->save();
        }
    }

    public function getDealStage($dealStageID)
    {
        $createList = $this->sharpspring('getDealStage', array('id' => $dealStageID));
        return $createList;
    }


    public function getDealStages($dealStageID)
    {
        $createList = $this->sharpspring('getDealStages', array('id' => $dealStageID));
        return $createList;
    }

    public function retrieveMemberByStatusVendas($status)
    {
        $vendas = Lead::where('status_venda', '=', $status)->get();
        foreach ($vendas as $key => $value) {
            $leadID = $value->json['SharpSpringID'];
            $this->getLeadTimeline($leadID, $value->id);
            $oportunidade = $this->getOpportunityLeads($leadID);
            if (isset($oportunidade['result']['opportunity'])) {
                foreach ($oportunidade['result']['opportunity'] as $i => $v) {
                    Oportunidade::updateOrCreate(
                        [
                            'closeDate' => $v['closeDate'],
                            'oportunidade_id' => $v['id']
                        ],
                        [
                            "ownerID" => $v['ownerID'],
                            "dealStageID" => $v['dealStageID'],
                            "accountID" => $v['accountID'],
                            "campaignID" => $v['campaignID'],
                            "opportunityName" => $v['opportunityName'],
                            "probability" => $v['probability'],
                            "amount" => $v['amount'],
                            "isClosed" => $v['isClosed'],
                            "isWon" => $v['isWon'],
                            "closeDate" => $v['closeDate'],
                            "originatingLeadID" => $v['originatingLeadID'],
                            "isActive" => $v['isActive'],
                            "primaryLeadID" => $v['primaryLeadID'],
                            "lead_id" => $value->id
                        ]
                    );
                }
            }
        }
    }

    public function recuperaOportunidades()
    {
        $lead = Lead::where('leadStatus', '=', 'contactWithOpp')->get();
        foreach ($lead as $i => $v) {
        }
    }

    public function getAllListMembers()
    {
        $this->getListMembers('869270531', '2'); //ESV
        $this->getListMembers('869271555', '3'); //FORMULA
        $this->getListMembers('869269507', '4'); //QUISTO
        $this->getListMembers('869052419', '5'); //SUAV
        $this->getListMembers('869267459', '1'); //ACQUAZERO
    }

    public function getListMembers($id, $marca_id)
    {
        //FORMULA - 869271555 - 3
        //ESV - 869270531 - 2
        //QUISTO - 869269507 - 4
        //SUAV - 869052419 - 5
        $getListMembers = $this->sharpspring('getListMembers', array('where' => array('id' => $id)));
        if (isset($getListMembers['result']['getWherelistMemberGets'])) {
            foreach ($getListMembers['result']['getWherelistMemberGets'] as $key => $v) {

                //Primary Key SharpSpring Lead is $v['leadID']
                $lead = Lead::updateOrCreate(
                    [
                        'emailAddress' => $v['emailAddress']
                    ],
                    [
                        'listID' => $v['listID'],
                        'companyName' => $v['companyName'],
                        'firstName' => $v['firstName'],
                        'lastName' => $v['lastName'],
                        'emailAddress' => $v['emailAddress'],
                        'dados' => $v,
                    ]
                );
                $sharp = Sharpspring::updateOrCreate(
                    [
                        'sharpspring_id' => $v['leadID']
                    ],
                    [
                        'lead_id' => $lead->id,
                        'marca_id' => $marca_id
                    ]
                );
                $leadDataComplete = $this->getLead($v['leadID']);
                $data = $v;
                if (isset($leadDataComplete['result']['lead'][0])) {
                    $leadDataComplete = $leadDataComplete['result']['lead'][0];
                    foreach ($leadDataComplete as $leadI => $leadV) {
                        $data[$leadI] = $leadV;
                    }
                    if ($data['leadStatus'] == 'contactWithOpp') {
                        $timeline = $this->getLeadTimeline($v['leadID']);
                        if (isset($timeline['result']['leadTimeline'][0])) {
                            $timeline = $timeline['result']['leadTimeline'][0];
                            $timeline = $timeline['createTimestamp'];
                            $data['data_oportunidade'] = $timeline;
                        }
                    }
                    $l = Lead::find($lead->id);
                    $l->dados = $data;
                    $l->created_at = $data['createTimestamp'];
                    $l->save();
                }
            }
        }
        //return $getListMembers;
    }

    public function getSpecificLists($id)
    {
        $getActiveLists = $this->sharpspring('getActiveLists', array('where' => array('id' => $id)));
        return response()->json($getActiveLists);
    }

    public function getContactListMemberships($id_membro)
    {
        $getContactListMemberships = $this->sharpspring('getContactListMemberships', array('where' => array('id' => $id_membro)));
        return response()->json($getContactListMemberships);
    }

    //633109428227
    public function getLeadsSpecific($id_membro)
    {
        $getLeads = $this->sharpspring('getLeads', array('where' => array('id' => $id_membro)));
        return response()->json($getLeads);
    }

    public function getLead($id_membro)
    {
        $getLead = $this->sharpspring('getLead', array('id' => $id_membro));
        return $getLead;
    }

    public function getRelatedLeadsByEmail($id = null)
    {
        $getRelatedLeadsByEmail = $this->sharpspring('getRelatedLeadsByEmail', array('where' => array(), 'limit' => 20));
        return response()->json($getRelatedLeadsByEmail);
    }

    public function getEvents($id)
    {
        $getEvents = $this->sharpspring('getEvents', array('where' => array('leadID' => $id)));
        return response()->json($getEvents);
    }

    /**
     * subscribe on webhooks SharpSpring
     */
    public function subscribeToLeadUpdates()
    {
        $url = $this->sharpspring('subscribeToLeadUpdates', array('url' => 'https://sistema.encontresuafranquia.com.br/jsonCrudeSharpSpring'));
        dump($url);
    }

    public function listeningUpdates(Request $request)
    {
        Evento::create(['nome' => 'TESTE', 'json' => json_encode($request->all(), true)]);
    }


    public function logCalls($userID, $leadID, $direction, $callResult)
    {
        $logCalls = $this->sharpspring('logCalls', array(
            'userID' => $userID,
            'leadID' => $leadID,
            'direction' => $direction,
            'callResult' => $callResult
        ));
        dump($logCalls);
    }

    public function getOpportunity($opportunityID, $leadID)
    {
        $getOpportunityLeads = $this->sharpspring('getOpportunity', array('id' => $opportunityID));
        return $getOpportunityLeads;
    }

    public function getOpportunityLeads($leadID = null)
    {
        $getOpportunityLeads = $this->sharpspring('getOpportunityLeads', array('where' => array('leadID' => $leadID)));
        if (isset($getOpportunityLeads['result']['getWhereopportunityLeads']) and !empty($getOpportunityLeads['result']['getWhereopportunityLeads'])) {
            foreach ($getOpportunityLeads['result']['getWhereopportunityLeads'] as $i => $v) {
                $getOpportunity = $this->getOpportunity($v['opportunityID'], $v['leadID']);
                return $getOpportunity;
            }
        }
    }


    public function getAmountStatusVendeu()
    {
        $leads = Lead::where('status_venda', '=', 'Vendeu')->get();
        foreach ($leads as $i => $v) {
            $this->getOpportunityLeads($v->json['SharpSpringID'], $v->id);
        }
    }

    public function getOpportunityLeadUnique($leadID = null)
    {
        $getOpportunityLead = $this->sharpspring('getOpportunityLead', array('id' => $leadID));
        dump($getOpportunityLead);
        if (isset($getOpportunityLead['result']['opportunity']) and !empty($getOpportunityLead['result']['opportunity'])) {
        }
    }

    public function richDataLeads()
    {
        $leadsNull = Lead::get();
        foreach ($leadsNull as $key => $value) {
            $lead = $this->getLead($value->dados['leadID']);
            if (isset($lead['result']['lead'][0]) and !empty($lead['result']['lead'][0])) {
                $leads = $lead['result']['lead'][0];
                $dados = $value->dados;
                foreach ($leads as $i => $v) {
                    if (!isset($dados[$i])) {
                        $dados[$i] = $v;
                    }
                    if (!empty($v)) {
                        $dados[$i] = $v;
                    }
                }
                $updateLead = Lead::find($value->id);
                $updateLead->dados = $dados;
                $updateLead->save();
            }
        }
    }

    public function richDataLeadsOpportunities()
    {
        $lead = Lead::where('dados->leadStatus', '=', 'contactWithOpp')->whereNull('dados->opportunity')->orderBy("created_at", "DESC")->get();
        foreach ($lead as $i => $v) {
            $leadNow = $v->dados;
            $opportunity = $this->getOpportunityLeads($v->dados['id']);
            if (isset($opportunity['result']['opportunity'][0])) {
                $opportunity = $opportunity['result']['opportunity'][0];
                $leadNow['opportunity'][] = $opportunity;
            }
            $leadUpdate = Lead::find($v->id);
            $leadUpdate->dados = $leadNow;
            $leadUpdate->save();
        }
    }

    public function getOpportunityLead()
    {
        $getOpportunityLead = $this->sharpspring(
            'getLeads',
            array(
                'where' => array(),
                'offset' => '1',
                'limit' => '1'
            )
        );
        return $getOpportunityLead;
    }


    public function updateOpportunities($id)
    {
        $updateOpportunities = $this->sharpspring('updateOpportunities', array('where' => array('leadID' => $id)));
        return response()->json($updateOpportunities);
    }


    public function createOpportunityLeads()
    {
        $createOpportunityLeads = $this->sharpspring(
            'createOpportunityLeads',
            array(
                'params' => array(
                    'leadID' => '669768306691',
                    'opportunityID' => ''
                )
            )
        );
        return response()->json($createOpportunityLeads);
    }


    public function getOpportunities($id = null)
    {
        $getOpportunities = $this->sharpspring('getOpportunities', array('where' => array('ownerID' => $id)));

        return $getOpportunities;
    }

    public function getAllOpportunities()
    {
        $getOpportunities = $this->sharpspring('getOpportunities', array('where' => array()));
        dump($getOpportunities);
        //return $getOpportunities;
    }

    public function getCampaign($campaignId = null)
    {
        $getCampaign = $this->sharpspring('getCampaign', array('id' => $campaignId));
        return $getCampaign;
    }

    public function getCampaigns()
    {
        $getCampaign = $this->sharpspring('getCampaigns', array('where' => array()));
        if (isset($getCampaign['result']['campaign']) and !empty($getCampaign['result']['campaign'])) {
            foreach ($getCampaign['result']['campaign'] as $i => $v) {
                $campanha = Campanha::updateOrCreate(
                    ['campaignName' => $v['campaignName']],
                    [
                        "campaignName" => $v['campaignName'],
                        "campaignType" => $v['campaignType'],
                        "campaignAlias" => $v['campaignAlias'],
                        "campaignOrigin" => $v['campaignOrigin'],
                        "qty" => $v['qty'],
                        "price" => $v['price'],
                        "goal" => $v['goal'],
                        "otherCosts" => $v['otherCosts'],
                        "startDate" => $v['startDate'],
                        "endDate" => $v['endDate'],
                        "isActive" => $v['isActive']
                    ]
                );
                Sharpspring::updateOrCreate(
                    [
                        'sharpspring_id' => $v['id']
                    ],
                    [
                        'campanha_id' => $campanha->id
                    ]
                );
            }
        }
    }

    public function deletarfile($folder, $file, $name)
    {

        $delete = Storage::delete($folder . '/' . $file . '/' . $name);
        return redirect()->route('subirLeads');
    }

    /**
     * Update opportunities between dates
     * this request spend two request of sharpspring
     * @param Request $request
     * @param OpportunityServices $opportunityServices
     * @param SharpSpringService $sharpSpringService
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Exception
     */
    public function opportunitiesUpdate(Request $request, OpportunityServices $opportunityServices, SharpSpringService $sharpSpringService)
    {
        $request->validate([
            'dataInit' => 'required|date',
            'dataEnd' => 'required|date',
        ]);
        $initDate = new Carbon($request->dataInit);
        $endDate = new Carbon($request->dataEnd);

        try {
            if ($initDate->diffInDays($endDate) > 5) {
                $opportunityServices->updateOpportunities($initDate, $endDate);
            } else {
                $sharpSpringService->assocOpportunityToLead($initDate, $endDate);
                return response('update with success', 200);
            }
            return response('Your request will be process', 200);

        } catch (\Exception $e) {
            return response($e->getMessage(), 500);
        }

    }

    /**
     *
     * @param string $type 'sharp-spring' or 'sharp-spring-file'
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function metaDataProcess($type = 'sharp-spring', LeadServices $leadService)
    {
        if ($type == 'sharp-spring') {
            $leadService->processData(new \App\Sources\SharpSpringSource());
        } else {
            $leadService->processDataFile(new \App\Sources\SharpSpringSource());
        }

        return response('processed with success', 200);
    }

}
