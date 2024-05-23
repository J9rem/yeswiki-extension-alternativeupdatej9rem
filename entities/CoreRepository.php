<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-fix-4-4-3
 */

namespace YesWiki\Alternativeupdatej9rem\Entity;

use AutoUpdate\Repository As AutoUpdateCoreRepository;
use YesWiki\AutoUpdate\Entity\Repository As CoreCoreRepository;

if (file_exists('tools/autoupdate/vendor/autoload.php')){
    include_once 'tools/autoupdate/vendor/autoload.php';
}
if (class_exists(AutoUpdateCoreRepository::class)){
    class CoreRepository extends AutoUpdateCoreRepository{}
} else {
    class CoreRepository extends CoreCoreRepository{}
}
