<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace YesWiki\Alternativeupdatej9rem\Controller;

use AutoUpdate\Package;
use AutoUpdate\Repository;
// Feature UUID : auj9-local-cache
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\Exception\TokenNotFoundException;
use YesWiki\Alternativeupdatej9rem\Controller\BazarSendMailController; // Feature UUID : auj9-bazar-list-send-mail-dynamic
use YesWiki\Alternativeupdatej9rem\Controller\ConfigOpenAgendaController ; // Feature UUID : auj9-open-agenda-connect
use YesWiki\Alternativeupdatej9rem\Controller\PageController; // Feature UUID : auj9-fix-page-controller
use YesWiki\Alternativeupdatej9rem\Service\AutoUpdateService;
use YesWiki\Alternativeupdatej9rem\Service\CacheService; // Feature UUID : auj9-local-cache
use YesWiki\Bazar\Controller\ApiController as BazarApiController; // Feature UUID : auj9-local-cache
use YesWiki\Core\ApiResponse;
use YesWiki\Core\Controller\AuthController;
use YesWiki\Core\Controller\CsrfTokenController;
use YesWiki\Core\Service\DbService; // Feature UUID : auj9-fix-page-controller
use YesWiki\Core\Service\PageManager; // Feature UUID : auj9-fix-page-controller
use YesWiki\Core\Service\TripleStore;
use YesWiki\Core\Service\UserManager;
use YesWiki\Core\YesWikiController;
use YesWiki\Groupmanagement\Controller\ApiController as GroupmanagementApiController; # Feature UUID : auj9-local-cache
use YesWiki\Security\Controller\SecurityController;

class ApiController extends YesWikiController
{
    public const TOKEN_ID = "POST /api/alternativeupdatej9rem";

