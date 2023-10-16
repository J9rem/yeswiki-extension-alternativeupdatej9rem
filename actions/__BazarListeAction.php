<?php

namespace YesWiki\Alternativeupdatej9rem;

use Exception;
use YesWiki\Bazar\Exception\ParsingMultipleException;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\YesWikiAction;

class __BazarListeAction extends YesWikiAction
{
    public function formatArguments($arg)
    {
        $newArgs = [];

        /* === Feature UUID : auj9-bazar-list-video-dynamic === */
        if (($arg['template'] ?? '') === 'video'){
            $newArgs['dynamic'] = true;
        }
        /* === end of Feature UUID : auj9-bazar-list-video-dynamic === */
        /* === Feature UUID : auj9-bazar-list-send-mail-dynamic === */
        if (!empty($arg['template']) && $arg['template'] == "send-mail") {
            $newArgs['dynamic'] = true;
            $newArgs['pagination'] = -1;
        }
        /* === end of Feature UUID : auj9-bazar-list-send-mail-dynamic === */
        return $newArgs;
    }

    public function run(){

    }
}
