<?php

namespace App\Services;

use App\Interfaces\ServiceLayer;
use App\Jobs\UpdateOpportunities;
use App\Metadata;
use App\Opportunity;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class OpportunityServices implements ServiceLayer
{

    private $meta = null;
    /**
     * @param Request $request
     * @return array
     */
    public function filterOpportunities(Request $request): array
    {
        $dataChart = [
            "labels" => [],
            "data" => [],
            "total" => 0
        ];
        $dateFormat = 'Y-m-d';
        if ($request->has('group')) {
            switch (Arr::first($request->group)) {
                default:
                    $dateFormat = 'Y-m-d';
                    break;
                case 'monthly':
                    $dateFormat = 'Y-m';
                    break;
                case 'yearly':
                    $dateFormat = 'Y';
                    break;
            };
        }
        QueryBuilder::for(Opportunity::class)
            ->allowedFilters([
                AllowedFilter::scope('between_source_data_created'),
                AllowedFilter::scope('by_brand')
            ])
            ->orderBy('data->createTimestamp', 'ASC')
            ->get()
            ->groupBy(function (Opportunity $opportunity) use ($dateFormat) {
                if ($opportunity->data != null) {
                    return Carbon::parse($opportunity->data['createTimestamp'])->format($dateFormat); // grouping by year
                }
            })
            ->map(function ($opportunity, $key) use (&$dataChart) {
                $opportunityCount = $opportunity->count();
                array_push($dataChart['labels'], $key);
                array_push($dataChart['data'], $opportunityCount);
                $dataChart['total'] += $opportunityCount;
                return [
                    'label' => $key,
                    'value' => $opportunity->count(),
                ];
            });
        return $dataChart;
    }

    public function updateOpportunities(Carbon $startDate, Carbon $endDate)
    {
        $period = new CarbonPeriod($startDate, '5 days', $endDate);
        $initDate = '';
        foreach ($period as $key => $value) {
            if (empty($initDate)) {
                $initDate = $value;
            } else {
                $aux = $value->copy();
                UpdateOpportunities::dispatch($initDate, $aux->endOfDay());
                $initDate = $value;
            }
        }
    }
    public function getMetaDataInDatabase()
    {
        $this->meta =  Metadata::where('origin', 'sharp-spring-opp')->orderBy('id', 'DESC')->take(1)->get();
        return $this->meta;
    }

    public function processeMetaData()
    {

        while (count($this->getMetaDataInDatabase())) {
            $meta = Arr::first($this->meta);
            $this->formatOpp($meta->dataJson, $meta);
        }
    }

    public function formatOpp($data, $meta)
    {
        $opp =  [
            "id" => $data['opp_id'],
            "isWon" => $data['is_won'],
            "amount" => $data['amount'],
            "ownerID" => $data['owner_id'],
            "ownerName" => $data['owner_name'],
            "ownerEmail" => $data['owner_email'],
            "isActive" => "",
            "isClosed" => $data['is_closed'],
            "accountID" => "",
            "closeDate" => $data['close_date'],
            "campaignID" => "",
            "campaignName" => $data['campaign_name'],
            "dealStageID" => $data['deal_stage_id'],
            "probability" => $data['probability'],
            "primaryLeadID" => $data['primary_lead_id'],
            "createTimestamp" => $data['date_created'],
            "opportunityName" => $data['opp_name'],
            "cep_5d1e10112eafd" => $data['cep'],
            "originatingLeadID" => $data['originating_lead_id'],
            "notas_5d14c4698a919" => null,
            "bairro_5d1e0fa093031" => $data['bairro'],
            "cidade_5d1e0fc4d1481" => $data['cidade'],
            "n__mero_5d1e0f6131456" => $data['numero'],
            "cnpj_cpf_5d1e0efee429a" => $data['cnpj/cpf'],
            "endere__o_5d1e0f3f7b365" => $data['endereoo'],
            "complemento_5d1e0f89bbda7" => $data['complemento'],
            "estado__uf__5d1e0feb2a184" => $data['estado_(uf)'],
            "nome_completo_5d1e0e928e0d3" => $data['nome_completo/titular'],
            "tipo_de_pessoa_5d1e0edf93ef5" => $data['tipo_de_pessoa'],
            "marca_da_franquia_5d2384fae9451" => $data['marca_da_franquia'],
            "email_para_contato_5d1e114f51551" => $data['email_para_contato'],
            "telefone_para_contato_5d1e11724b84c" => $data['telefone_para_contato'],
            "status_da_negocia____o_5d14bb56b9a0b" => $data['status_da_negociaoao'],
            "data_do_fechamento_da_venda_5d276b588c4eb" => $data['data_do_fechamento_da_venda'],
            "comprovante_de_pagamento_n__1_5d14bd12de02d" => $data['comprovante_de_pagamento_nº1'],
            "comprovante_de_pagamento_n__2_5d14bd2546b80" => $data['comprovante_de_pagamento_nº2'],
            "comprovante_de_pagamento_n__3_5d14bd449c4ac" => $data['comprovante_de_pagamento_nº3'],
            "comprovante_de_pagamento_n__4_5d14bd5094890" => $data['comprovante_de_pagamento_nº4'],
            "modalidade_da_franquia_escolhida_5d1e112bc1933" => $data['modalidade_da_franquia_escolhida'],
            "data_da_mudan__a_para_primeiro_retorno__5d276aac095e5" => $data['data_do_primeiro_contato']
        ];
        Opportunity::create([
            'name'=>$data['opp_name'],
            'amount'=>$data['amount'],
            'status_negotiation'=>$data['status_da_negociaoao'],
            'is_active'=>0,
            'is_closed'=>$data['is_closed'],
            'is_won'=>$data['is_won']>0?true:false,
            'close_date'=>$data['close_date'],
            'lead_id'=> $data['primary_lead_id'],
            'id_source_data'=>$data['opp_id'],
            'id_campaign'=>'0',
            'brand_id'=>$meta->config['brand'],
            'data'=>$opp,

        ]);
        $meta->delete();
    }
}
