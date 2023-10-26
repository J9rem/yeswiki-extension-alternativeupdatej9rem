<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-autoupdate-system
 */

namespace YesWiki\Alternativeupdatej9rem\Service;

use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;
use URLify;
use YesWiki\Bazar\Field\MapField;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Core\Entity\Event;
use YesWiki\Core\Service\ConfigurationService;
use YesWiki\Wiki;

class ConfigOpenAgendaService implements EventSubscriberInterface
{
    public const UNKNOWN_PLACE_NAME = 'Inconnu';

    protected $cacheMapField;
    protected $cacheTokens;
    protected $configurationService;
    protected $followedFormsIds;
    protected $formManager;
    protected $isActivated;
    protected $openAgendaParams;
    protected $params;
    protected $wiki;

    public static function getSubscribedEvents()
    {
        return [
            'entry.created' => ['followEntryCreation',-99], // negative integer to be the last one
            'entry.updated' => ['followEntryChange',-99], // negative integer to be the last one
            'entry.deleted' => ['followEntryDeletion',-99], // negative integer to be the last one
        ];
    }

    public function __construct(
        ConfigurationService $configurationService,
        FormManager $formManager,
        ParameterBagInterface $params,
        Wiki $wiki
    ) {
        $this->configurationService = $configurationService;
        $this->formManager = $formManager;
        $this->params = $params;
        $this->wiki = $wiki;
        $this->openAgendaParams = $params->get('openAgenda');
        $this->isActivated = ($this->openAgendaParams['isActivated'] ?? false) === true;
        $this->followedFormsIds = array_keys($this->openAgendaParams['associations'] ?? []);
        $this->cacheMapField = [];
        $this->cacheTokens = [];
    }

    /**
     * @param Event $event
     */
    public function followEntryCreation($event)
    {
        if ($this->isActivated){
            $entry = $this->getEntry($event);
            if ($this->shouldFollowEntry($entry)){
                try {
                    $this->createEvent($entry);
                } catch (Throwable $th) {
                    // TODO remove this trigger
                    trigger_error($th->__toString());
                }
            }
        }
    }

    /**
     * @param Event $event
     */
    public function followEntryChange($event)
    {
        if ($this->isActivated){
            $entry = $this->getEntry($event);
            if ($this->shouldFollowEntry($entry)){
            }
        }
    }

    /**
     * @param Event $event
     */
    public function followEntryDeletion($event)
    {
        if ($this->isActivated){
            $entryBeforeDeletion = $this->getEntry($event);
            if (!empty($entryBeforeDeletion) && $this->shouldFollowEntry($entryBeforeDeletion, false)){
            }
        }
    }
    
    /**
     * @param Event $event
     * @return array $entry
     */
    protected function getEntry(Event $event): array
    {
        $data = $event->getData();
        $entry = $data['data'] ?? [];
        return is_array($entry) ? $entry : [];
    }

    /**
     * get config from wakka.config.php
     * @return array [$openAgenda,$config]
     */
    public function getOpenAgendaFromConfig(): array
    {
        $config = $this->configurationService->getConfiguration('wakka.config.php');
        $config->load();
        $openAgenda = (isset($config->openAgenda)) ? $config->openAgenda : [];
        return compact(['config','openAgenda']);
    }

    /**
     * @param array $entry
     * @param bool $testDate
     * @return bool
     */
    protected function shouldFollowEntry(array $entry,bool $testDate = false): bool
    {
        return !empty($entry['id_typeannonce'])
            && !empty($entry['id_fiche'])
            && in_array($entry['id_typeannonce'],$this->followedFormsIds)
            && (
                !$testDate
                || (
                    !empty($entry['bf_date_debut_evenement'])
                    && !empty($entry['bf_date_fin_evenement'])
                )
            );
    }

    /**
     * get access token
     * @param string $keyName
     * @return array [string $token, int $expiresIn]
     * @throws Exception
     */
    public function getAccessToken(string $keyName): array
    {
        $code = $this->openAgendaParams['privateApiKeys'][$keyName] ?? '';
        if (empty($code)){
            throw new Exception('Unknown key !');
        }
        if (empty($this->cacheTokens[$code])){
            $data = $this->getRouteApi(
                'https://api.openagenda.com/v2/requestAccessToken',
                'requestAccessToken',
                [
                    'grant_type' => 'authorization_code',
                    'code' => $code
                ]
            );
            if (empty($data['access_token']) || empty($data['expires_in'])){
                trigger_error(json_encode($data));
                throw new Exception('badly formatted response !');
            }
            $this->cacheTokens[$code] = [
                'token' => $data['access_token'],
                'expiresIn' => $data['expires_in']
            ];
        }
        return $this->cacheTokens[$code];
    }
    /**
     * get events
     * @param string $formId
     * @return array 
     * @throws Exception
     */
    public function getEvents(string $formId): array
    {
        $association = $this->openAgendaParams['associations'][$formId] ?? '';
        if (empty($association)){
            throw new Exception('Unknown id !');
        }
        $data = $this->getRouteApi(
            "https://api.openagenda.com/v2/agendas/{$association['id']}/events?key={$association['public']}",
            'getEvents'
        );
        if (!is_array($data) || empty($data)){
            throw new Exception('badly formatted response !');
        }
        return $data;
    }

