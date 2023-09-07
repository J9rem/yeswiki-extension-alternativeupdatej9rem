<?php

namespace YesWiki\Alternativeupdatej9rem;

use YesWiki\Core\Service\AclService;
use YesWiki\Core\YesWikiHandler;

/**
 * not needed since 4.4.1
 */
class EditHandler__ extends YesWikiHandler
{
    public function run()
    {
        // get services
        $aclService = $this->getService(AclService::class);
        $release = $this->params->has('yeswiki_release') ? $this->params->get('yeswiki_release') : '';
        $release = !is_string($release) ? '' : $release;
        if (!preg_match('/^4\.(?:[0-3]\.[0-9]|4\.0)+$/',$release)){
            return;
        }

        if (
            !$this->params->get('hide_keywords')
            && $aclService->hasAccess("write")
        ){
                
            // clean output
            $matches = [];
            if (preg_match('/<script>[^<]+var tagsexistants[^<]+<\/script>/',$this->output,$matches)){
                $this->output = str_replace(
                    $matches[0],
                    '',
                    $this->output
                );
            }
        }
    }
}
