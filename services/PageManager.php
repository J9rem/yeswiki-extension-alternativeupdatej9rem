<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-fix-edit-metadata
 */

namespace YesWiki\Alternativeupdatej9rem\Service;

use YesWiki\Core\Service\PageManager as CorePageManager;

class PageManager extends CorePageManager
{
    public function setMetadata($tag, $metadata)
    {
        if ($this->aclService->hasAccess('read',$tag)
            || $this->aclService->hasAccess('write',$tag)
            || (
                !empty($_GET['newpage'])
                && empty($metadata['favorite_preset'])
                && isset($metadata['bgimg'])
            )){
            return 0;
        }

        return parent::setMetadata($tag, $metadata);
    }
}
