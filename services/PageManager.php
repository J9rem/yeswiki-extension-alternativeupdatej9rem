<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-fix-edit-metadata
 */

namespace YesWiki\Alternativeupdatej9rem\Service;

use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\Service\PageManager as CorePageManager;
use YesWiki\Core\Service\ThemeManager;

class PageManager extends CorePageManager
{
    public function setMetadata($tag, $metadata)
    {
        $previousMetadata = $this->getMetadata($tag);
        if (!$this->aclService->hasAccess('read', $tag)
            || !$this->aclService->hasAccess('write', $tag)
            || (
                !empty($_GET['newpage'])
                && (
                    empty($metadata['forceSave'])
                    || $metadata['forceSave'] !== true
                )
            )) {
            return 0;
        }

        if(isset($metadata['forceSave'])) {
            unset($metadata['forceSave']);
        }

        return parent::setMetadata($tag, $metadata);
    }

    public function deleteOrphaned($tag)
    {
        parent::deleteOrphaned($tag);
        $this->dbService->query(<<<SQL
        DELETE FROM {$this->dbService->prefixTable('triples')}
          WHERE `resource`='{$this->dbService->escape($tag)}'
            and `property`='http://outils-reseaux.org/_vocabulary/metadata';
        SQL);
    }

    /**
     * SavePage
     * Sauvegarde un contenu dans une page donnee
     *
     * @param string $body
     *            Contenu a sauvegarder dans la page
     * @param string $tag
     *            Nom de la page
     * @param string $comment_on
     *            Indication si c'est un commentaire
     * @param boolean $bypass_acls
     *            Indication si on bypasse les droits d'ecriture
     * @return int Code d'erreur : 0 (succes), 1 (l'utilisateur n'a pas les droits)
     */
    public function save($tag, $body, $comment_on = "", $bypass_acls = false)
    {
        // is page new?
        $oldPage = $this->getOne($tag);
        if (parent::save($tag, $body, $comment_on, $bypass_acls) === 0) {
            $previousMetadata = $this->getMetadata($tag);
            if (!$this->wiki->services->get(EntryManager::class)->isEntry($tag)
                && !empty($_POST["newpage"])
                && empty($oldPage) // only new page
                && (
                    (!empty($_GET['wiki']) && $_GET['wiki'] === $tag)
                    ||
                    explode('/', array_key_first($_GET), 2)[0] === $tag
                )
                && $this->wiki->tag === $tag // be sure to be on right tag
                && isset($_POST['theme'])
                && empty($previousMetadata) // only of no previous metadata
            ) {
                // imported from __edit
                $metadata = [
                    'theme' => $_POST["theme"],
                    'style' => $_POST["style"] ?? CSS_PAR_DEFAUT ,
                    'squelette' => $_POST["squelette"] ?? SQUELETTE_PAR_DEFAUT ,
                    'bgimg' => $_POST["bgimg"] ?? null
                ];
                foreach (ThemeManager::SPECIAL_METADATA as $metadataName) {
                    if (!empty($_POST[$metadataName])) {
                        $metadata[$metadataName] = $_POST[$metadataName];
                    }
                }
                $this->setMetadata($tag, array_merge($metadata, ['forceSave'=>true]));
            }
            return 0;
        }
        return 1;
    }
}
