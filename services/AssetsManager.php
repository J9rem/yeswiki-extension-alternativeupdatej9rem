<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-fix-4-4-1
 */

namespace YesWiki\Alternativeupdatej9rem\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Core\Service\AssetsManager as CoreAssetsManager;

/**
 * not needed since 4.4.1
 */

if (class_exists(CoreAssetsManager::class,false)){
    class AssetsManager extends CoreAssetsManager
    {
        public function AddJavascriptFile($file, $first = false, $module = false)
        {
            $release = $this->wiki->services->get(ParameterBagInterface::class)->get('yeswiki_release');
            $shouldReplace = (is_string($release)
                && preg_match('/^4\.(?:[0-3]\.[0-9]|4\.0)$/',$release));
            return parent::AddJavascriptFile(
                ($shouldReplace && $file == 'tools/bazar/presentation/javascripts/components/BazarMap.js')
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