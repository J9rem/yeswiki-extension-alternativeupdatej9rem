<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-local-cache
 */

namespace YesWiki\Alternativeupdatej9rem\Service;

use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;
use YesWiki\Alternativeupdatej9rem\Service\PhpFileCache;
use YesWiki\Security\Controller\SecurityController;
use YesWiki\Core\Controller\AuthController;
use YesWiki\Core\Entity\Event;
use YesWiki\Core\Service\ConfigurationService;
use YesWiki\Core\Service\ConsoleService;
use YesWiki\Core\Service\DbService;
use YesWiki\Core\Service\TripleStore;
use YesWiki\Wiki;

class CacheService implements EventSubscriberInterface
{
    public const DB_CACHE_DIRECTORY = 'cache';
    public const TRIPLE_KEY_FOR_FORM_ID = 'https://yeswiki.net/cache-timestamp-form-id';
    public const CONFIG_FILE = 'wakka.config.php';

    protected $authController;
    protected $configurationService;
    protected $consoleService;
    protected $dbService;
    protected $params;
    protected $phpFileCacheArray;
    protected $dbPhpFileCacheService;
    protected $dbDataPhpFileCacheService;
    protected $securityController;
    protected $tripleStore;
    protected $wiki;

    public static function getSubscribedEvents()
    {
        return [
            'entry.created' => 'updateFormIdTimestampFromEvent',
            'entry.updated' => 'updateFormIdTimestampFromEvent',
            'entry.deleted' => 'updateFormIdTimestampFromEvent'
        ];
    }

    public function __construct(
        AuthController $authController,
        ConfigurationService $configurationService,
        ConsoleService $consoleService,
        DbService $dbService,
        ParameterBagInterface $params,
        SecurityController $securityController,
        TripleStore $tripleStore,
        Wiki $wiki
    ) {
        $this->authController = $authController;
        $this->configurationService = $configurationService;
        $this->consoleService = $consoleService;
        $this->dbService = $dbService;
        $this->params = $params;
        $this->phpFileCacheArray = [];
        if (!is_dir(self::DB_CACHE_DIRECTORY)){
            mkdir(self::DB_CACHE_DIRECTORY,0777,true);
        }
        $this->securityController = $securityController;
        $this->tripleStore = $tripleStore;
        $this->wiki = $wiki;
    }

    /**
     * update formid timestamp from event
     * @param $event
     */
    public function updateFormIdTimestampFromEvent($event)
    {
        $entry = $this->getEntry($event);
        if (!empty($entry['id_typeannonce'])){
            $this->updateFormIdTimestamp(strval($entry['id_typeannonce']));
        }
    }