    /**
     * get Hello Asso route api
     * @param string $url
     * @param string $type
     * @param array|string $postData optionnal
     * @param string $bearer
     * @return mixed $resul
     * @throws Exception
     */
    protected function getRouteApi(string $url, string $type, $postData = [], string $bearer = '')
    {
        $headers = !empty($bearer) ? ["Authorization: Bearer $bearer"] : [] ;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if (!empty($postData) && (is_string($postData) || is_array($postData))) {
            curl_setopt($ch, CURLOPT_POST, true);
            if (is_array($postData)){
                $headers[] = 'Content-Type: application/json';
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            }
        } else {
            curl_setopt($ch, CURLOPT_POST, false);
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // connect timeout in seconds
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // total timeout in seconds
        $results = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if (!empty($error)) {
            throw new Exception("Error when getting $type via API : $error (httpcode: $httpCode)");
        }
        try {
            if (empty($results)){
                throw new Exception("Empty result when getting '$url' for '$type'");
            }
            $output = json_decode($results, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $th) {
            throw new Exception("Json Decode Error : {$th->getMessage()}".($th->getCode() == 4 ? " ; output : '".strval($results)."'": ''), $th->getCode(),$th);
        }
        if (is_null($output)){
            throw new Exception('Output is not json '.strval($results));
        }
        return $output;
    }

    /**
     * post to openagenda
     * @param string $name
     * @param string $formId
     * @param string $type
     * @param array $data
     * @return array $results
     * @throws Exception
     */
    protected function postToOpenAgenda(
        string $name,
        string $formId,
        string $type,
        array $data
    ):array
    {
        $association = $this->openAgendaParams['associations'][$formId];

        list('token' => $token,'expiresIn'=> $expiresIn) = 
            $this->getAccessToken($association['key']);

        $results = $this->getRouteApi(
            "https://api.openagenda.com/v2/agendas/{$association['id']}/$type",
            $name,
            [
                'access_token' => $token,
                'nonce' => $this->generateNonce(),
                'data' => $data
            ]
        );
        $this->triggerErrorsIfNeeded("openAgenda$name",$results);
        return $results;
    }

    /**
     * create an event on OpenAgenda
     * @param array $entry
     * @throws Exception
     */
    protected function createEvent(array $entry)
    {
        
        $data = $this->postToOpenAgenda(
            'createEvent',
            $entry['id_typeannonce'],
            'events',
            $this->prepareEntryData($entry)
        );
    }

    /**
     * prepare Entry data
     * @param array $entry
     * @return array
     */
    protected function prepareEntryData(array $entry): array
    {
        $entryLang = $GLOBALS['prefered_language'] ?? 'fr';
        $entryTitle = $entry['bf_titre'] ?? $entry['id_fiche'];
        $data = [
            'title' => [
                $entryLang => substr(strip_tags($entryTitle),0,140)
            ],
            'state' => 2, // published, 1= not published controller, 0 = to control
            'featured' => false // to display in head
        ];

        
        $entryDescription = 'TBD';
        $renderedEntryDescription = 'TBD';
        $entryImage = '';

        if (!empty($renderedEntryDescription)){
            $data['description'] = [
                $entryLang => substr(strip_tags($renderedEntryDescription),0,200)
            ];
        }
        if (!empty($entryDescription)){
            $data['longDescription'] = [
                $entryLang => substr(strip_tags($entryDescription),0,10000)
            ];
        }
        if (!empty($entryImage)){
            $data['image'] = [
                'url' => $entryImage
            ];
        }
        $data['timings'] = [
            [
                'begin' => $entry['bf_date_debut_evenement'],
                'end' =>  $entry['bf_date_fin_evenement']
            ]
        ];
        // TODO manage recurrence
        $data['links'] = [
            [
                'link' => $this->wiki->Href('',$entry['id_fiche'])
            ]
        ];
        $entryPlace = $this->getPlace($entry);
        if (empty($entryPlace)){
            $entryPlace = $this->createPlace($entry);
        }
        $data['locationUid'] = $entryPlace['uid'] ?? 0;
        return $data;
    }

    /**
     * create a place from entry on OpenAgenda
     * @param array $entry
     * @return array $createdPlace
     * @throws Exception
     */
    protected function createPlace(array $entry): array
    {
        $extract = $this->extractAddress($entry);

        $data = [
            'name' => $extract['name'],
            'address' => $extract['address'],
            'state' => 1, // verified by default,
            'countryCode' => 'FR' // TODO manage country code
        ];
        if (!empty($extract['town'])){
            $data['city'] = $extract['town'];
        }
        if (!empty($extract['postalCode'])){
            $data['postalCode'] = $extract['postalCode'];
        }
        if (!empty($extract['latitude'])){
            $data['latitude'] = floatval($extract['latitude']);
        }
        if (!empty($extract['longitude'])){
            $data['longitude'] = floatval($extract['longitude']);
        }

        $result = $this->postToOpenAgenda(
            'createPlace',
            $entry['id_typeannonce'],
            'locations',
            $data
        );
        return empty($result['location']) ? [] : $result['location'];
    }

    /**
     * get a place from entry on OpenAgenda
     * @param array $entry
     * @return array $place
     * @throws Exception
     */
    protected function getPlace(array $entry)
    {
        $association = $this->openAgendaParams['associations'][$entry['id_typeannonce']];

        $extract = $this->extractAddress($entry);
        
        $data = $this->getRouteApi(
            "https://api.openagenda.com/v2/agendas/{$association['id']}/locations?key={$association['public']}&search=".urlencode($extract['name']),
            'getPlace'
        );
        $this->triggerErrorsIfNeeded('openAgendaGetPlace',$data);
        $places = empty($data['locations'])
            ? []
            : array_filter(
                $data['locations'],
                function($place) use($extract){
                    return ($place['name'] ?? '') === $extract['name'];
                }
            );
        return empty($places) ? [] : $places[array_key_first($places)];
    }

    /**
     * extract address from $entry
     * @param array $entry
     * @return array (String) [$name,$address,$street,$street1,$street2,$postalCode,$town,$state,$latitude,$longitude]
     */
    protected function extractAddress(array $entry): array
    {
        $mapField = $this->getMapField($entry['id_typeannonce']);
        $fieldNames = empty($mapField)
            ? [    
                'street' => MapField::DEFAULT_FIELDNAME_STREET,
                'street1' => MapField::DEFAULT_FIELDNAME_STREET1,
                'street2' => MapField::DEFAULT_FIELDNAME_STREET2,
                'postalCode' => MapField::DEFAULT_FIELDNAME_POSTALCODE,
                'town' => MapField::DEFAULT_FIELDNAME_TOWN,
                'state' => MapField::DEFAULT_FIELDNAME_STATE
            ]
            : $mapField->getAutocompleteFieldnames();
        $geolocationFieldNames = empty($mapField)
            ? [    
                'latitude' => 'bf_latitude',
                'longitude' => 'bf_longitude'
            ]
            : [    
                'latitude' => $mapField->getLatitudeField(),
                'longitude' => $mapField->getLongitudeField()
            ];

        $extract = array_map(
            function($propertyName) use ($entry){
                return (!empty($propertyName)
                        && !empty($entry[$propertyName])
                        && is_string($entry[$propertyName])
                    )
                    ? trim($entry[$propertyName])
                    : '';
            },
            $fieldNames
        );
        
        $name = implode(',',array_filter(
            $extract,
            function($str){
                return !empty($str);
            }
        ));

        $extract['name'] = empty($name) ? self::UNKNOWN_PLACE_NAME : substr($name,0,100);
        $extract['address'] = empty($name) ? self::UNKNOWN_PLACE_NAME : substr($name,0,255);

        foreach($geolocationFieldNames as $key => $fieldName){
            $extract[$key] = empty($fieldName)
                ? ''
                : ($entry[$fieldName] ?? $entry['geolocation'][$fieldName] ?? '');
        }
        return $extract;
    }

    /**
     * trigger errors if needed
     * @param string $name
     * @param array $data
     */
    protected function triggerErrorsIfNeeded(string $name, array $data)
    {
        $errors = [];
        if (!empty($data['message'])){
            $errors[] = $data['message'];
        }
        if (!empty($data['errors'])){
            $errors[] = implode(
                ';',
                array_map(
                    function($e){
                        return 'code:'.($e['code']??'???')
                            .'=>'.($e['message']??'???')
                            .' (field:'.($e['field'] ?? '??').')';
                    },
                
                    $data['errors']
                )
            );
        }
        if (!empty($errors)){
            trigger_error("$name: ".implode('...',$errors));
        }
    }

    /**
     * generate nonce
     * @return int
     */
    protected function generateNonce(): int
    {
        return random_int(0,pow(2, 31));
    }

    /**
     * get MapField if any
     * @param string $formId
     * @return null|MapField
     */
    protected function getMapField(string $formId): ?MapField
    {
        if (!array_key_exists($formId,$this->cacheMapField)){
            $form = $this->formManager->getOne($formId);
            $this->cacheMapField[$formId] = null;
            if (!empty($form['prepared'])){
                foreach($form['prepared'] as $field){
                    if (empty($this->cacheMapField[$formId])
                        && $field instanceof MapField){
                        $this->cacheMapField[$formId] = $field;
                    }
                }
            }
        }
        return $this->cacheMapField[$formId];
    }
}
