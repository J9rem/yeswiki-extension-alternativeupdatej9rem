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
use Throwable;
use YesWiki\Alternativeupdatej9rem\Entity\Repository;
use YesWiki\Alternativeupdatej9rem\Service\AutoUpdateService;
use YesWiki\Core\YesWikiAction;
use YesWiki\Security\Controller\SecurityController;

include_once 'tools/autoupdate/vendor/autoload.php';

class __UpdateAction extends YesWikiAction
{
    protected $autoUpdateService;
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

        $repository = $this->autoUpdateService->initRepository($this->arguments['version']);

        if ($this->autoUpdateService->isAdmin() &&
            !$this->securityController->isWikiHibernated()) {
            if (isset($_GET['upgrade'])) {
                return $this->upgradeAlternativeIfNeeded($repository);
            }
            if (isset($_GET['delete'])) {
                return $this->deleteAlternativeIfNeeded($repository);
            }
            if (isset($_GET['activate'])) {
                return $this->activationLocal($repository, true);
            }
            if (isset($_GET['deactivate'])) {
                return $this->activationLocal($repository, false);
            }
        }

        return null;
    }

    /**
     * update alternative package
     * @param Repository $repository
     */
    private function upgradeAlternativeIfNeeded(Repository $repository) :string
    {
        $packageName = filter_var($_GET['upgrade'], FILTER_UNSAFE_RAW);
        $packageName = ($packageName === false) ? "" : htmlspecialchars(strip_tags($packageName));
        if (empty($packageName) || $packageName == "yeswiki") {
            return '';
        }

        $messages = $this->autoUpdateService->upgradeAlternativeIfNeeded($repository, $packageName);

        if (is_null($messages)) {
            return '';
        } else {
            unset($_GET['upgrade']);
            $_GET['alternativeupdatej9rem'] = "1";
        }
        
        return $this->render("@autoupdate/update.twig", [
            'messages' => $messages,
            'baseUrl' => $this->autoUpdateService->baseUrl(),
        ]);
    }

    
    /**
     * delete alternative package
     * @param Repository $repository
     */
    private function deleteAlternativeIfNeeded(Repository $repository) :string
    {
        $packageName = filter_var($_GET['delete'], FILTER_UNSAFE_RAW);
        $packageName = ($packageName === false) ? "" : htmlspecialchars(strip_tags($packageName));
        if (empty($packageName) || $packageName == "yeswiki") {
            return '';
        }

        if (!empty($repository->getPackage($packageName))) {
            // leave core manage it
            return '';
        }
        list('key' => $key, 'package' => $package) = $repository->getAlternativePackage($packageName);
        if (!empty($package) && get_class($package) === PackageCollection::CORE_CLASS) {
            // not found for alternative repository or core
            return '';
        } elseif (empty($package)) {
            $package = $repository->getLocalPackage($packageName);
            if (empty($package) || get_class($package) === PackageCollection::CORE_CLASS) {
                return '';
            }
        }

        unset($_GET['delete']);
        $_GET['alternativeupdatej9rem'] = "1";

        // update alternative package
        $messages = new Messages();

        // Remise a zéro des messages
        $messages->reset();

        if (false === $package->deletePackage()) {
            $messages->add('AU_DELETE', 'AU_ERROR');
        } else {
            $messages->add('AU_DELETE', 'AU_OK');
        }
        
        return $this->render("@autoupdate/update.twig", [
            'messages' => $messages,
            'baseUrl' => $this->autoUpdateService->baseUrl(),
        ]);
    }

    /**
     * deactive local ext
     * @param Repository $repository
     * @param bool $activation
     * @return string
     */
    protected function activationLocal(Repository $repository, $activation = true): string
    {
        $key = $activation ? 'activate' : 'deactivate';
        $packageName = filter_var($_GET[$key], FILTER_UNSAFE_RAW);
        unset($_GET[$key]);
        $packageName = ($packageName === false) ? "" : htmlspecialchars(strip_tags($packageName));
        if (!empty($packageName) && $packageName != "yeswiki") {
            $package = $repository->getLocalPackage($packageName);
            if (!empty($package) && get_class($package) !== PackageCollection::CORE_CLASS &&
                    $package->activate($activation)) {
                flash("L'extension '$packageName' a été ".($activation ? "activée": "désactivée"), 'success');
                $this->wiki->Redirect($this->wiki->Href());
            }
        }
        flash("L'extension '$packageName' n'a été ".($activation ? "activée": "désactivée"), 'error');
        $this->wiki->Redirect($this->wiki->Href());
    }
}
