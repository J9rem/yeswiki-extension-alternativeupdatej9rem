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
use Throwable;
use YesWiki\Core\Service\ConfigurationService;

class ConfigOpenAgendaService
{
    protected $configurationService;
    protected $openAgendaParams;
    protected $params;

    public function __construct(
        ConfigurationService $configurationService,
        ParameterBagInterface $params
    ) {
        $this->configurationService = $configurationService;
        $this->params = $params;
        $this->openAgendaParams = $params->get('openAgenda');
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
     * get access token
     * @param string $keyName
     * @return array [string $token, int $expiresIn]
     */
    public function getAccessToken(string $keyName): array
    {
        $code = $this->openAgendaParams['privateApiKeys'][$keyName] ?? '';
        if (empty($code)){
            return ['error' => 'Unknown key !'];
        }
        try {
            $data = $this->getRouteApi(
                'https://api.openagenda.com/v2/requestAccessToken',
                'requestAccessToken',
                true,
                [
                    'grant_type' => 'authorization_code',
                    'code' => $code
                ]
            );
        } catch (Throwable $th) {
            return ['error' => $th->getMessage()];
        }
        if (empty($data['access_token']) || empty($data['expires_in'])){
            return ['error' => 'badly formatted response !'];
        }
        return [
            'token' => $data['access_token'],
            'expiresIn' => $data['expires_in']
        ];
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

}
