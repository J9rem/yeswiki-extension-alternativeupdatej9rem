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
use YesWiki\Bazar\Field\EnumField;
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

    /** Use bazarlist options like groups, titles, groupicons, groupsexpanded
     * To create a filters array to be used by the view
     * Note for [old-non-dynamic-bazarlist] For old bazarlist, most of the calculation happens on the backend
     *  But with the new dynamic bazalist, everything is done on the front
     * */
    public function getFilters($options, $entries, $forms): array
    {
        $params = $this->wiki->services->get(ParameterBagInterface::class);
        if (!$params->has('sortListAsDefinedInFilters') || $params->get('sortListAsDefinedInFilters') !== true) {
            return parent::getFilters($options, $entries, $forms);
        }
        // add default options
        $options = array_merge([
            'groups' => [],
            'dynamic' => true,
            'groupsexpanded' => false,
        ], $options);

        $formIdsUsed = array_unique(array_column($entries, 'id_typeannonce'));
        $formsUsed = array_map(function ($formId) use ($forms) { return $forms[$formId]; }, $formIdsUsed);
        $allFields = array_merge(...array_column($formsUsed, 'prepared'));

        $propNames = $options['groups'];
        // Special value groups=all use all available Enum fields
        if (count($propNames) == 1 && $propNames[0] == 'all') {
            $enumFields = array_filter($allFields, function ($field) {
                return $field instanceof EnumField;
            });
            $propNames = array_map(function ($field) { return $field->getPropertyName(); }, $enumFields);
        }

        $filters = [];

        foreach ($propNames as $index => $propName) {
            // Create a filter object to be returned to the view
            $filter = [
                'propName' => $propName,
                'title' => '',
                'icon' => '',
                'nodes' => [],
                'collapsed' => true,
            ];

            // Check if an existing Form Field existing by this propName
            foreach ($allFields as $aField) {
                if ($aField->getPropertyName() == $propName) {
                    $field = $aField;
                    break;
                }
            }
            // Depending on the propName, get the list of filter nodes
            if (!empty($field) && $field instanceof EnumField) {
                // ENUM FIELD
                $filter['title'] = $field->getLabel();

                if (!empty($field->getOptionsTree()) && $options['dynamic'] == true) {
                    // OptionsTree only supported by bazarlist dynamic
                    foreach ($field->getOptionsTree() as $node) {
                        $filter['nodes'][] = $this->recursivelyCreateNode($node);
                    }
                } else {
                    foreach ($field->getOptions() as $value => $label) {
                        $filter['nodes'][] = $this->createFilterNode($value, $label);
                    }
                }
            } elseif ($propName == 'id_typeannonce') {
                // SPECIAL PROPNAME id_typeannonce
                $filter['title'] = _t('BAZ_TYPE_FICHE');
                foreach ($formsUsed as $form) {
                    $filter['nodes'][] = $this->createFilterNode($form['bn_id_nature'], $form['bn_label_nature']);
                }
                // comment this part to not sort on label
                // usort($filter['nodes'], function ($a, $b) { return strcmp($a['label'], $b['label']); });
            } else {
                // OTHER PROPNAME (for example a field that is not an Enum)
                $filter['title'] = $propName == 'owner' ? _t('BAZ_CREATOR') : $propName;
                // We collect all values
                $uniqValues = array_unique(array_column($entries, $propName));
                // comment this part to not sort on label
                // sort($uniqValues);
                foreach ($uniqValues as $value) {
                    $filter['nodes'][] = $this->createFilterNode($value, $value);
                }
            }

            // Filter Icon
            if (!empty($options['groupicons'][$index])) {
                $filter['icon'] = '<i class="' . $options['groupicons'][$index] . '"></i> ';
            }
            // Custom title
            if (!empty($options['titles'][$index])) {
                $filter['title'] = $options['titles'][$index];
            }
            // Initial Collapsed state
            $filter['collapsed'] = ($index != 0) && !$options['groupsexpanded'];

            // [old-non-dynamic-bazarlist] For old bazarlist, most of the calculation happens on the backend
            if ($options['dynamic'] == false) {
                $checkedValues = $this->parseCheckedFiltersInURLForNonDynamic();
                // Calculate the count for each filterNode
                $entriesValues = array_column($entries, $propName);
                // convert string values to array
                $entriesValues = array_map(function ($val) { return explode(',', $val); }, $entriesValues);
                // flatten the array
                $entriesValues = array_merge(...$entriesValues);
                $countedValues = array_count_values($entriesValues);
                $adjustedNodes = [];
                foreach ($filter['nodes'] as $rootNode) {
                    $adjustedNodes[] = $this->recursivelyInitValuesForNonDynamic($rootNode, $propName, $countedValues, $checkedValues);
                }
                $filter['nodes'] = $adjustedNodes;
            }

            $filters[] = $filter;
        }

        return $filters;
    }

    // [old-non-dynamic-bazarlist] filters state in stored in URL
    // ?Page&facette=field1=3,4|field2=web
    // => ['field1' => ['3', '4'], 'field2' => ['web']]
    private function parseCheckedFiltersInURLForNonDynamic()
    {
        if (empty($_GET['facette'])) {
            return [];
        }
        $result = [];
        foreach (explode('|', $_GET['facette']) as $field) {
            list($key, $values) = explode('=', $field);
            $result[$key] = explode(',', trim($values));
        }

        return $result;
    }

    private function createFilterNode($value, $label)
    {
        return [
            'value' => htmlspecialchars($value),
            'label' => $label,
            'children' => [],
        ];
    }

    private function recursivelyCreateNode($node)
    {
        $result = $this->createFilterNode($node['id'], $node['label']);
        foreach ($node['children'] as $childNode) {
            $result['children'][] = $this->recursivelyCreateNode($childNode);
        }

        return $result;
    }

    private function recursivelyInitValuesForNonDynamic($node, $propName, $countedValues, $checkedValues)
    {
        $result = array_merge($node, [
            'id' => $propName . $node['value'],
            'name' => $propName,
            'count' => $countedValues[$node['value']] ?? 0,
            'checked' => isset($checkedValues[$propName]) && in_array($node['value'], $checkedValues[$propName]) ? ' checked' : '',
        ]);

        foreach ($node['children'] as &$childNode) {
            $result['children'][] = $this->recursivelyInitValuesForNonDynamic($childNode, $propName, $countedValues, $checkedValues);
        }

        return $result;
    }
}
