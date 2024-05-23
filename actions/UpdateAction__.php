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

use YesWiki\Alternativeupdatej9rem\Service\AutoUpdateService;
use YesWiki\Alternativeupdatej9rem\Service\RevisionChecker;
use YesWiki\Alternativeupdatej9rem\Service\UpdateHandlerService;
use YesWiki\Core\YesWikiAction;
use YesWiki\Security\Controller\SecurityController;

/**
 * customization for update
 */
class UpdateAction__ extends YesWikiAction
{
    protected $autoUpdateService;
    /**
     * @var bool $isNewSystem
     */
    protected $isNewSystem;
    protected $securityController;
    protected $updateHandlerService;

    public function run()
    {
        // get services
        $this->autoUpdateService = $this->getService(AutoUpdateService::class);
        $this->securityController = $this->getService(SecurityController::class);
        $this->updateHandlerService = $this->getService(UpdateHandlerService::class);

        // check if activated
        if (!$this->autoUpdateService->isActivated()) {
            return "";
        }
        
        if ($this->autoUpdateService->isAdmin() &&
            !$this->securityController->isWikiHibernated()) {
            $this->isNewSystem = RevisionChecker::isRevisionThan($this->params, false, 'doryphore', 4, 4, 4, false);
            if ($this->isNewSystem
                && !empty($_GET['action'])
                && $_GET['action'] === 'post_install'
            ){
                $messages = [];

                $this->updateHandlerService->addSpecifiPage($messages);
                $this->updateHandlerService->cleanUnusedMetadata($messages);
                if (!empty($messages)){
                    $anchor = '<ul class="list-group span4">';
                    $this->output = str_replace(
                        $anchor,
                        "$anchor\n"
                        .implode(
                            "\n",
                            array_map(
                                function ($message){
                                    $label = $message['status'] === 'ok' ? 'success' : 'danger';
                                    return <<<HTML
                                        <li class="list-group-item">
                                            <span class="pull-right label label-$label">{$message['status']}</span>
                                            {$message['text']}
                                        </li>
                                    HTML;
                                },
                                $messages
                            )
                        ),
                        $this->output
                    );
                }
            }
        }

        return null;
    }
}
