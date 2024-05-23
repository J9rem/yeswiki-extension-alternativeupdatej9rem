<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-autoupdate-system
 * Feature UUID : auj9-fix-4-4-3
 */

namespace YesWiki\Alternativeupdatej9rem\Entity;

use AutoUpdate\PackageTool as AutoUpdatePackageTool; // Feature UUID : auj9-fix-4-4-3
use YesWiki\AutoUpdate\Entity\PackageTool as CorePackageTool;
use Exception;

trait PackageToolTrait
{
    public function __construct($release, $address, $desc, $doc, $minimalPhpVersion = null)
    {
        parent::__construct($release, $address, $desc, $doc, $minimalPhpVersion);
    }
    public function setdownloadedFile(string $downloadedFile)
    {
        $this->downloadedFile = $downloadedFile;
    }
    public function setMD5File(string $md5File)
    {
        $this->md5File = $md5File;
    }
}

/* === Feature UUID : auj9-fix-4-4-3 === */
if (file_exists('tools/autoupdate/vendor/autoload.php')){
    include_once 'tools/autoupdate/vendor/autoload.php';
}
if (class_exists(AutoUpdatePackageTool::class)){
    class PackageTool extends AutoUpdatePackageTool
    {
        use PackageToolTrait;
    }
} else {
/* === end of Feature UUID : auj9-fix-4-4-3 === */
    class PackageTool extends CorePackageTool
    {
        use PackageToolTrait;
    }
}
