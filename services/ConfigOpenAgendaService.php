<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-open-agenda-connect
 */

namespace YesWiki\Alternativeupdatej9rem\Service;

use DateInterval;
use DateTimeImmutable;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;
use URLify;
use YesWiki\Bazar\Field\ImageField;
use YesWiki\Bazar\Field\MapField;
use YesWiki\Bazar\Field\TextareaField;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Core\Entity\Event;
use YesWiki\Core\Service\ConfigurationService;
use YesWiki\Core\Service\TripleStore;
use YesWiki\Wiki;

class ConfigOpenAgendaService implements EventSubscriberInterface
{
    public const TRIPLE_PROPERTY = 'https://yeswiki.net/triple/openagenda/event/uid';
    public const UNKNOWN_PLACE_NAME = 'France';

    protected $cacheDescriptionPropertyName;
    protected $cacheImagePropertyName;
    protected $cacheMapField;
    protected $cacheTokens;
    protected $configurationService;
    protected $followedFormsIds;
    protected $entryManager;
    protected $formManager;
    protected $isActivated;
    protected $openAgendaParams;
    protected $params;
    protected $tripleStore;
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
        EntryManager $entryManager,
        FormManager $formManager,
        ParameterBagInterface $params,
        TripleStore $tripleStore,
        Wiki $wiki
    ) {
        $this->configurationService = $configurationService;
        $this->entryManager = $entryManager;
        $this->formManager = $formManager;
        $this->params = $params;
        $this->tripleStore = $tripleStore;
        $this->wiki = $wiki;
        $this->openAgendaParams = $params->get('openAgenda');
        $this->isActivated = ($this->openAgendaParams['isActivated'] ?? false) === true;
        $this->followedFormsIds = array_keys($this->openAgendaParams['associations'] ?? []);
        $this->cacheDescriptionPropertyName = [];
        $this->cacheImagePropertyName = [];
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
                try {
                    $this->updateEvent($entry);
                } catch (Throwable $th) {
                    trigger_error($th->__toString());
                }
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
                try {
                    $this->deleteEvent($entryBeforeDeletion);
                } catch (Throwable $th) {
                    trigger_error($th->__toString());
                }
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
                    && empty($entry['bf_date_fin_evenement_data']['recurrentParentId'])
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
     * @param array $headersFromExt
     * @param string $customRequest
     * @return mixed $resul
     * @throws Exception
     */
    protected function getRouteApi(string $url, string $type, $postData = [], array $headersFromExt = [], string $customRequest = '')
    {
        $headers = array_filter($headersFromExt,'is_string') ;
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
            if (!empty($customRequest) && in_array($customRequest,['PATCH','PUT','GET','DELETE','HEAD','CONNECT'])){
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $customRequest);
            }
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
     * delete to openagenda
     * @param string $name
     * @param string $formId
     * @param string $type
     * @return array $results
     * @throws Exception
     */
    protected function deleteFromOpenAgenda(
        string $name,
        string $formId,
        string $type
    ):array
    {
        $association = $this->openAgendaParams['associations'][$formId];

        list('token' => $token,'expiresIn'=> $expiresIn) = 
            $this->getAccessToken($association['key']);

        $results = $this->getRouteApi(
            "https://api.openagenda.com/v2/agendas/{$association['id']}/$type",
            $name,
            [],
            [
                "access-token: $token",
                "nonce: {$this->generateNonce()}"
            ],
            'DELETE'
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

        if (!empty($data['event']['uid'])){
            $this->registerUid($entry['id_fiche'],$data['event']['uid']);
        }
    }
    
    /**
     * update an event on OpenAgenda
     * @param array $entry
     * @throws Exception
     */
    protected function updateEvent(array $entry)
    {
        $uid = $this->getUid($entry['id_fiche']);
        if (empty($uid)){
            $this->createEvent($entry);
        } else {
            $data = $this->postToOpenAgenda(
                'updateEvent',
                $entry['id_typeannonce'],
                "events/$uid",
                $this->prepareEntryData($entry)
            );
        }
    }

    /**
     * delete an event from OpenAgenda
     * @param array $entry
     * @throws Exception
     */
    protected function deleteEvent(array $entry)
    {
        $uid = $this->getUid($entry['id_fiche']);
        if (!empty($uid)){
            // first update with empty data
            $data = $this->postToOpenAgenda(
                'setEventToDeleted',
                $entry['id_typeannonce'],
                "events/$uid",
                $this->prepareEntryData(array_merge($entry,[
                    'bf_titre' => '==DELETED=='
                ]))
            );
            $data = $this->deleteFromOpenAgenda(
                'deleteEvent',
                $entry['id_typeannonce'],
                "events/$uid"
            );
            $this->deleteAllUid($entry['id_fiche']);
        }
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

        $desc = $this->getDescriptions($entry);
        $imagePropName = $this->getImagePropertyName($entry['id_typeannonce']);
        $entryImage = $entry[$imagePropName] ?? '';

        $data['description'] = [
            $entryLang => empty($desc['description'])
                ? substr(strip_tags($entryTitle),0,140)
                : substr($desc['description'],0,199).(strlen($desc['description'])>199?'…':'')
        ];
        if (!empty($desc['longDescriptionMarkdown'])){
            $data['longDescription'] = [
                $entryLang => substr($desc['longDescriptionMarkdown'],0,9995).(strlen($desc['longDescriptionMarkdown'])>9995?'…':'')
            ];
        }
        if (!empty($entryImage)
            && preg_match('/\.(?:png|jpg|jpeg|bmp|webp)$/i',$entryImage)
        ){
            $baseUrl = explode('?',$this->params->get('base_url'))[0];
            if (!preg_match('/^http?:\\/\\/localhost\\//',$baseUrl)){
                $data['image'] = [
                    'url' => "{$baseUrl}files/$entryImage"
                ];
            }
        }
        $data['timings'] = $this->getTimings($entry);
        
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
        $defaultFieldsNames =  [    
            'street' => MapField::DEFAULT_FIELDNAME_STREET,
            'street1' => MapField::DEFAULT_FIELDNAME_STREET1,
            'street2' => MapField::DEFAULT_FIELDNAME_STREET2,
            'postalCode' => MapField::DEFAULT_FIELDNAME_POSTALCODE,
            'town' => MapField::DEFAULT_FIELDNAME_TOWN,
            'state' => MapField::DEFAULT_FIELDNAME_STATE
        ];
        $fieldNames = empty($mapField)
            ? $defaultFieldsNames
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

        $extract = [];
        foreach(array_keys($defaultFieldsNames) as $key){
            $extract[$key] = (!empty($fieldNames[$key])
                    && !empty($entry[$fieldNames[$key]])
                    && is_string($entry[$fieldNames[$key]])
                )
                ? trim($entry[$fieldNames[$key]])
                : '';
        }
        
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

    /**
     * get description propertyName if any
     * @param string $formId
     * @return string|TextareaField
     */
    protected function getDescriptionField(string $formId)
    {
        if (!array_key_exists($formId,$this->cacheDescriptionPropertyName)){
            $form = $this->formManager->getOne($formId);
            $this->cacheDescriptionPropertyName[$formId] = null;
            if (!empty($form['prepared'])){
                foreach($form['prepared'] as $field){
                    if (
                        $field instanceof TextareaField
                        && (
                            empty($this->cacheDescriptionPropertyName[$formId])
                            || $field->getPropertyName() === 'bf_description'
                        )
                    ){
                        $this->cacheDescriptionPropertyName[$formId] = $field;
                    }
                }
                if (empty($this->cacheDescriptionPropertyName[$formId])){
                    $this->cacheDescriptionPropertyName[$formId] = 'bf_description';
                }
            }
        }
        return $this->cacheDescriptionPropertyName[$formId];
    }

    /**
     * get description image property name if any
     * @param string $formId
     * @return string
     */
    protected function getImagePropertyName(string $formId): string
    {
        if (!array_key_exists($formId,$this->cacheImagePropertyName)){
            $form = $this->formManager->getOne($formId);
            $this->cacheImagePropertyName[$formId] = '';
            if (!empty($form['prepared'])){
                foreach($form['prepared'] as $field){
                    if (
                        empty($this->cacheImagePropertyName[$formId])
                        && $field instanceof ImageField
                    ){
                        $this->cacheImagePropertyName[$formId] = $field->getPropertyName();
                    }
                }
            }
        }
        return $this->cacheImagePropertyName[$formId];
    }

    /**
     * get descriptions
     * @param array $entry
     * @return array string [$description,$longDescriptionMarkdown]
     */
    protected function getDescriptions(array $entry):array
    {
        $descriptionField = $this->getDescriptionField($entry['id_typeannonce']);

        $renderedDescriptionFromFrield = is_string($descriptionField)
            ? ($entry[$descriptionField] ?? '')
            : $descriptionField->renderStaticIfPermitted($entry);
        $renderedDescriptionHtml = (strpos($renderedDescriptionFromFrield,'<span class="BAZ_texte">') === false)
            ? $renderedDescriptionFromFrield
            : preg_replace('/[\s\S]*<span class="BAZ_texte">([\s\S]*)<\\/span>\\s*<\\/div>\\s*$/','$1',$renderedDescriptionFromFrield);
        $renderedDescriptionHtml = preg_replace('/^   \s*/','',$renderedDescriptionHtml);
        
        $description = trim(strip_tags($renderedDescriptionHtml));

        $renderedDescriptionHtmlCleaned = trim(strip_tags(
            $renderedDescriptionHtml,
            '<br><li><a><p><i><b><h1><h2><h3><h4><h5><h6><figure><img><del><code>'
        ));

        $longDescriptionMarkdown = preg_replace(
            [
                '/<i(?: [^>]*)?><\\/i>/',
                '/<\\/?i(?: [^>]*)?>/','/<\\/?b(?: [^>]*)?>/','/<\\/?del(?: [^>]*)?>/','/<\\/?code(?: [^>]*)?>/','/<\\/?br(?: [^>]*)?>/','/<p(?: [^>]*)?>/','/<\\/p>/',
                '/<h1(?: [^>]*)?>/','/<h2(?: [^>]*)?>/','/<h3(?: [^>]*)?>/','/<h4(?: [^>]*)?>/','/<h5(?: [^>]*)?>/','/<h6(?: [^>]*)?>/',
                '/<\\/h[0-6]>/',
                '/<li(?: [^>]*)?>/','/<\\/li>/',
                '/<img(?: [^>]*)?src="([^"]+)"(?: [^>]*)?alt="([^"]+)"(?: [^>]*)?\\/>/',
                '/<img(?: [^>]*)?alt="([^"]+)"(?: [^>]*)?src="([^"]+)"(?: [^>]*)?\\/>/',
                '/<img(?: [^>]*)?src="([^"]+)"(?: [^>]*)?\\/>/',
                '/<a(?: [^>]*)?href="[^"]+\\/upload.(amp;)?file=[^"]+"(?: [^>]*)?>[^<]*<\\/a>/',
                '/<a(?: [^>]*)?href="([^"]+)(?:\\/iframe)?"(?: [^>]*)?>([^<]+)<\\/a>/',
                '/<a(?: [^>]*)?href="([^"]+)(?:\\/iframe)?"(?: [^>]*)?><\\/a>/'
            ],
            [
                '',
                '*','**','~~','`',"  \r\n",'',"\r\n\r\n",
                "\n# ","\n## ","\n### ","\n#### ","\n##### ","\n###### ",
                "\n",
                "\n - ",'',
                "![$2]($1)",
                "![$1]($2)",
                "![an image]($1)",
                '',
                "[$2]($1)",
                "[$1]($1)"
            ],
            $renderedDescriptionHtmlCleaned
        );
        $longDescriptionMarkdown = trim(strip_tags($longDescriptionMarkdown),"\x00..\x1F");
        $url = $this->wiki->Href('',$entry['id_fiche']);
        $longDescriptionMarkdown .= (empty($longDescriptionMarkdown) ? '' : "  \n")."Source: [$url]($url)";
        $longDescriptionMarkdown = trim(trim($longDescriptionMarkdown),"\x00..\x1F");

        return compact(['description','longDescriptionMarkdown']);
    }

    /**
     * register open agenda uid
     * overwrite it if existing
     * @param string $entryId
     * @param int $uid
     */
    protected function registerUid(string $entryId,int $uid)
    {
        $triples = $this->tripleStore->getAll($entryId,self::TRIPLE_PROPERTY,'','');
        if (empty($triples)){
            $this->tripleStore->create($entryId,self::TRIPLE_PROPERTY,$uid,'','');
        } elseif (count($triples) > 1) {
            for ($i=1; $i < count($triples); $i++) {
                $this->tripleStore->delete($entryId,self::TRIPLE_PROPERTY,$triples[$i]['value'],'','');
            }
            $this->registerUid($entryId,$uid);
        } else {
            $this->tripleStore->update($entryId,self::TRIPLE_PROPERTY,$triples[0]['value'],$uid,'','');
        }
    }

    /**
     * get open agenda uid
     * @param string $entryId
     * @return int (0 if not existing)
     */
    protected function getUid(string $entryId): int
    {
        $value = $this->tripleStore->getOne($entryId,self::TRIPLE_PROPERTY,'','');
        return empty($value) ? 0 : intval($value);
    }

    /**
     * delete all open agenda uid
     * @param string $entryId
     */
    protected function deleteAllUid(string $entryId)
    {
        $this->tripleStore->delete($entryId,self::TRIPLE_PROPERTY,null,'','');
    }

    /**
     * get timings
     * @param array $entry
     * @return array $timings
     */
    protected function getTimings(array $entry): array
    {
        $timings = [];
        try {
            $timingsForMaster = [];
            $firstBeginning = new DateTimeImmutable($entry['bf_date_debut_evenement']);
            try {
                $firstEnd = new DateTimeImmutable($entry['bf_date_fin_evenement']);
                if (strlen($entry['bf_date_fin_evenement']) < 11){
                    // set to midnight of next day
                    $firstEnd = $firstEnd->add(new DateInterval('P1D'))->setTime(0,0);
                }
                if ($firstBeginning->diff($firstEnd)->invert > 0){
                    throw new Exception('End should be after beginning !');
                }
            } catch (Throwable $th) {
                $firstEnd = $firstBeginning->add(new DateInterval('PT1H'));
            }
            $beginPlus24h = $firstBeginning->add(new DateInterval('PT24H'));
            if ($firstEnd->diff($beginPlus24h)->invert === 0){
                // less than 24 h or 24h
                $timingsForMaster[] = [
                    'begin' => $firstBeginning->add(new DateInterval("PT0S")),
                    'end' =>  $firstEnd->add(new DateInterval("PT0S"))
                ];
            } else {
                // first day
                $nextDayMidnight = $beginPlus24h->setTime(0,0);
                $timingsForMaster[] = [
                    'begin' => $firstBeginning->add(new DateInterval("PT0S")),
                    'end' =>  $nextDayMidnight->add(new DateInterval("PT0S"))
                ];

                $currentDayMidnight = $nextDayMidnight;
                $nextDayMidnight = $nextDayMidnight->add(new DateInterval('P1D'))->setTime(0,0);
                while ($firstEnd->diff($nextDayMidnight)->invert > 0) {
                    $timingsForMaster[] = [
                        'begin' => $currentDayMidnight->add(new DateInterval("PT0S")),
                        'end' =>  $nextDayMidnight->add(new DateInterval("PT0S"))
                    ];
                    $currentDayMidnight = $nextDayMidnight;
                    $nextDayMidnight = $nextDayMidnight->add(new DateInterval('P1D'))->setTime(0,0);
                }

                // last day
                $timingsForMaster[] = [
                    'begin' => $currentDayMidnight->add(new DateInterval("PT0S")),
                    'end' =>  $firstEnd->add(new DateInterval("PT0S"))
                ];
            }
            $steps = [0]; // seconds
            if (!empty($entry['bf_date_fin_evenement_data']['isRecurrent'])
                && $entry['bf_date_fin_evenement_data']['isRecurrent'] == "1"){
                // unset cache
                $GLOBALS['_BAZAR_'] = [];
                $linkedEntries = $this->entryManager->search([
                        'formsIds' => [$entry['id_typeannonce']],
                        'queries' => [
                            'bf_date_fin_evenement_data' => "{\"recurrentParentId\":\"{$entry['id_fiche']}\"}"
                        ]
                    ],
                    false, // filter on read Acl
                    false // useGuard 
                );
                if (!empty($linkedEntries)){
                    foreach($linkedEntries as $linkedEntry){
                        if (!empty($linkedEntry['bf_date_debut_evenement'])){
                            try {
                                $currentBegin = new DateTimeImmutable($linkedEntry['bf_date_debut_evenement']);
                                if ($firstBeginning->diff($currentBegin)->invert === 0){
                                    $steps[] = $currentBegin->getTimestamp() - $firstBeginning->getTimestamp();
                                }
                            } catch (Throwable $th) {
                            }
                        }
                    }
                }
            }
            foreach ($steps as $step) {
                foreach ($timingsForMaster as $timing) {
                    $timings[] = [
                        'begin' => ($timing['begin'])->add(new DateInterval("PT{$step}S"))->format('c'),
                        'end' =>  ($timing['end'])->add(new DateInterval("PT{$step}S"))->format('c')
                    ];
                }
            }
        } catch (Throwable $th) {
            trigger_error($th->__toString());
            $timings[] = [
                'begin' => $entry['bf_date_debut_evenement'],
                'end' =>  $entry['bf_date_fin_evenement']
            ];
        }
        return $timings;

    }
}
