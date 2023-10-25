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
use YesWiki\Core\Entity\Event;
use YesWiki\Core\Service\ConfigurationService;
use YesWiki\Wiki;

class ConfigOpenAgendaService implements EventSubscriberInterface
{
    protected $configurationService;
    protected $followedFormsIds;
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
        ParameterBagInterface $params,
        Wiki $wiki
    ) {
        $this->configurationService = $configurationService;
        $this->params = $params;
        $this->wiki = $wiki;
        $this->openAgendaParams = $params->get('openAgenda');
        $this->isActivated = ($this->openAgendaParams['isActivated'] ?? false) === true;
        $this->followedFormsIds = array_keys($this->openAgendaParams['associations'] ?? []);
    }

    /**
     * @param Event $event
     */
    public function followEntryCreation($event)
    {
        if ($this->isActivated){
            $entry = $this->getEntry($event);
            if ($this->shouldFollowEntry($entry)){
                $this->createEvent($entry);
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
        $data = $this->getRouteApi(
            'https://api.openagenda.com/v2/requestAccessToken',
            'requestAccessToken',
            true,
            [
                'grant_type' => 'authorization_code',
                'code' => $code
            ]
        );
        if (empty($data['access_token']) || empty($data['expires_in'])){
            throw new Exception('badly formatted response !');
        }
        return [
            'token' => $data['access_token'],
            'expiresIn' => $data['expires_in']
        ];
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
     * @param bool $isPost optionnal
     * @param array|string $postData optionnal
     * @param string $bearer
     * @return mixed $resul
     * @throws Exception
     */
    protected function getRouteApi(string $url, string $type, bool $isPost = false, $postData = [], string $bearer = '')
    {
        if (!empty($bearer)) {
            $headers = [
                "Authorization: Bearer $bearer",
            ];
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, $isPost);
        if ($isPost && !empty($postData) && (is_string($postData) || is_array($postData))) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }
        if (!empty($bearer)) {
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
     * create an event on OpenAgenda
     * @param array $entry
     * @throws Exception
     */
    protected function createEvent(array $entry)
    {
        $association = $this->openAgendaParams['associations'][$entry['id_typeannonce']];

        list('token' => $token,'expiresIn'=> $expiresIn) = 
            $this->getAccessToken($association['key']);
        
        $data = $this->getRouteApi(
            "https://api.openagenda.com/v2/agendas/{$association['id']}/events",
            'createEvent',
            true,
            [
                'access_token' => $token,
                'nonce' => random_int(0,pow(2, 31)),
                'data' => $this->prepareEntryData($entry,$this->generateOpenAgendaUid($association['id'],$entry['id_fiche']))
            ]
        );
        if (!empty($data['message'])){
            trigger_error("openAgendaCreate: {$data['message']}");
        }
    }

    /**
     * prepare Entry data
     * @param array $entry
     * @param int $uid
     * @return array
     */
    protected function prepareEntryData(array $entry,int $uid): array
    {
        $entryLang = $GLOBALS['prefered_language'] ?? 'fr';
        $entryTitle = $entry['bf_titre'] ?? $entry['id_fiche'];
        $data = [
            'uid' => $uid,
            'slug' => URLify::slug($entryTitle),
            'title' => [
                $entryLang => substr(strip_tags($entryTitle),0,140)
            ],
            'state' => 2, // published, 1= not published controller, 0 = to control
            'featured' => false, // to display in head
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
        return $this->prepareForPost($data);
    }

    /**
     * prepare data for POST
     * @param array $data
     * @param bool $isTop
     * @return array
     */
    protected function prepareForPost(array $data, bool $isTop = true): array
    {
        $newData = [];
        foreach ($data as $key => $value) {
            if (is_scalar($value)){
                $newData[$isTop ? $key : "[$key]"] = strval($value);
            } elseif (is_array($value)){
                $tmp = $this->prepareForPost($value,false);
                foreach($tmp as $key2 => $value2){
                    $newKeyName = $isTop
                        ? "$key$key2"
                        : "[$key]$key2";
                    $newData[$newKeyName] = $value2;
                }
            }
        }
        return $newData;
    }

    /**
     * generate OpenAgenda uid from entryId and agenda uid
     * @param int $agendaUid
     * @param string $entryId
     * @return int $uid
     */
    protected function generateOpenAgendaUid(int $agendaUid,string $entryId): int
    {
        return intval(sprintf('%u',crc32("$agendaUid$entryId")));
    }
}
