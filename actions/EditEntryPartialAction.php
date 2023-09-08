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

use YesWiki\Bazar\Controller\EntryController;
use YesWiki\Bazar\Field\BazarField;
use YesWiki\Bazar\Field\TitleField;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Core\Service\AclService;
use YesWiki\Core\Service\TripleStore;
use YesWiki\Core\YesWikiAction;

class EditEntryPartialAction extends YesWikiAction
{
    protected $aclService;
    protected $entryController;
    protected $entryManager;
    protected $formManager;
    protected $tripleStore;

    public function formatArguments($arg)
    {
        return([
            'id' => (empty($arg['id']) || !is_string($arg['id'])) ? '' : $arg['id'],
            'fields' => $this->formatArray($arg['fields'] ?? null),
        ]);
    }

    public function run()
    {

        // get Services
        $this->aclService = $this->getService(AclService::class);
        $this->entryController = $this->getService(EntryController::class);
        $this->entryManager = $this->getService(EntryManager::class);
        $this->formManager = $this->getService(FormManager::class);
        $this->tripleStore = $this->getService(TripleStore::class);

        if (empty($this->arguments['id'])){
            return $this->renderAlert(_t('AUJ9_ID_PARAM_NOT_EMPTY'));
        }
        if (empty($this->arguments['fields'])){
            return $this->renderAlert(_t('AUJ9_FIELDS_PARAM_NOT_EMPTY'));
        }
        if (strval($this->arguments['id']) !== strval(intval($this->arguments['id'])) || intval($this->arguments['id']) <= 0){
            return $this->renderAlert(_t('AUJ9_ID_PARAM_SHOULD_BE_NUMBER'));
        }

        $error = (($_GET['message'] ?? '') === 'modif_ok') 
            ? $this->renderAlert(_t('BAZ_FICHE_MODIFIEE'),'success')
            : '';

        $form = $this->formManager->getOne($this->arguments['id']);

        if (empty($form['prepared'])){
            return $error.$this->renderAlert(_t('AUJ9_ID_PARAM_SHOULD_BE_A_FORM'));
        }

        $editableEntries = $this->entryManager->search(
            [
                'formsIds' => [$this->arguments['id']]
            ],
            true, // filterOnReadACL,
            true // useGuard
        );
        $editableEntries = array_filter(
            $editableEntries,
            function ($e) {
                return $this->aclService->hasAccess('write',$e['id_fiche']);
            }
        );

        $triple = $this->tripleStore->getOne($this->wiki->getPageTag(),'https://yeswiki.net/triple/EditEntryPartialParams','','');
        if (empty($triple) || (json_decode($triple,true)['sha1'] ?? '') != sha1("{$this->arguments['id']}-".implode(',',$this->arguments['fields']))){
            return $error.$this->renderAlert(_t('AUJ9_EDIT_ENTRY_PARTIAL_WRONG_PARAMS'));
        }

        if ($this->isPostingNewData()){
            $idFiche = filter_input(INPUT_POST,'id_fiche',FILTER_SANITIZE_STRING);
            $idFiche = empty($idFiche) ? '' : $idFiche;
            if (!empty($idFiche)){
                $editEntry = null;
                foreach($editableEntries as $entry){
                    if ($editEntry === null && $entry['id_fiche'] === $idFiche){
                        $editEntry = $entry;
                    }
                }
                if (!empty($editEntry)){
                    if (!empty($_POST['incomingurl'])){
                        if (empty($_GET['incomingurl'])){
                            $_GET['incomingurl'] = $_POST['incomingurl'];
                        }
                        unset($_POST['incomingurl']);
                    }
                    $this->entryController->update($idFiche);
                }
            }
            // something is wrong
            $error .= $this->renderAlert(_t('AUJ9_EDIT_PARTIAL_ENTRY_ERROR_REGISTER'));
        }

        $selectedEntryId = empty($idFiche)
            ? filter_input(INPUT_POST,'selectedEntryId',FILTER_SANITIZE_STRING)
            : empty($idFiche);
        $selectedEntryId = empty($selectedEntryId) ? filter_input(INPUT_GET,'selectedEntryId',FILTER_SANITIZE_STRING) : $selectedEntryId;
        $selectedEntryId = empty($selectedEntryId) ? '' : $selectedEntryId;
        $conf = [];
        foreach([
            'image-small-width','image-small-height',
            'image-medium-width','image-medium-height',
            'image-big-width','image-big-height',
            'password_for_editing'
            ] as $configName){
            if ($this->params->has($configName)){
                $conf[$configName] = $this->params->get($configName);
            }
        }

        $selectedEntry = null;
        if (!empty($selectedEntryId)){
            foreach($editableEntries as $entry){
                if ($selectedEntry === null && $entry['id_fiche'] === $selectedEntryId){
                    $selectedEntry = $entry;
                }
            }
        }

        $renderedInputs = empty($selectedEntryId)
            ? []
            : $this->getRenderedInputs($form,$this->arguments['fields'],$selectedEntry);

        return $this->render(
            '@alternativeupdatej9rem/edit-entry-partial-action.twig',
            compact(['editableEntries','form','selectedEntryId','renderedInputs','conf','error'])
        );
    }

    protected function isPostingNewData(): bool
    {
        return !empty($_POST['bf_titre']) && !empty($_POST['id_fiche']) && is_string($_POST['id_fiche']);
    }

    protected function renderAlert(string $message, string $type='danger'): string
    {
        return $this->render('@templates/alert-message.twig',[
            'type' => $type,
            'message' => $message
        ]);
    }

    private function getRenderedInputs($form, array $fieldsNames, $entry = null)
    {
        $renderedFields = [];
        foreach($this->arguments['fields'] as $wantedFieldName){
            foreach ($form['prepared'] as $field) {
                if ($field instanceof BazarField && in_array($wantedFieldName, [$field->getName(),$field->getPropertyName()])) {
                    $renderedFields[] = $field->renderInputIfPermitted($entry);
                }
            }
        }
        if (!in_array('bf_titre',$fieldsNames)){
            $titleFields = array_filter($form['prepared'],function($f){
                return $f instanceof BazarField && $f->getPropertyName() == 'bf_titre';
            });
            if (!empty($titleFields) && $titleFields[0] instanceof TitleField){
                $renderedFields[] = ($titleFields[0])->renderInputIfPermitted($entry);
            } else {
                $title = $entry['bf_titre'] ?? $entry['id_fiche'];
                $renderedFields[] = <<<HTML
                <input type="hidden" name="bf_titre" value="$title">
                HTML;
            }
        }
        return $renderedFields;
    }
}