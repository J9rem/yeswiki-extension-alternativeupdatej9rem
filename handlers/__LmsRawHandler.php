<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-fix-lmsraw-handler
 */


namespace YesWiki\Alternativeupdatej9rem;

use YesWiki\Core\YesWikiHandler;

class __LmsRawHandler extends YesWikiHandler
{
    public function run()
    {
        // block usage of ths handler because security breach on read acl
        exit('forbidden');
    }
}
