<?php

namespace App\Sources;


use App\Abstracts\SourceDataAbstract;
use App\Interfaces\SourceData;
use App\Lead;
use App\Metadata;
use App\Opportunity;
use App\Services\SharpSpringService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;


/**
 * Class SharpSpringSource
 * Interface with source SharpSpring with the system
 * @package App\Sources
 * @author wellingtonfds
 */
class SharpSpringSource extends SourceDataAbstract implements SourceData
{
    const SHARP_SPRING_FILE = 'sharp-spring-file';
    const SHARP_SPRING_DATABASE = 'sharp-spring';
    protected $fillable = [
        'status_source_data' => 'leadStatus',
        'id_source_data' => 'id',
        'created_source_data' => 'createTimestamp',
        'updated_source_data' => 'updateTimestamp',
        'first_name' => 'firstName',
        'last_name' => 'lastName',
        'email' => 'emailAddress'
    ];

    /**
     * @param string $origin
     * @param array $positionData
     * @param Metadata $metaData
     * @throws \Exception
     */
    private function processMetaData($origin = self::SHARP_SPRING_DATABASE, $positionData = [], Metadata $metaData)
    {
        if (!empty($metaData)) {
            if ($origin == self::SHARP_SPRING_DATABASE) {
                foreach ($metaData->dataJson as $key => $value) {
                    if ($value) {
                        $this->updateOrCreateLead($value, $positionData, $origin);
                    }
                }
            } elseif ($origin == self::SHARP_SPRING_FILE) {
                $this->updateOrCreateLead($metaData->dataJson, $positionData, $origin);
            }
        }
        if(env('DELETE_METADATA',true)){
            $metaData->delete();
        }
    }

    /**
     * Make parse json to format off sharpSpring
     * @param $data
     * @return Array
     */
    private function parseDataToSharpSpringStructureJson($data): Array
    {
        return [
            "id" => $data['sharpspring_id'],
            "city" => $data['city'],
            "state" => $data['state'],
            "title" => $data['title'],
            "active" => null,
            "street" => $data['street'],
            "country" => $data['country'],
            "ownerID" => $data['owner_id'],
            "persona" => null,
            "website" => $data['website'],
            "zipcode" => $data['zip'],
            "industry" => $data['industry'],
            "lastName" => $data['last_name'],
            "accountID" => null,
            "faxNumber" => $data['fax'],
            "firstName" => $data['first_name'],
            "leadScore" => $data['lead_score'],
            "campaignID" => $data['primary_campaign_id'],
            "campaignName" => $data['primary_campaign_name'],
            "leadStatus" => $data['lead_status'],
            "trackingID" => null,
            "companyName" => $data['company_name'],
            "description" => $data['description'],
            "phoneNumber" => $data['phone_number'],
            "emailAddress" => $data['email'],
            "isUnsubscribed" => $data['is_unsubscribed'],
            "createTimestamp" => $data['lead_create_date'],
            "updateTimestamp" => $data['lead_create_date'],
            "leadScoreWeighted" => 0,
            "mobilePhoneNumber" => $data['mobile_phone'],
            "officePhoneNumber" => $data['office_phone_number'],
            "pa__s_5d14c2825eace" => $data['country'],
            "cidade_5d14c23cb4ce4" => $data['city'],
            "estado_5d14c267d65bb" => $data['state'],
            "phoneNumberExtension" => $data['extension'],
            "profiss__o_5d16049c6f674" => $data['profissao'],
            "estado_civil_5d1607c8b09cd" => $data['estado_civil'],
            "primeiro_dono_5d31ae505ac68" => null,
            "interesse_do_lead_5d14bfc57453e" => $data['nivel_de_interesse_do_lead'],
            "marca_de_interesse_5d14c0a4b9c67" => $data['marca_de_interesse'],
            "tomada_de_decis__o_5d1604e4261d5" => $data['tomada_de_decisao'],
            "tempo_para_investir_5d14b86be7e6d" => $data['tempo_para_investir'],
            "dono_da_oportunidade_5d31ae5bb890a" => null,
            "data_do___ltimo_contato_5d14bee891c1a" => $data['data_do_nltimo_contato'],
            "data_do_primeiro_contato_5d14bebcba458" => $data['data_do_primeiro_contato'],
            "capital_para_investimento_5d14b7858773d" => $data['capital_para_investimento'],
            "data_do_primeiro_contato__1__5d1a8ba240b6a" => "",
            "melhor_hor__rio_para_contato_5d1a70088ee02" => $data['melhor_horario_para_contato'],
            "motivo_de_finaliza____o_do_contato_5d1cdeda7794e" => $data['motivo_de_finalizaoao_do_contato']

        ];
    }

