<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-duplicate
 */

namespace YesWiki\Alternativeupdatej9rem;

use YesWiki\Alternativeupdatej9rem\DuplicateHandler;

if (!class_exists(DuplicateHandler::class)) {
    include_once 'tools/alternativeupdatej9rem/handlers/DuplicateHandler.php';
}

class DuplicateIframeHandler extends DuplicateHandler
{
    protected function finalRender(string $content, bool $includePage = false): string
    {
        // inspired from IframeHandler.php and EditIframeHandler.php

        // on recupere les entetes html mais pas ce qu'il y a dans le body
        $header = explode('<body', $this->wiki->Header());
        $output = $header[0].$content;
        // on recupere juste les javascripts et la fin des balises body et html
        $output .= preg_replace('/^.+<script/Us', '<script', $this->wiki->Footer());

        return $output;
    }
}
