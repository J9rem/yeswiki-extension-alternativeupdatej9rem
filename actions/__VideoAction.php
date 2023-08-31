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

use YesWiki\Core\YesWikiAction;

class __VideoAction extends YesWikiAction
{
    public function formatArguments($arg)
    {
        $url = (!empty($arg['url']) && is_string($arg['url'])) ? $arg['url'] : '';
        $matches = [];
        $id = $arg['id'] ?? '';
        $serveur = $arg['serveur'] ?? '';
        $peertubeinstance = $arg['peertubeinstance'] ?? '';
        if (preg_match('/^'
            .'(https?:\\/\\/.*)' // begin as url
            .'(?:' // multiple options
                .'youtu\.be\/(.+)|youtube.*watch\?v=(.+)' // youtube
                .'|vimeo\.com\/(.+)' // vimeo
                .'|dai\.?ly.+\/video\/(.+)' // dailymotion
                .'|(?:\/videos\/embed\/|\/w\/)(.+)' // peertube
            .')/',$url,$matches)){
            if (!empty($matches[2])){
                $serveur  = 'youtube';
                $id = $matches[2];
            } elseif (!empty($matches[3])){
                $serveur  = 'youtube';
                $id = $matches[3];
            } elseif (!empty($matches[4])){
                $serveur  = 'vimeo';
                $id = $matches[4];
            } elseif (!empty($matches[5])){
                $serveur  = 'dailymotion';
                $id = $matches[5];
            } elseif (!empty($matches[6])){
                $serveur  = 'peertube';
                $id = $matches[6];
                $peertubeinstance = $matches[1];
            }
        }
        return [
            'id' => $id,
            'serveur' => $serveur,
            'peertubeinstance' => $peertubeinstance,
        ];
    }
    
    public function run()
    {
        return '';
    }
}
