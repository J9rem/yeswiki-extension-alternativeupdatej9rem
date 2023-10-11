<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-local-cache
 */

namespace YesWiki\Alternativeupdatej9rem\Service;

use Doctrine\Common\Cache\PhpFileCache as DoctrinePhpFileCache;

class PhpFileCache extends DoctrinePhpFileCache
{
    public function publicGetFiletime($id): int
    {
        $filename = $this->getFilename($id);
        if (empty($filename)){
            return -1;
        }
        $ftime = filemtime($filename);
        return ($ftime === false) ? -1 : $ftime; 
    }
}