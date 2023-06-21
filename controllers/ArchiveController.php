<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YesWiki\Alternativeupdatej9rem\Controller;

use YesWiki\Core\Controller\ArchiveController as CoreArchiveController;
use YesWiki\Core\YesWikiController;

if (class_exists(CoreArchiveController::class,false)){

    class ArchiveController extends CoreArchiveController
    {
        /**
         * start archive async or async via CLI
         * @param array $params
         * @param bool $startAsync
         * @return string uid
         */
        protected function startArchive(
            array $params = [],
            bool $startAsync = true
        ): string {
            $savefiles = (isset($params['savefiles']) && in_array($params['savefiles'], [1,"1",true,'true'], true));
            $savedatabase = (isset($params['savedatabase']) && in_array($params['savedatabase'], [1,"1",true,'true'], true));

            return $this->archiveService->startArchiveNew(
                $savefiles,
                $savedatabase,
                [],
                [],
                $startAsync
            );
        }
    }
    
} else {
    
    class ArchiveController extends YesWikiController
    {
    }
}