    /**
     * Get metadata when choose a source with const SHARP_SPRING_FILE or SHARP_SPRING_DATABASE
     * @param string $origin
     * @return Collection
     */
    public function getMetaDataInDatabase($origin = self::SHARP_SPRING_DATABASE): Collection
    {
        return Metadata::where('origin', $origin)->orderBy('id', 'DESC')->take(1)->get();
    }

    /**
     * Update or create a lead basement in positionData
     * When the source is from a file, we don't update information with API.
     * @param string $value
     * @param array $positionData
     * @param string $origin
     * @param boolean $updateOpportunity default is false
     * @return Lead
     * @throws \Exception
     */
    public function updateOrCreateLead($value, $positionData = [], $origin = self::SHARP_SPRING_DATABASE, $updateOpportunity = false): Lead
    {
        if (empty($value)) {
            throw  new \Exception('Can\'t update or create when $value is empty');
        }
        if (empty($positionData)) {
            $positionData = $this->fillable;
        }

        $leadCheck = Lead::where('id_source_data', $value[$positionData['id_source_data']])->first();


        $created_at = new Carbon($value[$positionData['created_source_data']]);
        $updated_at = new Carbon($value[$positionData['updated_source_data']]);

        $lead = Lead::updateOrCreate([
            'id' => isset($leadCheck->id) ? $leadCheck->id : null,
        ], [
            'status_source_data' => $value[$positionData['status_source_data']],
            'id_source_data' => $value[$positionData['id_source_data']],
            'origin_source_data' => 'sharp-spring',
            'created_source_data' => $created_at->subHours(3),
            'updated_source_data' => $updated_at->subHours(3),
            'first_name' => $value[$positionData['first_name']] ?? '',
            'last_name' => $value[$positionData['last_name']] ?? '',
            'email' => $value[$positionData['email']] ?? '',
            'data' => $origin == self::SHARP_SPRING_FILE ? $this->parseDataToSharpSpringStructureJson($value) : $value,
        ]);
        $status = array_search(
            $value[$positionData['status_source_data']],
            ['contact', 'contactWithOpp']
        );
        if ($status != false && $origin != self::SHARP_SPRING_FILE && $updateOpportunity) {
            $sharpSpringService = new SharpSpringService();
            $opportunities = $sharpSpringService->getOpportunityLeads($lead->id_source_data);
            foreach ($opportunities as $opportunity) {
                $opportunityCheck = Opportunity::where('id_source_data', $opportunity['id'])->first();
                $lead->opportunities()->updateOrCreate([
                    'id' => isset($opportunityCheck->id) ? $opportunityCheck->id : null,
                ], [
                    'id_source_data' => $opportunity['id'],
                    'name' => $opportunity['opportunityName'] ?? '',
                    'is_active' => $opportunity['isActive'],
                    'is_closed' => $opportunity['isClosed'],
                    'is_won' => $opportunity['isWon'],
                    'close_date' => $opportunity['closeDate'],
                    'id_campaign' => $opportunity['campaignID'],
                    'data' => $opportunity,
                ]);
            }
        }
        return $lead;
    }

    /**
     * Process data from database
     * @param string $origin
     * @param array $positionData
     * @return void
     * @throws \Exception
     */
    public function processData($origin = self::SHARP_SPRING_DATABASE, $positionData = []): void
    {
        $metaData = Arr::first($this->getMetaDataInDatabase($origin));
        $this->processMetaData($origin, $positionData, $metaData);

    }

    /**
     * import data from table metadata
     * @return void
     * @throws \Exception
     */
    public function importDatabase(): void
    {
        while (count($this->getMetaDataInDatabase())) {
            $this->processData(self::SHARP_SPRING_DATABASE, $this->fillable);
        }
    }

    /**
     * import data from table metadata with origin sharp-spring-file
     * and process the data
     * @return void
     * @throws \Exception
     */
    public function importDatabaseFile(): void
    {
        while (count($this->getMetaDataInDatabase(self::SHARP_SPRING_FILE))) {
            $this->processData(self::SHARP_SPRING_FILE, [
                'status_source_data' => 'lead_status',
                'id_source_data' => 'sharpspring_id',
                'created_source_data' => 'lead_create_date',
                'updated_source_data' => 'lead_create_date',
                'first_name' => 'first_name',
                'last_name' => 'last_name',
                'email' => 'email'
            ]);
        }
    }

    /**
     * Process a metadata only from origin sharp-spring
     * @param Metadata $metadata
     * @return void
     * @throws \Exception
     */
    public function processOneMetaData(Metadata $metadata): void
    {
        $this->processMetaData(self::SHARP_SPRING_DATABASE, $this->fillable, $metadata);
    }


}
