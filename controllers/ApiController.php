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
use Throwable;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\Exception\TokenNotFoundException;
use YesWiki\Alternativeupdatej9rem\Service\AutoUpdateService;
use YesWiki\Core\ApiResponse;
use YesWiki\Core\Controller\AuthController;
use YesWiki\Core\Service\UserManager;
use YesWiki\Core\YesWikiController;
use YesWiki\Security\Controller\SecurityController;

class ApiController extends YesWikiController
{
    public const TOKEN_ID = "POST /api/alternativeupdatej9rem";

    /**
     * @Route("/api/alternativeupdatej9rem", methods={"POST"}, options={"acl":{"public", "@admins"}})
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
                            
                            $repos = [];

                            foreach ([
                                'themes' => ['function' => 'getThemesPackages','altFunction' => 'getAlternativeThemesPackages'],
                                'tools' => ['function' => 'getToolsPackages','altFunction' => 'getAlternativeToolsPackages'],
                                ] as $type => $info) {
                                $corePackages = $repository->{$info['function']}();
                                $packagesNames = [];
                                foreach ($corePackages as $package) {
                                    $packagesNames[] = $package->name;
                                }
                                $alternativePackages = $repository->{$info['altFunction']}();
                                foreach ($alternativePackages as $key => $packages) {
                                    foreach ($packages as $package) {
                                        if (!in_array($package->name, $packagesNames)) {
                                            if (!isset($repos[$key])) {
                                                $repos[$key] = [];
                                            }
                                            if (!isset($repos[$key][$type])) {
                                                $repos[$key][$type] = [];
                                            }
                                            $repos[$key][$type][$package->name] = $this->toArray($package);
                                            $packagesNames[] = $package->name;
                                        }
                                    }
                                }
                            }
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
}
