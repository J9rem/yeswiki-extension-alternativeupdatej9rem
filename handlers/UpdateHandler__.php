<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-autoupdate-system
 * Feature UUID : auj9-video-field
 * Feature UUID : auj9-fix-edit-metadata
 */

namespace YesWiki\Alternativeupdatej9rem;

use Exception;
use YesWiki\Alternativeupdatej9rem\Service\RevisionChecker;
use YesWiki\Alternativeupdatej9rem\Service\UpdateHandlerService;
use YesWiki\Core\YesWikiHandler;
use YesWiki\Security\Controller\SecurityController;

class UpdateHandler__ extends YesWikiHandler
{
    public function run()
    {
        if (RevisionChecker::isRevisionThan($this->params, false, 'doryphore', 4, 4, 4, false)) {
            return null;
        }
        if (!$this->wiki->UserIsAdmin()) {
            return null;
        }
        if ($this->getService(SecurityController::class)->isWikiHibernated()) {
            throw new Exception(_t('WIKI_IN_HIBERNATION'));
        };

        $updateHandlerService = $this->wiki->services->get(UpdateHandlerService::class);

        $messages = [];

        $updateHandlerService->addSpecifiPage($messages);
        $updateHandlerService->removeNotUpToDateTools($messages);
        $updateHandlerService->cleanUnusedMetadata($messages);
        $updateHandlerService->transformVideoFieldToUrlField($messages);

        if (!empty($messages)) {
            $message = implode('<br/>',
                array_map(
                    function ($lines){
                        return implode('<br/>', $lines);
                    },
                    array_column($messages, 'lines')
                )
            );
            $output = <<<HTML
            <strong>Extension AlternativeUpdateJ9rem</strong><br/>
            $message<br/>
            <hr/>
            HTML;

            // set output
            $this->output = str_replace(
                '<!-- end handler /update -->',
                $output . '<!-- end handler /update -->',
                $this->output
            );
        }
        return null;
    }
}
