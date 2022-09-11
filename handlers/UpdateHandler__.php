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

use YesWiki\Core\YesWikiHandler;
use YesWiki\Core\Service\AclService;
use YesWiki\Core\Service\PageManager;
use YesWiki\Security\Controller\SecurityController;

class UpdateHandler__ extends YesWikiHandler
{
    public const SPECIFIC_PAGE_NAME = "GererMisesAJourSpecific";

    public function run()
    {
        if ($this->getService(SecurityController::class)->isWikiHibernated()) {
            throw new \Exception(_t('WIKI_IN_HIBERNATION'));
        };
        if (!$this->wiki->UserIsAdmin()) {
            return null;
        }

        $aclService = $this->wiki->services->get(AclService::class);
        $pageManager = $this->wiki->services->get(PageManager::class);
        if (!$pageManager->getOne(self::SPECIFIC_PAGE_NAME)) {
            $output = '<strong>Extension AlternativeUpdateJ9rem</strong><br/>';
            $output .= 'ℹ️ Adding the <em>'.self::SPECIFIC_PAGE_NAME.'</em> page<br />';
            // save the page with default value
            $body = "{{alternativeupdatej9rem2 versions=\"doryphore\"}}\n";
            $aclService->save(self::SPECIFIC_PAGE_NAME, 'read', '@admins');
            $aclService->save(self::SPECIFIC_PAGE_NAME, 'write', '@admins');
            $aclService->save(self::SPECIFIC_PAGE_NAME, 'comment', 'comments-closed');
            $pageManager->save(self::SPECIFIC_PAGE_NAME, $body);
            $output .= '✅ Done !<br />';
            // set output
            $this->output = str_replace(
                '<!-- end handler /update -->',
                $output.'<!-- end handler /update -->',
                $this->output
            );
            return null;
        }
    }
}