    /**
     * @Route("/api/alternativeupdatej9rem", methods={"POST"}, options={"acl":{"public", "@admins"}})
     * Feature UUID : auj9-autoupdate-system
     */
    public function manageAction()
    {
        $action = filter_input(INPUT_POST, 'action', FILTER_UNSAFE_RAW);
        $action = in_array($action, [false,null], true) ? "" : htmlspecialchars(strip_tags($action));
        $password = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW);
        $password = in_array($password, [false,null], true) ? "" : $password;
        switch ($action) {
            case 'getToken':
                $userManager = $this->wiki->services->get(UserManager::class);
                if ($this->wiki->services->has(AuthController::class)) {
                    $authController = $this->wiki->services->get(AuthController::class);
                    $user = $authController->getLoggedUser();
                    if (!empty($user)) {
                        $user = $userManager->getOneByName($user['name']);
                    }
                    if (empty($user)) {
                        return new ApiResponse(
                            ['error' => "no user","wrongPassword"=>false],
                            Response::HTTP_BAD_REQUEST
                        );
                    } elseif ($authController->checkPassword($password, $user)) {
                        return new ApiResponse(
                            ['token' => $this->wiki->services->get(CsrfTokenManager::class)->refreshToken(self::TOKEN_ID)->getValue()],
                            Response::HTTP_OK
                        );
                    } else {
                        return new ApiResponse(
                            ['error' => "wrong password","wrongPassword"=>true],
                            Response::HTTP_BAD_REQUEST
                        );
                    }
                } else {
                    $user = $userManager->getLoggedUser();
                    if (!empty($user)) {
                        $user = $userManager->getOneByName($user['name']);
                    }
                    if (empty($user)) {
                        return new ApiResponse(
                            ['error' => "no user","wrongPassword"=>false],
                            Response::HTTP_BAD_REQUEST
                        );
                    } elseif ($user['password'] === md5($password)) {
                        return new ApiResponse(
                            ['token' => $this->wiki->services->get(CsrfTokenManager::class)->refreshToken(self::TOKEN_ID)->getValue()],
                            Response::HTTP_OK
                        );
                    } else {
                        return new ApiResponse(
                            ['error' => "wrong password","wrongPassword"=>true],
                            Response::HTTP_BAD_REQUEST
                        );
                    }
                }
                // no break
            case 'getPackagesPaths':
                return $this->executeInSecureContext(function ($autoUpdateService) use ($action) {
                    $version = filter_input(INPUT_POST, 'version', FILTER_UNSAFE_RAW);
                    $version = in_array($version, [false,null], true) ? "" : htmlspecialchars(strip_tags($version));
                    if (empty($version)) {
                        return new ApiResponse(
                            ['error' => "empty 'version' in POST for action '$action'"],
                            Response::HTTP_BAD_REQUEST
                        );
                    }
                    
                    $repository = $autoUpdateService->initRepository($version);
                    $addresses = $repository->getAlternativeAddresses();
                    foreach (['getAlternativeToolsPackages','getAlternativeThemesPackages'] as $functionName) {
                        $alternativePackages = $repository->{$functionName}();
                        foreach ($alternativePackages as $key => $data) {
                            // unset($addresses[$key]);
                        }
                    }
                    return new ApiResponse(
                        array_map(function ($url) {
                            return "{$url}packages.json";
                        }, array_values($addresses)),
                        Response::HTTP_OK
                    );
                });
            case 'updatePackagesInfos':
                return $this->executeInSecureContext(function ($autoUpdateService) use ($action) {
                    foreach (['packages','versions'] as $name) {
                        if (empty($_POST[$name])) {
                            return new ApiResponse(
                                ['error' => "empty '$name' in POST for action '$action'"],
                                Response::HTTP_BAD_REQUEST
                            );
                        }
                        if (!is_array($_POST[$name])) {
                            return new ApiResponse(
                                ['error' => "'$name' is not an array in POST for action '$action'"],
                                Response::HTTP_BAD_REQUEST
                            );
                        }
                    }
                    $data = [
                        'isHibernated' => $this->wiki->services->get(SecurityController::class)->isWikiHibernated(),
                        'versions' => []
                    ];
                    foreach ($_POST['versions'] as $version) {
                        if (!empty($version) && is_string($version)) {
                            $repository = $autoUpdateService->initRepository($version, $_POST['packages']);
                            
                            $repos = $autoUpdateService->getReposForAlternative($repository,function($package){
                                return $this->toArray($package);
                            });

                            $data['versions'][$version] = [
                                'repos' => $repos,
                            ];
                            foreach (['localTools' => 'getLocalToolsPackages','localThemes' => 'getLocalThemesPackages'] as $key => $functionName) {
                                $data[$key][$version] = [];
                                foreach ($repository->{$functionName}() as $packageName => $package) {
                                    $data[$key][$version][$package->name] = json_decode(json_encode($this->toArray($package)), true);
                                    if (empty($data[$key][$version][$package->name])) {
                                        unset($data[$key][$version][$package->name]);
                                    }
                                }
                            }
                        }
                    }
                    foreach (['localTools','localThemes'] as $key) {
                        $init = true;
                        foreach ($data[$key] as $version => $versionExts) {
                            if ($init) {
                                $exts = $versionExts;
                                $init = false;
                            } else {
                                foreach ($exts as $extName => $ext) {
                                    if (!in_array($extName, array_keys($data[$key][$version]))) {
                                        unset($exts[$extName]);
                                    }
                                }
                            }
                        }
                        $data[$key] = $exts;
                    }
                    
                    
                    return new ApiResponse(
                        $data,
                        Response::HTTP_OK
                    );
                });
            case 'install':
                return $this->executeInSecureContext(function ($autoUpdateService) use ($action) {
                    foreach (['version','packageName','md5'] as $name) {
                        $var = filter_input(INPUT_POST, $name, FILTER_UNSAFE_RAW);
                        $var = in_array($var, [false,null], true) ? "" : htmlspecialchars(strip_tags($var));
                        if (empty($var)) {
                            return new ApiResponse(
                                ['error' => "empty '$name' in POST for action '$action'"],
                                Response::HTTP_BAD_REQUEST
                            );
                        }
                        extract([$name=>$var]);
                        unset($var);
                    }
                    foreach (['file','packages'] as $name) {
                        $var = filter_input(INPUT_POST, $name, FILTER_UNSAFE_RAW);
                        $var = in_array($var, [false,null], true) ? "" : $var;
                        if (empty($var)) {
                            return new ApiResponse(
                                ['error' => "empty '$name' in POST for action '$action'"],
                                Response::HTTP_BAD_REQUEST
                            );
                        }
                        extract([$name=>$var]);
                        unset($var);
                    }
                    $file = base64_decode($file);
                    if (empty($file)) {
                        return new ApiResponse(
                            ['error' => "error decoding file in POST for action '$action'"],
                            Response::HTTP_BAD_REQUEST
                        );
                    }
                    $packagesRaw = $packages;
                    $packages = json_decode($packagesRaw, true);
                    if (empty($packages) || !is_array($packages)) {
                        return new ApiResponse(
                            ['error' => "error decoding packages '$packagesRaw' in POST for action '$action'"],
                            Response::HTTP_BAD_REQUEST
                        );
                    }
                    if ($this->wiki->services->get(SecurityController::class)->isWikiHibernated()) {
                        return new ApiResponse(
                            ['error' => _t('WIKI_IN_HIBERNATION'),'hibernate'=> true],
                            Response::HTTP_INTERNAL_SERVER_ERROR
                        );
                    }

                    $destPath = tempnam('cache', 'tmp_to_delete_zip_');
                    file_put_contents($destPath, $file);
                    $destPathMD5 = tempnam('cache', 'tmp_to_delete_md5_');
                    file_put_contents($destPathMD5, md5_file($destPath));
                    $repository = $autoUpdateService->initRepository($version, $packages);

                    $messages = $autoUpdateService->upgradeAlternativeIfNeeded($repository, $packageName, $destPath, $destPathMD5);

                    if (is_null($messages)) {
                        return new ApiResponse(
                            ['error' => "'$packageName' has not been installed in POST for action '$action'",'installed'=>false],
                            Response::HTTP_BAD_REQUEST
                        );
                    } else {
                        return new ApiResponse(
                            ['messages'=>$this->render("@autoupdate/update.twig", [
                                'messages' => $messages,
                                'baseUrl' => $autoUpdateService->baseUrl(),
                            ])],
                            Response::HTTP_OK
                        );
                    }
                });
            
            case 'delete':
                return $this->executeInSecureContext(function ($autoUpdateService) use ($action) {
                    foreach (['packageName'] as $name) {
                        $var = filter_input(INPUT_POST, $name, FILTER_UNSAFE_RAW);
                        $var = in_array($var, [false,null], true) ? "" : htmlspecialchars(strip_tags($var));
                        if (empty($var)) {
                            return new ApiResponse(
                                ['error' => "empty '$name' in POST for action '$action'"],
                                Response::HTTP_BAD_REQUEST
                            );
                        }
                        extract([$name=>$var]);
                        unset($var);
                    }
                    foreach (['packages'] as $name) {
                        $var = $_POST[$name] ?? null;
                        if (empty($var) || !is_array($var)) {
                            return new ApiResponse(
                                ['error' => "empty '$name' or not array in POST for action '$action'"],
                                Response::HTTP_BAD_REQUEST
                            );
                        }
                        extract([$name=>$var]);
                        unset($var);
                    }
                    if ($this->wiki->services->get(SecurityController::class)->isWikiHibernated()) {
                        return new ApiResponse(
                            ['error' => _t('WIKI_IN_HIBERNATION'),'hibernate'=> true],
                            Response::HTTP_INTERNAL_SERVER_ERROR
                        );
                    }

                    $repository = $autoUpdateService->initRepository("", $packages);

                    $messages = $autoUpdateService->deleteAlternativeOrLocal($repository, $packageName);

                    if (is_null($messages)) {
                        return new ApiResponse(
                            ['error' => "'$packageName' has not been deleted in POST for action '$action'"],
                            Response::HTTP_BAD_REQUEST
                        );
                    } else {
                        return new ApiResponse(
                            ['messages'=>$this->render("@autoupdate/update.twig", [
                                'messages' => $messages,
                                'baseUrl' => $autoUpdateService->baseUrl(),
                            ])],
                            Response::HTTP_OK
                        );
                    }
                });
            case 'activation':
                return $this->executeInSecureContext(function ($autoUpdateService) use ($action) {
                    foreach (['packageName','activation'] as $name) {
                        $var = filter_input(INPUT_POST, $name, FILTER_UNSAFE_RAW);
                        $var = in_array($var, [false,null], true) ? "" : htmlspecialchars(strip_tags($var));
                        if (empty($var)) {
                            return new ApiResponse(
                                ['error' => "empty '$name' in POST for action '$action'"],
                                Response::HTTP_BAD_REQUEST
                            );
                        }
                        extract([$name=>$var]);
                        unset($var);
                    }
                    foreach (['packages'] as $name) {
                        $var = $_POST[$name] ?? null;
                        if (empty($var) || !is_array($var)) {
                            return new ApiResponse(
                                ['error' => "empty '$name' or not array in POST for action '$action'"],
                                Response::HTTP_BAD_REQUEST
                            );
                        }
                        extract([$name=>$var]);
                        unset($var);
                    }
                    if ($this->wiki->services->get(SecurityController::class)->isWikiHibernated()) {
                        return new ApiResponse(
                            ['error' => _t('WIKI_IN_HIBERNATION'),'hibernate'=> true],
                            Response::HTTP_INTERNAL_SERVER_ERROR
                        );
                    }
                    $activation = in_array($activation,[1,"1",true,"true"],true);

                    $repository = $autoUpdateService->initRepository("", $packages);

                    if($autoUpdateService->activationLocal($repository, $packageName,$activation)){
                        return new ApiResponse(
                            [],
                            Response::HTTP_OK
                        );
                    } else {
                        return new ApiResponse(
                            ['error' => "'$packageName' has not been ".($activation ? 'activated' : 'deactivated')." in POST for action '$action'"],
                            Response::HTTP_BAD_REQUEST
                        );
                    }
                });
            default:
                return new ApiResponse(
                    ['error' => "Not supported action : $action"],
                    Response::HTTP_BAD_REQUEST
                );
                break;
        }
    }

    /**
     * Feature UUID : auj9-autoupdate-system
     */
    private function executeInSecureContext($callback): ApiResponse
    {
        $autoUpdateService = $this->getService(AutoUpdateService::class);
        $csrfTokenManager = $this->wiki->services->get(CsrfTokenManager::class);
                
        try {
            $inputToken = filter_input(INPUT_POST, 'token', FILTER_UNSAFE_RAW);
            $inputToken = in_array($inputToken, [false,null], true) ? $inputToken : htmlspecialchars(strip_tags($inputToken));
            
            if (is_null($inputToken) || $inputToken === false) {
                throw new TokenNotFoundException(_t('NO_CSRF_TOKEN_ERROR'));
            }
            $token = new CsrfToken(self::TOKEN_ID, $inputToken);
            if (!$csrfTokenManager->isTokenValid($token)) {
                throw new TokenNotFoundException(_t('CSRF_TOKEN_FAIL_ERROR'));
            }
            // check if activated
            if (!$autoUpdateService->isActivated()) {
                return new ApiResponse(
                    ['error' => "Alternative update not activated"],
                    Response::HTTP_BAD_REQUEST
                );
            }
            return $callback($autoUpdateService);
        } catch (TokenNotFoundException $th) {
            return new ApiResponse(
                ['error' => $th->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Feature UUID : auj9-autoupdate-system
     */
    protected function toArray(Package $package): array
    {
        return [
            'name' => $package->name,
            'release' => strval($package->release),
            'localRelease' => strval($package->localRelease),
            'installed' => $package->installed,
            'updateAvailable' => $package->updateAvailable,
            'updateLink' => $package->updateLink,
            'deleteLink' => $package->deleteLink ?? null,
            'description' => $package->description,
            'documentation' => $package->documentation,
            'isActive' => method_exists($package, 'isActive') ? $package->isActive() : null,
            'isTheme' => method_exists($package, 'isTheme') ? $package->isTheme() : null,
        ];
    }

    

    /**
     * @Route("/api/alternativeupdatej9rem/set-edit-entry-partial-params/{resource}/{id}/{fields}", methods={"POST"}, options={"acl":{"public", "@admins"}})
     * Feature UUID : auj9-editentrypartial-action
     */
    public function setEditEntryPartialParams($resource,$id,$fields)
    {
        $this->wiki->services->get(CsrfTokenController::class)->checkToken('admin-token', 'POST', 'anti-csrf-token',true);
        if ($this->wiki->services->get(SecurityController::class)->isWikiHibernated()) {
            return new ApiResponse(
                ['error' => _t('WIKI_IN_HIBERNATION'),'hibernate'=> true],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
        if (empty($resource) || empty($id) || empty($fields)) {
            return new ApiResponse(
                ['error' => 'parameters should not be empty'],
                Response::HTTP_BAD_REQUESTHTTP_BAD_REQUEST
            );
        }

        $tripleStore = $this->wiki->services->get(TripleStore::class);

        // find previous triples
        $previousTriples = $tripleStore->getAll($resource,'https://yeswiki.net/triple/EditEntryPartialParams','','');
        if (!empty($previousTriples)){
            if (count($previousTriples) > 1){
                // delete duplicate
                for ($i=1; $i <= count($previousTriples); $i++) { 
                    $tripleStore->delete(
                        $resource,
                        'https://yeswiki.net/triple/EditEntryPartialParams',
                        null,
                        '',
                        '',
                        " `id` = '{$previousTriples[$i]['id']}'"
                    );
                }
            }
            $tripleStore->update(
                $resource,
                'https://yeswiki.net/triple/EditEntryPartialParams',
                $previousTriples[0]['value'],
                $this->formatEditEntryPartialValue($id,$fields),
                '',
                ''
            );
        } else {
            $tripleStore->create(
                $resource,
                'https://yeswiki.net/triple/EditEntryPartialParams',
                $this->formatEditEntryPartialValue($id,$fields),
                '',
                ''
            );
        }
        $triple = $tripleStore->getOne(
            $resource,
            'https://yeswiki.net/triple/EditEntryPartialParams',
            '',
            ''
        );
        $sha1 = empty($triple) ? '' : (json_decode($triple,true)['sha1'] ?? '');
        return new ApiResponse(
            ['sha1' => $sha1],
            Response::HTTP_OK
        );
    }

    /**
     * Feature UUID : auj9-editentrypartial-action
     */
    protected function formatEditEntryPartialValue($id,$fields): string
    {
        return json_encode([
            'sha1' => sha1("$id-$fields")
        ]);
    }

    /**
     * @Route("/api/alternativeupdatej9rem/getToken", methods={"POST"}, options={"acl":{"public", "@admins"}})
     * Feature UUID : auj9-editentrypartial-action
     */
    public function getToken()
    {
        return new ApiResponse(
            ['token' => $this->wiki->services->get(CsrfTokenManager::class)->refreshToken('admin-token')->getValue()],
            Response::HTTP_OK
        );
    }

    /**
     * @Route("/api/entries/bazarlist", methods={"GET"}, options={"acl":{"public"}},priority=5)
     * Feature UUID : auj9-local-cache
     */
    public function getBazarListData()
    {
        $params = $this->getService(ParameterBagInterface::class);
        $bazarApiController = $this->wiki->services->has(GroupmanagementApiController::class)
            ? $this->getService(GroupmanagementApiController::class)
            : $this->getService(BazarApiController::class);
        $localCacheParam = $params->get('localCache');
        if (!empty($_GET) & !empty($localCacheParam['activated']) && in_array($localCacheParam['activated'],[1,'1',true,'true'])){
            $cacheService = $this->getService(CacheService::class);
            $fomsIds = [];
            if (!empty($_GET['idtypeannonce']) && is_array($_GET['idtypeannonce'])){
                $fomsIds = $_GET['idtypeannonce'];
            }

            list('data'=>$data,'eTag'=>$eTag) = $cacheService->getFromCache(
                'bazarlist',
                json_encode($_GET),
                function() use ($bazarApiController){
                    $response = $bazarApiController->getBazarListData();
                    return json_decode($response->getContent(),true);
                },
                $fomsIds,
                false, // $followWakkaConfigTimestamp
                true, // $disconnectDB
                true // $gzResult
            );
            $headers = [];
            if (!empty($eTag)){
                // configured BUT $.getJSON does not sent If-Not-Match header
                header_remove('Cache-Control');
                header_remove('Expires');
                header_remove('Pragma');
                $headers = [
                    'ETag'=>"W/\"$eTag\"",
                    'Cache-Control' => 'no-cache',
                ];
            }
            return new ApiResponse(
                is_array($data) ? $data : [],
                Response::HTTP_OK,
                $headers
            );
        } else {
            return $bazarApiController->getBazarListData();
        }
    }

    /**
     * @Route("/api/pages/{tag}/delete",methods={"POST"},options={"acl":{"public","+"}},priority=5)
     * Feature UUID : auj9-fix-page-controller
     */
    public function deletePageByGetMethod($tag)
    {
        $result = [];
        $code = Response::HTTP_INTERNAL_SERVER_ERROR;
        try {
            $csrfTokenController = $this->wiki->services->get(CsrfTokenController::class);
            $csrfTokenController->checkToken('main', 'POST', 'csrfToken',false);
        } catch (TokenNotFoundException $th) {
            $code = Response::HTTP_UNAUTHORIZED;
            $result = [
                'notDeleted' => [$tag],
                'error' => $th->getMessage()
            ];
        } catch (Throwable $th) {
            $code = Response::HTTP_INTERNAL_SERVER_ERROR;
            $result = [
                'notDeleted' => [$tag],
                'error' => $th->getMessage()
            ];
        }
        return (empty($result))
            ? $this->deletePage($tag)
            : new ApiResponse($result, $code);
    }

    /**
     * @Route("/api/pages/{tag}",methods={"DELETE"},options={"acl":{"public","+"}},priority=5)
     * Feature UUID : auj9-fix-page-controller
     */
    public function deletePage($tag)
    {
        $pageManager = $this->getService(PageManager::class);
        $pageController = $this->getService(PageController::class);
        $dbService = $this->getService(DbService::class);

        $result = [
            'notDeleted' => [$tag]
        ];
        $code = Response::HTTP_INTERNAL_SERVER_ERROR;
        try {
            $page = $pageManager->getOne($tag, null, false);
            if (empty($page)) {
                $code = Response::HTTP_NOT_FOUND;
            } else {
                $tag = isset($page['tag']) ? $page['tag'] : $tag;
                $result['notDeleted'] = [$tag];
                if ($this->wiki->UserIsOwner($tag) || $this->wiki->UserIsAdmin()) {
                    if (!$pageManager->isOrphaned($tag)) {
                        $dbService->query("DELETE FROM {$dbService->prefixTable('links')} WHERE to_tag = '$tag'");
                    }
                    $done = $pageController->delete($tag);
                    if (!$done || !empty($pageManager->getOne($tag, null, false))) {
                        $code = Response::HTTP_INTERNAL_SERVER_ERROR;
                    } else {
                        $result['deleted'] = [$tag];
                        unset($result['notDeleted']);
                        $code = Response::HTTP_OK;
                    }
                } else {
                    $code = Response::HTTP_UNAUTHORIZED;
                }
            }
        } catch (Throwable $th) {
            try {
                $page = $pageManager->getOne($tag, null, false);
                $result['error'] = $th->getMessage();
                if (!empty($page)) {
                    $code = Response::HTTP_INTERNAL_SERVER_ERROR;
                } else {
                    $code = Response::HTTP_OK;
                    unset($result['notDeleted']);
                    $result['deleted'] = [$tag];
                }
            } catch (Throwable $th) {
                $code = Response::HTTP_INTERNAL_SERVER_ERROR;
                $result['error'] = $th->getMessage();
            }
        }

        return new ApiResponse($result, $code);
    }

    /**
     * @Route("/api/auj9/send-mail/preview", methods={"POST"},options={"acl":{"public","+"}})
     * Feature UUID : auj9-bazar-list-send-mail-dynamic
     */
    public function previewEmail()
    {
        return $this->getService(BazarSendMailController::class)->previewEmail();
    }

    
    /**
     * @Route("/api/auj9/send-mail/sendmail", methods={"POST"},options={"acl":{"public","+"}})
     * Feature UUID : auj9-bazar-list-send-mail-dynamic
     */
    public function sendmailApi()
    {
        return $this->getService(BazarSendMailController::class)->sendmailApi();
    }
    
    /**
     * @Route("/api/auj9/send-mail/filterentries", methods={"POST"},options={"acl":{"public","+"}})
     * Feature UUID : auj9-bazar-list-send-mail-dynamic
     */
    public function filterAuthorizedEntries()
    {
        return $this->getService(BazarSendMailController::class)->filterAuthorizedEntries();
    }

    /**
     * @Route("/api/auj9/send-mail/currentuseremail", methods={"GET"},options={"acl":{"public","+"}})
     * Feature UUID : auj9-bazar-list-send-mail-dynamic
     */
    public function getCurrentUserEmail()
    {
        return $this->getService(BazarSendMailController::class)->getCurrentUserEmail();
    }
    /**
     * @Route("/api/openagenda/config/html", methods={"GET"}, options={"acl":{"public"}})
     * Feature UUID : auj9-open-agenda-connect
     */
    public function configOpenAgendaHTML()
    {
        return $this->getService(ConfigOpenAgendaController::class)->configOpenAgendaHTML();
    }
    /**
     * @Route("/api/openagenda/config/setkey", methods={"POST"}, options={"acl":{"public","@admins"}})
     * Feature UUID : auj9-open-agenda-connect
     */
    public function setKey()
    {
        return $this->getService(ConfigOpenAgendaController::class)->setKey();
    }
    /**
     * @Route("/api/openagenda/config/removekey", methods={"POST"}, options={"acl":{"public","@admins"}})
     * Feature UUID : auj9-open-agenda-connect
     */
    public function removeKey()
    {
        return $this->getService(ConfigOpenAgendaController::class)->removeKey();
    }
    /**
     * @Route("/api/openagenda/config/setassociation", methods={"POST"}, options={"acl":{"public","@admins"}})
     * Feature UUID : auj9-open-agenda-connect
     */
    public function setAssociation()
    {
        return $this->getService(ConfigOpenAgendaController::class)->setAssociation();
    }
    /**
     * @Route("/api/openagenda/config/removeassociation", methods={"POST"}, options={"acl":{"public","@admins"}})
     * Feature UUID : auj9-open-agenda-connect
     */
    public function removeAssociation()
    {
        return $this->getService(ConfigOpenAgendaController::class)->removeAssociation();
    }
}
