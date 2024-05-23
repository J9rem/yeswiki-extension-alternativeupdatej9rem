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

namespace YesWiki\Alternativeupdatej9rem;

use Throwable;
use YesWiki\Alternativeupdatej9rem\Entity\Repository;
use YesWiki\Alternativeupdatej9rem\Exception\UpgradeException;
use YesWiki\Alternativeupdatej9rem\Service\AutoUpdateService;
use YesWiki\Core\YesWikiAction;
use YesWiki\Security\Controller\SecurityController;

include_once 'tools/autoupdate/vendor/autoload.php';

class AlternativeUpdateJ9remAction extends YesWikiAction
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

        $repos = empty($repository) ? [] : $this->autoUpdateService->getReposForAlternative($repository);

        $localTools = $repository->getLocalToolsPackages();
        $localThemes = $repository->getLocalThemesPackages();
        $core = $repository->getCorePackage();

        return $this->wiki->render("@alternativeupdatej9rem/status.twig", [
            'baseUrl' => $this->autoUpdateService->baseUrl(),
            'isAdmin' => $this->autoUpdateService->isAdmin(),
            'isHibernated' => $this->securityController->isWikiHibernated(),
            'repos' => $repos,
            'localTools' => $localTools,
            'localThemes' => $localThemes,
            'core' => $core,
        ]);
    }
}
