<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace YesWiki\Alternativeupdatej9rem;

use AutoUpdate\Messages;
use AutoUpdate\PackageCollection;
use Exception;
use Throwable;
use YesWiki\Alternativeupdatej9rem\Entity\Repository;
use YesWiki\Alternativeupdatej9rem\Exception\UpgradeException;
use YesWiki\Alternativeupdatej9rem\Service\AutoUpdateService;
use YesWiki\Core\YesWikiAction;
use YesWiki\Security\Controller\SecurityController;

include_once 'tools/autoupdate/vendor/autoload.php';

class AlternativeUpdateJ9rem2Action extends YesWikiAction
{
    protected $autoUpdateService;
    protected $securityController;

    public function formatArguments($arg)
    {
        return([
            'versions' => array_filter($this->formatArray($arg['versions'] ?? null), 'is_string'),
        ]);
    }

    public function run()
    {
        // get services
        $this->autoUpdateService = $this->getService(AutoUpdateService::class);
        $this->securityController = $this->getService(SecurityController::class);

        // check if activated
        if (!$this->autoUpdateService->isActivated()) {
            return "";
        }
        if (empty($this->arguments['versions'])) {
            throw new Exception("params 'versions' should not be empty");
        }

        if ($this->autoUpdateService->isAdmin()) {
            return $this->wiki->render("@alternativeupdatej9rem/status-vusjs.twig", [
                'baseUrl' => $this->autoUpdateService->baseUrl(),
                'isAdmin' => $this->autoUpdateService->isAdmin(),
                'isHibernated' => $this->securityController->isWikiHibernated(),
                'uid' => uniqid('alternativeupdate_', true),
                'versions' => implode(',', $this->arguments['versions'])
            ]);
        }

        $repository = $this->autoUpdateService->initRepository($this->arguments['versions'][0]);

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
                        $repos[$key][$type][$package->name] = $package;
                        $packagesNames[] = $package->name;
                    }
                }
            }
        }

        return $this->wiki->render("@alternativeupdatej9rem/status.twig", [
            'baseUrl' => $this->autoUpdateService->baseUrl(),
            'isAdmin' => $this->autoUpdateService->isAdmin(),
            'isHibernated' => $this->securityController->isWikiHibernated(),
            'repos' => $repos,
            'localTools' => [],
            'localThemes' => [],
            'showThemes' => true,
            'showTools' => true,
            'uid' => str_replace([".","/","-","\\"],'',uniqid('alternativeupdate_', true)),
            'versions' => implode(',', $this->arguments['versions'])
        ]);
    }
}
