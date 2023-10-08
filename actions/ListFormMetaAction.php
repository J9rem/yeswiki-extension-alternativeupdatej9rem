<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-listformmeta-action
 */

 namespace YesWiki\Alternativeupdatej9rem;

use YesWiki\Bazar\Field\EnumField;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Core\Service\PageManager;
use YesWiki\Core\YesWikiAction;

class ListFormMetaAction extends YesWikiAction
{
    public function run()
    {
        if (!$this->wiki->userIsAdmin()){
            return $this->render('@templates/alert-message.twig',[
                'type' => 'danger',
                'message' => get_class($this)." : " . _t('BAZ_NEED_ADMIN_RIGHTS')
            ]);
        }
        $rawForms = $this->getService(FormManager::class)->getAll();
        $forms = empty($rawForms) ? [] : array_map(
            function($form){
                $form['id'] = $form['bn_id_nature'];
                $form['title'] = $form['bn_label_nature'];
                return $form;
            },
            array_values($rawForms)
        );
        $entryManager = $this->getService(EntryManager::class);
        $pageManager = $this->getService(PageManager::class);
        $forms = array_map(
            function($form) use($entryManager,$pageManager,$forms){
                $id = $form['id'];
                $entries = $entryManager->search(['formsIds'=>[$id]]);
                $pages = $pageManager->searchFullText("id=\"$id\"");
                $associatedForms = array_filter(
                    $forms,
                    function($formInternal) use($id){
                        foreach($formInternal['prepared'] as $field){
                            if ($field instanceof EnumField &&
                                $field->isEnumEntryField() &&
                                $field->getLinkedObjectName() == $id
                            ){
                                return true;
                            }
                        }
                        return false;
                    }
                );
                return [
                    'id' => $id,
                    'title' => $form['title'],
                    'nbEntries' => empty($entries) ? 0 : count($entries),
                    'pages' => empty($pages) ? [] : array_map(function($page){
                        return $page['tag'];
                    },$pages),
                    'associatedForms' => $associatedForms
                ];
            },
            $forms
        );
        return $this->render('@alternativeupdatej9rem/list-form-meta.twig',compact(['forms']));
    }
}