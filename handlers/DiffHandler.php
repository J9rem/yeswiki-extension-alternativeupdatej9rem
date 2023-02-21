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

use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\YesWikiHandler;
use YesWiki\Core\Service\AclService;
use YesWiki\Core\Service\DiffService;
use YesWiki\Core\Service\PageManager;

class DiffHandler extends YesWikiHandler
{
    protected $diffService;
    protected $entryManager;
    protected $pageManager;

    public function run()
    {
        // getServices
        $aclService = $this->getService(AclService::class);
        $this->diffService = $this->getService(DiffService::class);
        $this->entryManager = $this->wiki->services->get(EntryManager::class);
        $this->pageManager = $this->wiki->services->get(PageManager::class);

        if (!$aclService->hasAccess('read')){
            return $this->renderInSquelette('@templates/alert-mesage.twig',[
                'type' => 'warning',
                'message' => 'Vous n\'avez pas accès à cette page.'
            ]);
        } else {
            if (!empty($_REQUEST['a']) && !empty($_REQUEST['b'])){
                return $this->compare($_REQUEST);
            }
            $pages = $this->pageManager->getRevisions($this->wiki->tag);
            return $this->renderInSquelette('@alternativeupdatej9rem/revisions-handler.twig',[
                'pages' => $pages
            ]);
        }
    }

    protected function compare(array $get): string
    {
        if (!empty($get["fastdiff"])) {
            return $this->fastDiff($get);
        } else {        
            
            // load pages
            $pageA = $this->pageManager->getById($get["a"]);
            $pageB = $this->pageManager->getById($get["b"]);

            $isEntry = $this->entryManager->isEntry($this->wiki->tag);
            if ($isEntry) {
                $pageA['html'] = '';
                $pageA['code'] = $this->diffService->formatJsonCodeIntoHtmlTable($pageA);
                $pageB['html'] = '';
                $pageB['code'] = $this->diffService->formatJsonCodeIntoHtmlTable($pageB);
            } else {
                $pageA['html'] = '';
                $pageA['code'] = $pageA['body'];
                $pageB['html'] = '';
                $pageB['code'] = $pageB['body'];
            }
            $diff = $this->diffService->getPageDiff($pageB,$pageA);
            if (!$isEntry){
                $diff = str_replace("\n",'<br/>',$diff);
            }
            return $this->renderInSquelette('@alternativeupdatej9rem/code-diff.twig',[
                'pageA' => $pageA,
                'pageB' => $pageB,
                'diff' => $diff
            ]);
        }
    }
    protected function fastDiff(array $get): string
    {
        // load pages
        $pageA = $this->pageManager->getById($get["a"]);
        $pageB = $this->pageManager->getById($get["b"]);

        // prepare bodies
        $isEntry = $this->entryManager->isEntry($this->wiki->tag);
        if ($isEntry){
            $bodyA = explode(",\\\"", $pageA["body"]);
            $bodyB = explode(",\\\"", $pageB["body"]);
        } else {
            $bodyA = explode("\n", $pageA["body"]);
            $bodyB = explode("\n", $pageB["body"]);
        }

        $added = array_diff($bodyA, $bodyB);
        $deleted = array_diff($bodyB, $bodyA);

        $this->wiki->RegisterInclusion($this->wiki->GetPageTag());
        $output = $this->renderInSquelette('@alternativeupdatej9rem/fast-diff.twig',[
            'pageA' => $pageA,
            'pageB' => $pageB,
            'added' => implode($isEntry ? ",\n\\\"" : "\n",$added),
            'deleted' => implode($isEntry ? ",\n\\\"" : "\n",$deleted),
        ]);
        $this->wiki->UnregisterLastInclusion();

        return $output;

    }
}
