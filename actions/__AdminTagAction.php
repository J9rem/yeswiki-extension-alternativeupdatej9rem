<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-custom-changes
 */


namespace YesWiki\Alternativeupdatej9rem;

use YesWiki\Core\YesWikiAction;

/**
 * customization not to authorize admintag action for not admins
 */
class __AdminTagAction extends YesWikiAction
{
    public function run()
    {
        if (!$this->wiki->UserIsAdmin()) {
            $acl = $this->wiki->GetModuleACL('admintag', 'action');
            if (empty($acl) || in_array($acl, ['*','+'], true)) {
                $this->wiki->SetModuleACL('admintag', 'action', '@admins');
            }
        }
    }
}
