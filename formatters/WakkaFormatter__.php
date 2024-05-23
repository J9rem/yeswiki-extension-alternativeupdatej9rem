<?php

namespace YesWiki\Alternativeupdatej9rem;

use YesWiki\Alternativeupdatej9rem\Service\RevisionChecker;
use YesWiki\Core\YesWikiFormatter;

/**
 * not needed since 4.4.3
 * Feature UUID : auj9-fix-4-4-2
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
        if (
            !$this->wiki->services->has(RevisionChecker::class)
            || $this->getService(RevisionChecker::class)->isRevisionLowerThan('doryphore', 4, 4, 0, false)
            || $this->getService(RevisionChecker::class)->isRevisionHigherThan('doryphore', 4, 4, 3)
            ) {
            return;
        }

        // clean output
        $matches = [];
        if (preg_match_all("/<a href='([^']+)'([^>]*)>/", $this->output, $matches)) {
            foreach($matches[0] as $idx => $match) {
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
