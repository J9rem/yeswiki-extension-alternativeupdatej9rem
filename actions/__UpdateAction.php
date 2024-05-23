<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-autoupdate-system
 * Feature UUID : auj9-fix-4-4-3
 */

namespace YesWiki\Alternativeupdatej9rem;

use Throwable;
use YesWiki\Alternativeupdatej9rem\Entity\Messages; // Feature UUID : auj9-fix-4-4-3
use YesWiki\Alternativeupdatej9rem\Entity\PackageCollection; // Feature UUID : auj9-fix-4-4-3
use YesWiki\Alternativeupdatej9rem\Entity\Repository;
use YesWiki\Alternativeupdatej9rem\Service\AutoUpdateService;
use YesWiki\Alternativeupdatej9rem\Service\RevisionChecker;
// use YesWiki\AutoUpdate\Entity\Messages;
// use YesWiki\AutoUpdate\Entity\PackageCollection;
use YesWiki\Core\YesWikiAction;
use YesWiki\Security\Controller\SecurityController;

/**
 * customization for update
 */
class __UpdateAction extends YesWikiAction
{
    protected $autoUpdateService;
    /**
     * @var bool $isNewSystem
     */
    protected $isNewSystem;
    protected $securityController;

    public function formatArguments($arg)
    {
        return([
            'version' => (empty($arg['version']) || !is_string($arg['version'])) ? '' : $arg['version'],
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

        
        if ($this->autoUpdateService->isAdmin() &&
            !$this->securityController->isWikiHibernated()) {
            $repository = $this->autoUpdateService->initRepository($this->arguments['version']);
            $this->isNewSystem = !RevisionChecker::isRevisionThan($this->params, true, 'doryphore', 4, 4, 4);
            if (!$this->isNewSystem){
                if (isset($_GET['upgrade'])) {
                    $action = 'upgrade';
                    $packageName = $this->filterInput('upgrade');
                } elseif (isset($_GET['delete'])) {
                    $action = 'delete';
                    $packageName = $this->filterInput('delete');
                } elseif (isset($_GET['activate'])) {
                    $action = 'activate';
                    $packageName = $this->filterInput('activate');
                } elseif (isset($_GET['deactivate'])) {
                    $action = 'deactivate';
                    $packageName = $this->filterInput('deactivate');
                } else {
                    $action = '';
                    $packageName = '';
                }
            } else {
                $action = $this->filterInput('action');
                $packageName = $this->filterInput('package');
            }
            switch ($action) {
                case 'upgrade':
                    return $this->upgradeAlternativeIfNeeded($repository, $packageName);
                case 'delete':
                    return $this->deleteAlternativeIfNeeded($repository, $packageName);
                case 'activate':
                    return $this->activationLocal($repository, $packageName, true);
                case 'deactivate':
                    return $this->activationLocal($repository, $packageName, false);
                
                default:
                    return null;
            }
        }

        return null;
    }

    protected function filterInput(string $name): string
    {
        if (empty($_GET[$name])){
            return '';
        }
        $value = filter_var($_GET[$name], FILTER_UNSAFE_RAW);
        return ($value === false) ? "" : htmlspecialchars(strip_tags($value));
    }

    /**
     * update alternative package
     * @param Repository $repository
     * @param string $packageName
     */
    private function upgradeAlternativeIfNeeded(Repository $repository, string $packageName): string
    {
        if (empty($packageName) || $packageName == "yeswiki") {
            return '';
        }

        $messages = $this->autoUpdateService->upgradeAlternativeIfNeeded($repository, $packageName);

        if (is_null($messages)) {
            return '';
        } else {
            $this->arguments['version'] = 'unknown-to-remove-display';
        }
        return $this->render(
            $this->isNewSystem ? "@autoupdate/update-result.twig" : "@autoupdate/update.twig",
            [
                'messages' => $messages,
                'baseUrl' => $this->autoUpdateService->baseUrl(),
            ]
        );
    }


    /**
     * delete alternative package
     * @param Repository $repository
     * @param string $packageName
     */
    private function deleteAlternativeIfNeeded(Repository $repository, string $packageName): string
    {
        $messages = $this->autoUpdateService->deleteAlternativeOrLocal($repository, $packageName);

        if (is_null($messages)) {
            return '';
        } else {
            $this->arguments['version'] = 'unknown-to-remove-display';
        }

        return $this->render(
            $this->isNewSystem ? "@autoupdate/update-result.twig" : "@autoupdate/update.twig",
            [
                'messages' => $messages,
                'baseUrl' => $this->autoUpdateService->baseUrl(),
            ]
        );
    }

    /**
     * deactive local ext
     * @param Repository $repository
     * @param bool $activation
     * @param string $packageName
     * @return string
     */
    protected function activationLocal(Repository $repository, string $packageName, $activation = true): string
    {
        $key = $activation ? 'activate' : 'deactivate';

        if ($this->autoUpdateService->activationLocal($repository, $packageName, $activation)) {
            flash("L'extension '$packageName' a été " . ($activation ? "activée" : "désactivée"), 'success');
            $this->wiki->Redirect($this->wiki->Href());
        } else {
            flash("L'extension '$packageName' n'a pas été " . ($activation ? "activée" : "désactivée"), 'error');
            $this->wiki->Redirect($this->wiki->Href());
        }
    }
}
