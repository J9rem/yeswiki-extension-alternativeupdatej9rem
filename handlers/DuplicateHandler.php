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

use Exception;
use YesWiki\Alternativeupdatej9rem\Service\DuplicationFollower;
use YesWiki\Bazar\Controller\EntryController;
use YesWiki\Bazar\Field\BazarField;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Core\Service\AclService;
use YesWiki\Core\Service\PageManager;
use YesWiki\Core\YesWikiHandler;

class DuplicateHandler extends YesWikiHandler
{
    protected $aclService;
    protected $duplicationFollower;
    protected $entryController;
    protected $entryManager;
    protected $formManager;
    protected $pageManager;

    public function run()
    {
        // get services
        $this->aclService = $this->getService(AclService::class);
        $this->duplicationFollower = $this->getService(DuplicationFollower::class);
        $this->entryController = $this->getService(EntryController::class);
        $this->entryManager = $this->getService(EntryManager::class);
        $this->formManager = $this->getService(FormManager::class);
        $this->pageManager = $this->getService(PageManager::class);

        // check current user can read
        if (!$this->aclService->hasAccess('read')){
            return $this->finalRender($this->render('@templates/alert-message.twig',[
                'type' => 'danger',
                'message' => _t('TEMPLATE_NO_ACCESS_TO_PAGE')
            ]));
        }
        $tag = $this->wiki->getPageTag();
        $page = $this->pageManager->getOne($tag);
        $isEntry = $this->entryManager->isEntry($tag);

        if (!empty($page)){
            return ($this->entryManager->isEntry($tag))
                ? $this->duplicateEntry($tag)
                : $this->duplicatePage($tag);
        }
        return $this->chooseFromPageOrCreate($tag);
    }

    protected function finalRender(string $content, bool $includePage = false): string
    {
        $output = $includePage
            ? <<<HTML
            <div class="page">
                $content
            </div>
            HTML
            : $conten;
        return $this->wiki->Header().$content.$this->wiki->Footer() ;
    }

    protected function duplicateEntry(string $tag): string
    {
        $entry = $this->entryManager->getOne($tag); // with current rights
        $form = $this->formManager->getOne($entry['id_typeannonce']);
        if (empty($form['prepared'])){
            throw new Exception("Impossible to duplicate because form is not existing !");
        }
        if (!empty($_GET['created']) && $_GET['created'] === '1'){
            $followedEntryIds = [];
            if ($this->duplicationFollower->isFollowed($tag, $followedEntryIds)){
                $firstId = array_shift($followedEntryIds);
                if (count($followedEntryIds) > 0){
                    flash(_t('AUJ9_OTHER_ENTRIES_CREATED',[
                        'links'=> implode(',',array_map(
                            function ($id) {
                                return <<<HTML
                                    <a class="new-tab" href="{$this->wiki->Href('',$id)}">$id</a>
                                    HTML;
                            },
                            $followedEntryIds
                        ))
                    ]),'success');
                }
                $this->wiki->Redirect($this->wiki->Href(
                    $this->isInIframe() ? 'iframe' : '', // handler
                    $firstId,
                    [
                        'message' => 'ajout_ok'
                    ],
                    false
                ));
                throw new Exception("Error Processing Request");
            } else {
                return $this->finalRender(
                    $this->render('@templates/alert-message.twig',[
                        'type' => 'info',
                        'message' => _t('AUJ9_DUPLICATION_TROUBLE')
                    ]).
                    $this->entryController->view($tag),true);
            }
        }
        if (!isset($_POST['bf_titre'])){
            foreach ($form['prepared'] as $field) {
                if ($field instanceof BazarField){
                    $propName = $field->getPropertyName();
                    if (!empty($propName) && isset($entry[$propName])){
                        $_POST[$propName] = $entry[$propName];
                        $_REQUEST[$propName] = $entry[$propName];
                    }
                }
            }
            // clean inputs
            if (isset($_POST['bf_titre'])){
                unset($_POST['bf_titre']);
            }
            $redirectUrl = $this->wiki->Href(
                $this->isInIframe() ? 'iframe' : '', // handler
                $tag
            );
            return $this->finalRender($this->entryController->create($form['bn_id_nature'], $redirectUrl),true);
        }
        $redirectUrl = $this->wiki->Href(
            $this->isInIframe() ? 'duplicateiframe' : 'duplicate', // handler
            $tag,
            ['created'=>true], // params
            false // html outputs ?
        );
        
        return $this->finalRender($this->entryController->create($form['bn_id_nature'], $redirectUrl),true);
    }

    protected function duplicatePage(string $tag): string
    {
        return $this->finalRender($this->render('@templates/alert-message.twig',[
            'type' => 'info',
            'message' => "Duplication of page $tag"
        ]));
    }

    protected function chooseFromPageOrCreate(string $tag): string
    {
        // check current user can write
        if (!$this->aclService->hasAccess('write')){
            return $this->finalRender($this->render('@templates/alert-message.twig',[
                'type' => 'danger',
                'message' => _t('EDIT_NO_WRITE_ACCESS')
            ]));
        }
        return $this->finalRender($this->render('@templates/alert-message.twig',[
            'type' => 'danger',
            'message' => 'test'
        ]));
    }

    protected function isInIframe()
    {
        return preg_match('/duplicateiframe$/Ui', getAbsoluteUrl());
    }
}
