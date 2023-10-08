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


namespace YesWiki\Alternativeupdatej9rem;

use YesWiki\Core\YesWikiAction;

/**
 * security (could be remove since doryphore 4.4.1)
 */
class __BazarAction extends YesWikiAction
{
    public function formatArguments($arg)
    {
        $newArgs = [];
        if (!empty($_GET['vue'])
            && is_scalar($_GET['vue'])
            && strval($_GET['vue']) === 'saisir'
            && !empty($_GET['action'])
            && is_scalar($_GET['action'])
            && strval($_GET['action']) === 'supprimer'){
            $_GET['vue'] = 'formulaire';
            $_GET['action'] = '';
            $newArgs['vue'] = 'formulaire';
            $newArgs['action'] = '';
        }
        return $newArgs;
    }

    public function run(){}
}
