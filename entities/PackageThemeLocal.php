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

use YesWiki\Alternativeupdatej9rem\Entity\PackageTheme;
use YesWiki\Alternativeupdatej9rem\Entity\Release; //Feature UUID : auj9-fix-4-4-3
// use YesWiki\AutoUpdate\Entity\Release;
use Exception;

class PackageThemeLocal extends PackageTheme
{
    public function __construct($release, $address, $desc, $doc, $minimalPhpVersion = null)
    {
        parent::__construct(new Release(Release::UNKNOW_RELEASE), $address, $desc, $doc, $minimalPhpVersion);
        $infos = $this->getInfos();
        if (!empty($infos['release']) && is_string($infos['release'])) {
            $this->localRelease = new Release($infos['release']);
        }
    }
    protected function name()
    {
        return $this->address;
    }

    public function isTheme(): bool
    {
        return true;
    }

    protected function localPath()
    {
        return preg_replace('/^\//', '', parent::THEME_PATH."{$this->name}/");
    }
}
