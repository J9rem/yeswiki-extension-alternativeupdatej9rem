<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace YesWiki\Alternativeupdatej9rem;

use YesWiki\Core\Service\AssetsManager;
use YesWiki\Core\Service\ThemeManager;
use YesWiki\Core\YesWikiAction;

class LinkStyleAction__ extends YesWikiAction
{
    public function run()
    {
        $themeManager = $this->getService(ThemeManager::class);
        if (!$themeManager->getUseFallbackTheme()){
            $favoriteStyle = $themeManager->getFavoriteStyle();
            $favoritePreset = $themeManager->getFavoritePreset();
            $styleFile = 'themes/'.$themeManager->getFavoriteTheme().'/styles/'.$favoriteStyle;
            $presetsActivated = !empty(($themeManager->getTemplates())[$themeManager->getFavoriteTheme()]['presets']) && !empty($favoritePreset);
            if ($favoriteStyle!='none'){
                if (substr($favoriteStyle, -4, 4) == '.css'
                && file_exists('custom/'.$styleFile) 
                && strpos($this->output,'custom/'.$styleFile) === false) {
                    $this->output .= $this->getService(AssetsManager::class)->LinkCSSFile('custom/'.$styleFile, '', '', 'id="mainstyle"');
                }
                if ($presetsActivated){
                    $custom_prefix = ThemeManager::CUSTOM_CSS_PRESETS_PREFIX;
                    $presetIsCustom = (substr($favoritePreset, 0, strlen($custom_prefix)) == $custom_prefix);
                    if (!$presetIsCustom) {
                        $presetFile = 'themes/'.$themeManager->getFavoriteTheme().'/presets/'.$favoritePreset;
                    } else {
                        $presetFile = ThemeManager::CUSTOM_CSS_PRESETS_PATH . '/' . substr($favoritePreset, strlen($custom_prefix));
                    }

                    if (substr($favoritePreset, -4, 4) == '.css'
                        && !$presetIsCustom
                        && file_exists('custom/'.$presetFile) 
                        && strpos($this->output,'custom/'.$presetFile) === false){

                        $this->output .= $this->getService(AssetsManager::class)->LinkCSSFile('custom/'.$presetFile);
                    }
                }
            }
        }
    }
}
