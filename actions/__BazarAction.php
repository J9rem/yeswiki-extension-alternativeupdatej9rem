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

use YesWiki\Alternativeupdatej9rem\Service\CacheService;
use YesWiki\Core\YesWikiAction;

class __BazarAction extends YesWikiAction
{
    /**
     * security (could be remove since doryphore 4.4.1)
     * Feature UUID : auj9-fix-4-4-1
     */
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

    public function run(){
        /*
         * Feature UUID : auj9-local-cache
         */
        $view = $this->sanitizedGet('vue',function(){
            return $this->arguments['vue'] ?? null;
        });
        $action = $this->sanitizedGet('action',function(){
            return $this->arguments['action'] ?? 'formulaire';
        });
        if (isset($_POST['valider'])
            && $view === 'formulaire'
            && in_array($action,['new','modif'],true)
            && !empty($_POST['bn_id_nature'])){
            $this->getService(CacheService::class)->updateFormIdTimestamp(strval($_POST['bn_id_nature']));
        }
    }

    /**
     * check if get is scalar then return it or result of callback
     * @param string $key
     * @param function $callback
     * @return scalar
     * Feature UUID : auj9-local-cache
     */
    protected function sanitizedGet(string $key,$callback)
    {
        return (isset($_GET[$key]) && is_scalar($_GET[$key]))
            ? $_GET[$key]
            : (is_callable($callback) ? $callback() : null);
    }
}
