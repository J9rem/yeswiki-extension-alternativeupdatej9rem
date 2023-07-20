<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YesWiki\Alternativeupdatej9rem\Service;

use YesWiki\Core\Service\AssetsManager as CoreAssetsManager;

if (class_exists(CoreAssetsManager::class,false)){
    class AssetsManager extends CoreAssetsManager
    {
        public function AddJavascriptFile($file, $first = false, $module = false)
        {
            return parent::AddJavascriptFile(
                ($file == 'tools/bazar/presentation/javascripts/components/BazarMap.js')
                ? 'tools/alternativeupdatej9rem/javascripts/BazarMap.js'
                : $file,
                $first,
                $module
            );
        }
    }
} else {
    class AssetsManager {}
}