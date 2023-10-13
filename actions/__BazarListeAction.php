<?php

namespace YesWiki\Alternativeupdatej9rem;

use Exception;
use YesWiki\Bazar\Exception\ParsingMultipleException;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\YesWikiAction;

/**
 * Feature UUID : auj9-bazar-list-video-dynamic
 */
class __BazarListeAction extends YesWikiAction
{
    public function formatArguments($arg)
    {
        $newArgs = [];

        // Feature UUID : auj9-bazar-list-video-dynamic
        if (($arg['template'] ?? '') === 'video'){
            $newArgs['dynamic'] = true;
        }
        return $newArgs;
    }

    public function run(){

    }
}
