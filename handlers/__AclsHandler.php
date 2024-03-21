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


namespace YesWiki\Alternativeupdatej9rem;

use YesWiki\Alternativeupdatej9rem\Service\CacheService;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\YesWikiHandler;

class __AclsHandler extends YesWikiHandler
{
    public function run()
    {
        $entryManager = $this->getService(EntryManager::class);
        if (
            $entryManager->isEntry($this->wiki->GetPageTag())
            && $this->wiki->page
            && (
                $this->wiki->UserIsOwner() ||
                $this->wiki->UserIsAdmin()
            )
            && $_POST) {
            $entry = $entryManager->getOne($this->wiki->GetPageTag());
            if (!empty($entry['id_typeannonce'])) {
                $this->getService(CacheService::class)->updateFormIdTimestamp(strval($entry['id_typeannonce'])) ;
            }
        }
    }
}
