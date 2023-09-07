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

use Psr\Container\ContainerInterface;
use YesWiki\Bazar\Field\LinkedEntryField as RealLinkedEntryField;
use YesWiki\Core\Service\Performer;
use YesWiki\Templates\Service\TabsService;

/**
 * not needed since 4.4.1
 */

/**
 * @Field({"listefichesliees", "listefiches"})
 */
class LinkedEntryField extends RealLinkedEntryField
{
    protected const FIELD_LABEL = 7;
    public function __construct(array $values, ContainerInterface $services)
    {
        parent::__construct($values, $services);
        $this->label = $values[self::FIELD_LABEL] ?? '';
    }

    protected function renderInput($entry)
    {
        // Display the linked entries only on update
        if (isset($entry['id_fiche'])) {
            $output = $this->renderSecuredBazarList($entry);
            return $this->isEmptyOutput($output)
                ? $output
                : $this->render('@bazar/inputs/linked-entry.twig',compact(['output']));
        }
    }

    protected function renderStatic($entry)
    {
        // Display the linked entries only if id_fiche and id_typeannonce
        if (!empty($entry['id_fiche']) && !empty($entry['id_typeannonce'])) {
            $output = $this->renderSecuredBazarList($entry);
            return $this->isEmptyOutput($output)
                ? $output
                : $this->render('@bazar/fields/linked-entry.twig',compact(['output']));
        } else {
            return "" ;
        }
    }

    protected function isEmptyOutput(string $output): bool
    {
        return empty($output) || preg_match('/<div id="[^"]+" class="bazar-list[^"]*"[^>]*>\s*<div class="list"><\/div>\s*<\/div>/',$output);
    }

    protected function renderSecuredBazarList($entry): string
    {
        if ($this->getWiki()->services->has(TabsService::class)){
            $tabsService = $this->getService(TabsService::class);
            if (method_exists($tabsService,'saveState')){
                $index = $tabsService->saveState();
            } else {
                unset($tabsService);
            }
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
