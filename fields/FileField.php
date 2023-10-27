<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-recurrent-events
 */

namespace YesWiki\Alternativeupdatej9rem\Field;

use YesWiki\Alternativeupdatej9rem\Service\DateService;
use YesWiki\Bazar\Field\FileField as BazarFileField;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\Service\EventDispatcher;

trait redefineUpdateEntryAfterFileDelete
{
    protected function updateEntryAfterFileDelete($entry)
    {
        $entryManager = $this->services->get(EntryManager::class);

        // unset value in entry from db without modifier from GET
        $entryFromDb = $entryManager->getOne($entry['id_fiche']);
        if (!empty($entryFromDb)) {
            $previousGet = $_GET;
            $_GET = ['wiki' => $previousGet['wiki']];
            $previousPost = $_POST;
            $_POST= [];
            $previousRequest = $_REQUEST;
            $_REQUEST = [];
            unset($entryFromDb[$this->propertyName]); // remove current field
            if (isset($entryFromDb['bf_date_fin_evenement_data']) && is_string($entryFromDb['bf_date_fin_evenement_data'])){
                unset($entryFromDb['bf_date_fin_evenement_data']); // remove links to parent
            }
            $entryFromDb['antispam'] = 1;
            $entryFromDb['date_maj_fiche'] = date('Y-m-d H:i:s', time());
            $newEntry = $entryManager->update($entryFromDb['id_fiche'], $entryFromDb, false, true);

            $_GET = $previousGet;
            $_POST = $previousPost;
            $_REQUEST = $previousRequest;

            
            // be careful to recurrence
            
            if (!empty($newEntry['id_fiche'])
                && is_string($newEntry['id_fiche'])
                && isset($newEntry['bf_date_fin_evenement'])){
                $this->getService(DateService::class)->followId($newEntry['id_fiche']);
            }

            $errors = $this->services->get(EventDispatcher::class)->yesWikiDispatch('entry.updated', [
                'id' => $newEntry['id_fiche'],
                'data' => $newEntry
            ]);
        }
    }
}

/**
 * @Field({"fichier"})
 */
class FileField extends BazarFileField
{
    use redefineUpdateEntryAfterFileDelete;
}
