<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-bazarlist-filter-order
 */

namespace YesWiki\Alternativeupdatej9rem\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Bazar\Field\BazarField;
use YesWiki\Bazar\Service\BazarListService as CoreBazarListService;

class BazarListService extends CoreBazarListService
{
    public function formatFilters($options, $entries, $forms): array
    {
        $params = $this->wiki->services->get(ParameterBagInterface::class);
        if (!$params->has('sortListAsDefinedInFilters') || $params->get('sortListAsDefinedInFilters') !== true) {
            return parent::formatFilters($options, $entries, $forms);
        }

        if (empty($options['groups'])) {
            return [];
        }

        // Scanne tous les champs qui pourraient faire des filtres pour les facettes
        $facettables = $this->formManager
                            ->scanAllFacettable($entries, $options['groups']);

        if (count($facettables) == 0) {
            return [];
        }

        if (!$forms) {
            $forms = $this->getForms($options);
        }
        $filters = [];
        // Récupere les facettes cochees
        $tabfacette = [];
        if (isset($_GET['facette']) && !empty($_GET['facette'])) {
            $tab = explode('|', $_GET['facette']);
            //découpe la requete autour des |
            foreach ($tab as $req) {
                $tabdecoup = explode('=', $req, 2);
                if (count($tabdecoup) > 1) {
                    $tabfacette[$tabdecoup[0]] = explode(',', trim($tabdecoup[1]));
                }
            }
        }

        foreach ($facettables as $id => $facettable) {
            $list = [];
            // Formatte la liste des resultats en fonction de la source
            if (in_array($facettable['type'], ['liste','fiche'])) {
                $field = $this->findFieldByName($forms, $facettable['source']);
                if (!($field instanceof BazarField)) {
                    if ($this->debug) {
                        trigger_error("Waiting field instanceof BazarField from findFieldByName, " .
                            (
                                (is_null($field)) ? 'null' : (
                                    (gettype($field) == "object") ? get_class($field) : gettype($field)
                                )
                            ) . ' returned');
                    }
                } elseif ($facettable['type'] == 'liste') {
                    $list['titre_liste'] = $field->getLabel();
                    $list['label'] = $field->getOptions();
                } elseif ($facettable['type'] == 'fiche') {
                    $formId = $field->getLinkedObjectName() ;
                    $form = $forms[$formId];
                    $list['titre_liste'] = $form['bn_label_nature'];
                    $list['label'] = [];
                    foreach ($facettable as $idfiche => $nb) {
                        if ($idfiche != 'source' && $idfiche != 'type') {
                            $f = $this->entryManager->getOne($idfiche);
                            if (!empty($f['bf_titre'])) {
                                $list['label'][$idfiche] = $f['bf_titre'];
                            }
                        }
                    }
                }
            } elseif ($facettable['type'] == 'form') {
                if ($facettable['source'] == 'id_typeannonce') {
                    $list['titre_liste'] = _t('BAZ_TYPE_FICHE');
                    foreach ($facettable as $idf => $nb) {
                        if ($idf != 'source' && $idf != 'type') {
                            $list['label'][$idf] = $forms[$idf]['bn_label_nature'] ?? $idf;
                        }
                    }
                } elseif ($facettable['source'] == 'owner') {
                    $list['titre_liste'] = _t('BAZ_CREATOR');
                    foreach ($facettable as $idf => $nb) {
                        if ($idf != 'source' && $idf != 'type') {
                            $list['label'][$idf] = $idf;
                        }
                    }
                } else {
                    $list['titre_liste'] = $id;
                    foreach ($facettable as $idf => $nb) {
                        if ($idf != 'source' && $idf != 'type') {
                            $list['label'][$idf] = $idf;
                        }
                    }
                }
            }

            $idkey = htmlspecialchars($id);

            $i = array_key_first(array_filter($options['groups'], function ($value) use ($idkey) {
                return ($value == $idkey) ;
            }));

            $filters[$idkey]['icon'] = !empty($options['groupicons'][$i]) ?
                    '<i class="' . $options['groupicons'][$i] . '"></i> ' : '';

            $filters[$idkey]['title'] = !empty($options['titles'][$i]) ?
                    $options['titles'][$i] : $list['titre_liste'];

            $filters[$idkey]['collapsed'] = ($i != 0) && !$options['groupsexpanded'];

            $filters[$idkey]['index'] = $i;

            # sort facette labels
            /* natcasesort($list['label']); // commented line because changing order */
            foreach ($list['label'] as $listkey => $label) {
                if (!empty($facettables[$id][$listkey])) {
                    $filters[$idkey]['list'][] = [
                        'id' => $idkey . $listkey,
                        'name' => $idkey,
                        'value' => htmlspecialchars($listkey),
                        'label' => $label,
                        'nb' => $facettables[$id][$listkey],
                        'checked' => (isset($tabfacette[$idkey]) and in_array($listkey, $tabfacette[$idkey])) ? ' checked' : '',
                    ];
                }
            }
        }

        // reorder $filters
        uasort($filters, function ($a, $b) {
            if (isset($a['index']) && isset($b['index'])) {
                if ($a['index'] == $b['index']) {
                    return 0 ;
                } else {
                    return ($a['index'] < $b['index']) ? -1 : 1 ;
                }
            } elseif (isset($a['index'])) {
                return 1 ;
            } elseif (isset($b['index'])) {
                return -1 ;
            } else {
                return 0 ;
            }
        });

        foreach ($filters as $id => $filter) {
            if (isset($filter['index'])) {
                unset($filter['index']) ;
            }
        }

        return $filters;
    }

    /*
     * Scan all forms and return the first field matching the given ID
     */
    private function findFieldByName($forms, $name)
    {
        foreach ($forms as $form) {
            foreach ($form['prepared'] as $field) {
                if ($field instanceof BazarField) {
                    if ($field->getPropertyName() === $name) {
                        return $field;
                    }
                }
            }
        }
    }
}
