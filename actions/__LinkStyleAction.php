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
        // Feature UUID : auj9-fix-navbar-login-btn-since-4-4-3
        $this->getService(AssetsManager::class)->AddCSSFile('tools/alternativeupdatej9rem/styles/fix-navbar-button.css');
        // Feature UUID : auj9-custom-changes
        $this->getService(AssetsManager::class)->AddCSSFile('tools/alternativeupdatej9rem/styles/fix.css');
        // Feature UUID : auj9-choice-display-hidden-field
        $this->getService(AssetsManager::class)->AddJavascriptFile('tools/alternativeupdatej9rem/javascripts/toggle-button-hidden.js');
        $this->getService(AssetsManager::class)->AddCSSFile('tools/alternativeupdatej9rem/styles/toggle-button-hidden.css');
    }
}
