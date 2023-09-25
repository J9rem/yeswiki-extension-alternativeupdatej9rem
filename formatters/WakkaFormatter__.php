<?php

namespace YesWiki\Alternativeupdatej9rem;

use YesWiki\Core\YesWikiFormatter;

/**
 * not needed since 4.4.3
 */
class WakkaFormatter__ extends YesWikiFormatter
{
    public function formatArguments($args)
    {
        return [];
    }
    
    public function run()
    {
        // get services
        $release = $this->params->has('yeswiki_release') ? $this->params->get('yeswiki_release') : '';
        $release = !is_string($release) ? '' : $release;
        if (!preg_match('/^4\.(?:[0-3]\.[0-9]|4\.[0-2])+$/',$release)){
            return;
        }

        // clean output
        $matches = [];
        if (preg_match_all("/<a href='([^']+)'([^>]*)>/",$this->output,$matches)){
            foreach($matches[0] as $idx => $match){
                $newEndPart = empty($matches[2][$idx])
                    ? ''
                    :str_replace(
                        ['=true','=false'],
                        ['="true"','="false"'],
                        $matches[2][$idx]
                    );
                $this->output = str_replace(
                    $match,
                    <<<HTML
                    <a href="{$matches[1][$idx]}"$newEndPart>
                    HTML,
                    $this->output
                );
            }
        }
    }
}
