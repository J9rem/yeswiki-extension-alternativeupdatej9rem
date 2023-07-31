<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YesWiki\Alternativeupdatej9rem\Field;

use YesWiki\Bazar\Field\LinkedEntryField as RealLinkedEntryField;
use YesWiki\Core\Service\Performer;
use YesWiki\Templates\Service\TabsService;

/**
 * @Field({"listefichesliees", "listefiches"})
 */
class LinkedEntryField extends RealLinkedEntryField
{
    protected function renderInput($entry)
    {
        // Display the linked entries only on update
        if (isset($entry['id_fiche'])) {
            return $this->renderSecuredBazarList($entry);
        }
    }

    protected function renderStatic($entry)
    {
        // Display the linked entries only if id_fiche and id_typeannonce
        if (!empty($entry['id_fiche']) && !empty($entry['id_typeannonce'])) {
            return $this->renderSecuredBazarList($entry);
        } else {
            return "" ;
        }
    }

    protected function renderSecuredBazarList($entry): string
    {
        if ($this->getWiki()->services->has(TabsService::class)){
            $tabsService = $this->getService(TabsService::class);
            $index = $tabsService->saveState();
        }
        $output = $this->getService(Performer::class)->run('wakka', 'formatter', ['text' => $this->getBazarListAction($entry)]);
        if (isset($tabsService)){
            $tabsService->resetState($index);
        }
        return $output;
    }

    private function getBazarListAction($entry)
    {
        $query = $this->getQueryForLinkedLabels($entry) ;
        if (!empty($query)) {
            $query = ((!empty($this->query)) ? $this->query.  '|' : '')  . $query  ;

            return '{{bazarliste id="' . $this->name . '" query="' . $query . '"'
                . ((!empty($this->limit)) ? ' nb="' . $this->limit .'"' : '')
                . ((!empty(trim($this->template))) ? ' template="' . trim($this->template) . '" ' : '')
                . $this->otherParams . '}}';
        } else {
            return '';
        }
    }
}
