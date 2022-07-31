<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YesWiki\Alternativeupdatej9rem\Entity;

use AutoUpdate\PackageTool;
use AutoUpdate\Release;
use Exception;

include_once 'tools/autoupdate/vendor/autoload.php';

class PackageToolLocal extends PackageTool
{
    public const DESC_FILENAME = "desc.xml";

    protected $active;

    public function __construct($active, $address, $desc, $doc, $minimalPhpVersion = null)
    {
        parent::__construct(new Release(Release::UNKNOW_RELEASE), $address, $desc, $doc, $minimalPhpVersion);
        $this->active = ($active == true);
        $infos = $this->getInfos();
        if (!empty($infos['release']) && is_string($infos['release'])) {
            $this->localRelease = new Release($infos['release']);
        }
    }
    protected function name()
    {
        return $this->address;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function isTheme(): bool
    {
        return false;
    }

    protected function localPath()
    {
        return preg_replace('/^\//', '', parent::TOOL_PATH."{$this->name}/");
    }

    public function activate(bool $status = true): bool
    {
        $xmlPath = $this->localPath . "desc.xml";
        if (is_file($xmlPath)) {
            $xml = file_get_contents($xmlPath);
            $newXml = preg_replace("/(active=)\"([^\"]+)\"/", "$1\"".($status ? "1" : "0")."\"", $xml);
            if (!empty($newXml) && $newXml != $xml) {
                file_put_contents($xmlPath, $newXml);
                return true;
            }
        }
        return false;
    }
}
