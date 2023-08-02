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
        $this->getService(AssetsManager::class)->AddCSSFile('tools/alternativeupdatej9rem/styles/fix.css');
        $this->getService(AssetsManager::class)->AddCSSFile('tools/alternativeupdatej9rem/styles/toggle-button-hidden.css');
    }
}
