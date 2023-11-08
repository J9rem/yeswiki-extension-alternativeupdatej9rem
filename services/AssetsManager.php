<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-can-force-entry-save-for-admin
 */

namespace YesWiki\Alternativeupdatej9rem\Service;

use YesWiki\Core\Service\AssetsManager as CoreAssetsManager;

class AssetsManager extends CoreAssetsManager
{
    public const BAZAR_JS_OLD_PATH = 'tools/bazar/libs/bazar.js';
    public const BAZAR_JS_ALTERNATIVE_PATH = 'tools/alternativeupdatej9rem/javascripts/modified-bazar.js';

    public function AddJavascriptFile($file, $first = false, $module = false)
    {
        if (substr($file,-strlen(self::BAZAR_JS_OLD_PATH)) === self::BAZAR_JS_OLD_PATH){
            $file = str_replace(self::BAZAR_JS_OLD_PATH,self::BAZAR_JS_ALTERNATIVE_PATH,$file);
            $userIsAdmin = json_encode($this->wiki->UserIsAdmin());
            $this->AddJavascript(<<<JAVAS
            var userIsAdmin = $userIsAdmin;
            JAVAS);
        }
        return parent::AddJavascriptFile($file, $first, $module);
    }
}
