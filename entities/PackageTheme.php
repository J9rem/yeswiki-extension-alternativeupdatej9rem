<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-autoupdate-system
 */

namespace YesWiki\Alternativeupdatej9rem\Entity;

use AutoUpdate\PackageTheme as CorePackageTheme;
use AutoUpdate\Release;
use Exception;

include_once 'tools/autoupdate/vendor/autoload.php';

class PackageTheme extends CorePackageTheme
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
