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

use YesWiki\Core\Service\AssetsManager;
use YesWiki\Core\YesWikiAction;

class __LinkStyleAction extends YesWikiAction
{
    public function run()
    {
        // Feature UUID : auj9-custom-changes
        $this->getService(AssetsManager::class)->AddCSSFile('tools/alternativeupdatej9rem/styles/fix.css');
        // Feature UUID : auj9-choice-display-hidden-field
        $this->getService(AssetsManager::class)->AddJavascriptFile('tools/alternativeupdatej9rem/javascripts/toggle-button-hidden.js');
        $this->getService(AssetsManager::class)->AddCSSFile('tools/alternativeupdatej9rem/styles/toggle-button-hidden.css');
        /* === Feature UUID : auj9-fix-4-4-2 === */
        $timezone = json_encode(date_default_timezone_get());
        $this->getService(AssetsManager::class)->AddJavascript(<<<JS
        if (typeof wiki === 'object' && !wiki.hasOwnProperty('timezone')){
            wiki.timezone = $timezone
        }
        JS);
        /* === end of Feature UUID : auj9-fix-4-4-2 === */

        $this->replaceBazarCalendar(); // Feature UUID : auj9-fix-4-4-1
        $this->replaceBazarList();// Feature UUID : auj9-fix-4-4-1
    }

    /**
     * Feature UUID : auj9-fix-4-4-1
     */
    protected function replaceBazarList()
    {
        $release = $this->params->get('yeswiki_release');
        $baseUrl = $this->wiki->getBaseUrl();
        $rev = "?v=$release";
        $fileToReplace = "$baseUrl/tools/bazar/presentation/javascripts/bazar-list-dynamic.js$rev";
        if (!empty($GLOBALS['js']) && strpos($GLOBALS['js'],$fileToReplace) !== false){
            $matches = [];
            if (preg_match(
                '/^(?:'
                    .'(4\.4\.0)' // 4.4.0 => $1
                    .'|' // OR
                    .'('
                        .'(?:[5-9]|[1-9][0-9])\.[0-9]+\.[0-9]+' // something higher than 5.0.0
                        .'|' // OR
                        .'4\.(?:' // begin by 4
                                .'(?:[5-9]|[1-9][0-9])\.[0-9]+' // higher than 4.5.0
                                .'|'
                                .'4\.[1-9][0-9]*' // higher than 4.4.1
                            .')'
                    .')'
                .')$/',
                $release,
                $matches)){
                $replacement = 
                    (!empty($matches[1]))
                    ? "$baseUrl/tools/alternativeupdatej9rem/javascripts/bazar-list-dynamic-4-4-0.js"
                    : (
                        (!empty($matches[2]))
                        ? "$baseUrl/tools/alternativeupdatej9rem/javascripts/bazar-list-dynamic-4-4-1.js"
                        : $fileToReplace
                    );
                $GLOBALS['js'] = str_replace($fileToReplace,$replacement,$GLOBALS['js']);
            }
        }
    }

    /**
     * Feature UUID : auj9-fix-4-4-1
     */
    protected function replaceBazarCalendar()
    {
        $release = $this->params->get('yeswiki_release');
        $baseUrl = $this->wiki->getBaseUrl();
        $rev = "?v=$release";
        $fileToReplace = "$baseUrl/tools/bazar/presentation/javascripts/components/BazarCalendar.js$rev";
        if ($release === '4.4.1'
            && !empty($GLOBALS['js'])
            && strpos($GLOBALS['js'],$fileToReplace) !== false){
            $replacement = "$baseUrl/tools/alternativeupdatej9rem/javascripts/components/BazarCalendar-4-4-1.js";
            $GLOBALS['js'] = str_replace($fileToReplace,$replacement,$GLOBALS['js']);
        }
    }
}