    /**
     * update formid timestamp 
     * @param string $id
     */
    public function updateFormIdTimestamp(string $id)
    {
        if (!$this->securityController->isWikiHibernated()) {
            $value = $this->getFormIdTimestamp($id);
            if (empty($value)){
                $this->tripleStore->create($id,self::TRIPLE_KEY_FOR_FORM_ID,time(),'','');
            } else {
                $this->tripleStore->update($id,self::TRIPLE_KEY_FOR_FORM_ID,$value,time(),'','');
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
     * lazy load phpFileCache
     * @param string $folderName
     * @param bool $getDataCache
     * @return PhpFileCache
     * @throws Exception
     */
    protected function getPhpFileCache(string $folderName, bool $getDataCache = false): PhpFileCache
    {
        $key = $getDataCache ? 'data' : 'cache';
        if (empty($this->phpFileCacheArray[$folderName][$key])){
            if (empty($folderName)){
                throw new Exception('folderName could not be empty !');
            }
            if (strpos($folderName,'/') !== false){
                throw new Exception('folderName could not contain "/" !');
            }
            if (empty($this->phpFileCacheArray[$folderName])){
                $this->phpFileCacheArray[$folderName] = [];
            }
            $directory = self::DB_CACHE_DIRECTORY;
            if (substr(self::DB_CACHE_DIRECTORY) != '/'){
                $directory .= '/';
            }
            $directory .= $folderName;
            if (!is_dir($directory)){
                mkdir($directory,0777,true);
            }
            $htaccessFilePath = $directory.'/.htaccess';
            if (!is_file($htaccessFilePath)){
                file_put_contents($htaccessFilePath,"DENY FROM ALL\n");
            }
            $this->phpFileCacheArray[$folderName][$key] = $getDataCache
                ? new PhpFileCache($directory)
                : new PhpFileCache($directory,'.docdatacache.php')
            ;
        }
        return $this->phpFileCacheArray[$folderName][$key];
    }

    /**
     * get SQL request from cache
     * @param string $folderName
     * @param string $key
     * @param callable $action
     * @param array $formsIdsToFollow
     * @param bool $followWakkaConfigTimestamp
     * @param bool $disconnectDB
     * @param bool $gzResult
     * @return array [$eTag,$data]
     * @throws Exception
     */
    public function getFromCache(
        string $folderName,
        string $key,
        $action,
        array $formsIdsToFollow = [],
        bool $followWakkaConfigTimestamp = false,
        bool $disconnectDB = false,
        bool $gzResult = false
    ): ?array
    {
        $localId = $this->getLocalCacheId($key);
        if (empty($localId)){
           throw new Exception('It is not possible to obtain an local id from key !');
        }
        $cachedData = null;
        $cachedResult = [];
        $toSave = true;
        $eTag = '';
        try{
            // get Cache if possible
            $dataForCacheService = $this->getPhpFileCache($folderName,true);
            $cacheService = $this->getPhpFileCache($folderName,false);
            
            $cachedData = $dataForCacheService->fetch($localId);
            if (!empty($cachedData)
                && !$this->needRefresh($cachedData,$formsIdsToFollow,$followWakkaConfigTimestamp)){
                $data = $cacheService->fetch($localId);
                if ($data !==null){
                    if ($disconnectDB){
                        $this->disconnectDb();
                    }
                    $eTag = $this->generateEtag($localId, $cacheService);
                    if ($gzResult && function_exists('gzinflate') && function_exists('gzdeflate')){
                        $data = gzinflate($data);
                    }
                    return compact(['eTag','data']);
                }
            }

            // if cache to refresh
            if (empty($cachedData) || !$this->isAlreadyRunning($cachedData)){
                $cachedData = $this->setAlreadyRunning($localId,$dataForCacheService,true);
            }
        } catch (Throwble $th){
            $toSave = false;
        }

        // TODO manage async reload if running from more than 2 minutes
        if (!is_callable($action)){
            throw new Exception('Action is not callable !');
        }
        $data = $action();

        if ($toSave){
            $dataToSave = $data;
            if ($gzResult && function_exists('gzinflate') && function_exists('gzdeflate')){
                $dataToSave = gzdeflate($dataToSave);
            }
            $cacheService->save($localId,$dataToSave);
            $this->saveCachedData($localId,$dataForCacheService,$formsIdsToFollow,$followWakkaConfigTimestamp);
            $eTag = $this->generateEtag($localId, $cacheService);
        }
        return compact(['eTag','data']);
    }

    /**
     * conpare data to current resources
     * @param string $data
     * @param array $formsIdsToFollow
     * @param bool $followWakkaConfigTimestamp
     * @return bool
     */
    protected function needRefresh(string $data, array $formsIdsToFollow, bool $followWakkaConfigTimestamp): bool
    {
        if (!empty($formsIdsToFollow)){
            foreach ($formsIdsToFollow as $id) {
                if (empty($data['forms'][$id])){
                    return true;
                }
                $savedFormIdTimeStamp = $this->getFormIdTimestamp(strval($id));
                if (empty($savedFormIdTimeStamp) || $savedFormIdTimeStamp != $data['forms'][$id]){
                    return true;
                }
            }
        }
        if (empty($data['acls']['read']) || $data['acls']['read'] != $this->params->get('default_read_acl')){
            return true;
        }
        if (empty($data['acls']['write']) || $data['acls']['write'] != $this->params->get('default_write_acl')){
            return true;
        }
        if ($followWakkaConfigTimestamp
                && (
                    empty($data['configFileTime'])
                    || $data['configFileTime'] != filemtime(self::CONFIG_FILE)
                )
            ){
            return true;
        }
        return false;
    }

    /**
     * get saved timestamp for formid
     * @param string $id
     * @return string
     */
    protected function getFormIdTimestamp(string $id): string
    {
        $value = $this->tripleStore->getOne(
            $id,
            self::TRIPLE_KEY_FOR_FORM_ID,
            '',
            ''
        );
        return empty($value) ? '' : $value;
    }

    /**
     * disonnect DB
     */
    protected function disconnectDb()
    {
        $link = $this->dbService->getLink();
        mysqli_close($link);
    }

    /**
     * generate ETAG
     * @param string $localId
     * @param PhPFileCache $cacheService
     * @return string
     */
    protected function generateEtag(string $localId, PhPFileCache $cacheService): string
    {
        return md5($localId.$cacheService->publicGetFiletime($localId));
    }

    /**
     * get ETAG request from cache
     * @param string $folderName
     * @param string $key
     * @param array $formsIdsToFollow
     * @param bool $followWakkaConfigTimestamp
     * @return string
     */
    public function getETAGFromCache(
        string $folderName,
        string $key,
        array $formsIdsToFollow = [],
        bool $followWakkaConfigTimestamp = false,
    ): string
    {
        $localId = $this->getLocalCacheId($key);
        if (empty($localId)){
            return '';
        }
        $cacheService = $this->getPhpFileCache($folderName,false);
        return $this->generateEtag($localId, $cacheService);
    }

    /**
     * generate local Cache Id from $sqlRequest
     * and user
     * @param string $sqlRequest
     * @return string
     */
    protected function getLocalCacheId(string $sqlRequest): string
    {
        // get user
        $userName = $this->authController->getLoggedUserName();
        $userName = (empty($userName) || !is_string($userName)) ? '' : $userName;

        // prepend SQL
        return $userName.$sqlRequest;
    }

    /**
     * check if running
     * @param array $data
     * @return bool
     */
    protected function isAlreadyRunning(array $data): bool
    {
        return !empty($data['running']) && $data['running'] === true;
    }

    /**
     * @param string $localId
     * @param PhpFileCache $dataForCacheService
     * @param bool $running
     * @return array
     */
    protected function setAlreadyRunning(string $localId,PhpFileCache $dataForCacheService, bool $running): array
    {
        $data = $dataForCacheService->fetch($localId);
        if (empty($data)){
            $data = [];
        }
        $data['running'] = $running;
        $dataForCacheService->save($localId,$data);
        return $data;
    }

    /**
     * save new data in data cache
     * @param string $localId
     * @param PhpFileCache $dataForCacheService
     * @param array $formsIdsToFollow 
     * @param bool $followWakkaConfigTimestamp 
     */
    protected function saveCachedData(
        string $localId,
        PhpFileCache $dataForCacheService,
        array $formsIdsToFollow,
        bool $followWakkaConfigTimestamp)
    {
        $data = [
            'acls' => []
        ];
        if (!empty($formsIdsToFollow)){
            foreach ($formsIdsToFollow as $id) {
                $savedFormIdTimeStamp = $this->getFormIdTimestamp(strval($id));
                if (empty($savedFormIdTimeStamp)){
                    $this->updateFormIdTimestamp(strval($id));
                    $savedFormIdTimeStamp = $this->getFormIdTimestamp(strval($id));
                }
                if (!empty($savedFormIdTimeStamp)){
                    if (empty($data['forms'])){
                        $data['forms'] = [];
                    }
                    $data['forms'][strval($id)] = $savedFormIdTimeStamp;
                }
            }
        }
        $data['acls']['read'] = $this->params->get('default_read_acl');
        $data['acls']['write'] = $this->params->get('default_write_acl');
        if ($followWakkaConfigTimestamp){
            $fileTime = filemtime(self::CONFIG_FILE);
            if (!empty($fileTime)){
                $data['configFileTime'] = $fileTime;
            }
        }
        $dataForCacheService->save($localId,$data);
    }
}