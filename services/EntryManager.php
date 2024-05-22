<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-fix-4-4-3
 */

namespace YesWiki\Alternativeupdatej9rem\Service;

use YesWiki\Bazar\Service\FormManager;
use YesWiki\Bazar\Service\EntryManager as BazarEntryManager;
use YesWiki\Bazar\Service\Guard;
use YesWiki\Wiki;

class EntryManager extends BazarEntryManager
{
    /**
     * Return an array of fiches based on search parameters
     * @param array $params
     * @param bool $filterOnReadACL
     * @param bool $useGuard
     * @param bool $appendHtmlData
     * @return mixed
     */
    public function search(
        $params = [],
        bool $filterOnReadACL = false,
        bool $useGuard = false //,
        //bool $appendHtmlData = true
    ): array {
        $parentBacktrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $appendHtmlData = empty($appendHtmlData[1]['function'])
            || $appendHtmlData[1]['function'] != 'loadOptionsFromEntries'
            || empty($appendHtmlData[1]['class'])
            || substr($appendHtmlData[1]['class'], -strlen('EnumField')) != 'EnumField';
        $requete = $this->prepareSearchRequest($params, $filterOnReadACL);
        $searchResults = array();
        $results = $this->dbService->loadAll($requete);
        $debug = ($this->wiki->GetConfigValue('debug') == 'yes');
        foreach ($results as $page) {
            // save owner to reduce sql calls
            $this->pageManager->cacheOwner($page);
            // not possible to init the Guard in the constructor because of circular reference problem
            $filteredPage = (!$this->wiki->UserIsAdmin() && $useGuard)
                ? $this->wiki->services->get(Guard::class)->checkAcls($page, $page['tag'])
                : $page;
            $data = $this->getDataFromPage($filteredPage, false, $debug, $params['correspondance'], $appendHtmlData);
            $searchResults[$data['id_fiche']] = $data;
        }
        return $searchResults;
    }

    /** getDataFromPage
     * @param array $page , content of page from sql
     * @param bool $semantic
     * @param bool $debug, to throw exception in case of error
     * @param string $correspondance, to pass correspondance parameter directly to appendDisplayData
     * @param bool $appendHtmlData
     *
     * @return array data formated
     */
    private function getDataFromPage(
        $page,
        bool $semantic = false,
        bool $debug = false,
        string $correspondance = '',
        bool $appendHtmlData = true
    ): array {
        $data = [];
        if (!empty($page['body'])) {
            $data = $this->decode($page['body']);

            if ($debug) {
                if (empty($data['id_fiche'])) {
                    trigger_error('empty \'id_fiche\' in EntryManager::getDataFromPage in body of page \''
                        . $page['tag'] . '\'. Edit it to create id_fiche', E_USER_WARNING);
                }
                if (empty($page['tag'])) {
                    trigger_error('empty $page[\'tag\'] in EntryManager::getDataFromPage! ', E_USER_WARNING);
                }
            }

            // cas ou on ne trouve pas les valeurs id_fiche
            if (!isset($data['id_fiche'])) {
                $data['id_fiche'] = $page['tag'];
            }
            // TODO call this function only when necessary
            if ($appendHtmlData) {
                $this->appendDisplayData($data, $semantic, $correspondance, $page);
            }
        } elseif ($debug) {
            trigger_error('empty \'body\'  in EntryManager::getDataFromPage for page \'' . ($page['tag'] ?? '!!empty tag!!') . '\'', E_USER_WARNING);
        }

        return $data;
    }

    /**
     * Return the request for searching entries in database
     * @param array &$params
     * @param bool $filterOnReadACL
     * @param bool $applyOnAllRevisions
     * @return $string
     */
    private function prepareSearchRequest(&$params = [], bool $filterOnReadACL = false, bool $applyOnAllRevisions = false): string
    {
        // Merge les paramètres passé avec des paramètres par défaut
        $params = array_merge(
            [
                'queries' => '', // Sélection par clé-valeur
                'formsIds' => [], // Types de fiches (par ID de formulaire)
                'user' => '', // N'affiche que les fiches d'un utilisateur
                'keywords' => '', // Mots-clés pour la recherche fulltext
                'searchOperator' => 'OR', // Opérateur à appliquer aux mots-clés
                'minDate' => '', // Date minimale des fiches
                'correspondance' => ''
            ],
            $params
        );

        // requete pour recuperer toutes les PageWiki etant des fiches bazar
        // TODO refactor to use the TripleStore service
        $requete_pages_wiki_bazar_fiches =
            'SELECT DISTINCT resource FROM ' . $this->dbService->prefixTable('triples') .
            'WHERE value = "fiche_bazar" AND property = "http://outils-reseaux.org/_vocabulary/type" ' .
            'ORDER BY resource ASC';

        $requete =
            'SELECT DISTINCT * FROM ' . $this->dbService->prefixTable('pages') .
            'WHERE ' . ($applyOnAllRevisions ? '' : 'latest="Y" AND ') . ' comment_on = \'\'';

        // On limite au type de fiche
        if (!empty($params['formsIds'])) {
            if (is_array($params['formsIds'])) {
                $requete .= ' AND (' . join(' OR ', array_map(function ($formId) {
                    return 'body LIKE \'%"id_typeannonce":"' . $this->dbService->escape(strval($formId)) . '"%\'';
                }, array_filter(
                    $params['formsIds'],
                    'is_scalar'
                ))) . ') ';
            } elseif (is_scalar($params['formsIds'])) {
                // on a une chaine de caractere pour l'id plutot qu'un tableau
                $requete .= ' AND body LIKE \'%"id_typeannonce":"' . $this->dbService->escape(strval($params['formsIds'])) . '"%\'';
            }
        }

        // periode de modification
        if (!empty($params['minDate'])) {
            $requete .= ' AND time >= "' . mysqli_real_escape_string($this->wiki->dblink, $params['minDate']) . '"';
        }

        // si une personne a ete precisee, on limite la recherche sur elle
        if (!empty($params['user'])) {
            $requete .= ' AND owner = _utf8\'' . mysqli_real_escape_string($this->wiki->dblink, $params['user']) . '\'';
        }

        $requete .= ' AND tag IN (' . $requete_pages_wiki_bazar_fiches . ')';

        $requeteSQL = '';

        //preparation de la requete pour trouver les mots cles
        if (is_string($params['keywords']) && trim($params['keywords']) != '' && $params['keywords'] != _t('BAZ_MOT_CLE')) {
            $needles = $this->searchManager->searchWithLists($params['keywords'], $this->getFormsFromIds($param['formsIds'] ?? null));
            if (!empty($needles)) {
                $first = true;
                // generate search
                foreach ($needles as $needle => $results) {
                    if ($first) {
                        $first = false;
                    } else {
                        $requeteSQL .= ' AND ';
                    }
                    $requeteSQL .= '(';
                    // add standard search
                    $search = $this->convertToRawJSONStringForREGEXP($needle);
                    $search = str_replace('_', '\\_', $search);
                    $requeteSQL .= ' body REGEXP \'' . $search . '\'';
                    // add search in list
                    // $results is an array not empty only if list
                    foreach ($results as $result) {
                        $requeteSQL .= ' OR ';
                        if (!$result['isCheckBox']) {
                            $requeteSQL .= ' body LIKE \'%"' . str_replace('_', '\\_', $result['propertyName']) . '":"' . str_replace("'", "\\'", $result['key']) . '"%\'';
                        } else {
                            $requeteSQL .= ' body REGEXP \'"' . str_replace('_', '\\_', $result['propertyName']) . '":(' .
                                '"' . $result['key'] . '"' .
                                '|"[^"]*,' . $result['key'] . '"' .
                                '|"' . $result['key'] . ',[^"]*"' .
                                '|"[^"]*,' . $result['key'] . ',[^"]*"' .
                                ')\'';
                        }
                    }
                    $requeteSQL .= ')';
                }
                if (!empty($requeteSQL)) {
                    $requeteSQL = ' AND (' . $requeteSQL . ')';
                }
            }
        }

        //on ajoute dans la requete les valeurs passees dans les champs liste et checkbox du moteur de recherche
        if ($params['queries'] == '') {
            $params['queries'] = array();

            // on transforme les specifications de recherche sur les liste et checkbox
            if (isset($_REQUEST['rechercher'])) {
                reset($_REQUEST);

                foreach ($_REQUEST as $nom => $val) {
                    if (((substr($nom, 0, 5) == 'liste') || (substr($nom, 0, 8) ==
                                'checkbox')) && $val != '0' && $val != '') {
                        if (is_array($val)) {
                            $val = implode(',', array_keys($val));
                        }
                        $params['queries'][$nom] = $val;
                    }
                }
            }
        }

        foreach ($params['queries'] as $nom => $val) {
            if (!empty($nom)) {
                $nom = $this->convertToRawJSONStringForREGEXP($nom);
                // sanitize $nom to prevent REGEXP SQL errors
                $nom = preg_replace("/(?<=^|\?|\*|\+)(\?|\*|\+)/m", "\\\\\\\\$1", $nom);
                if (!in_array($val, [false,null,""], true)) {
                    $valcrit = explode(',', $val);
                    if (is_array($valcrit) && count($valcrit) > 1) {
                        $requeteSQL .= ' AND ';
                        if (substr($nom, -1) == '!') {
                            $requeteSQL .= ' NOT ';
                            $nom = substr($nom, 0, -1);
                        }
                        $requeteSQL .= '(';
                        $first = true;
                        foreach ($valcrit as $critere) {
                            $rawCriteron = $this->convertToRawJSONStringForREGEXP($critere);
                            if (!$first) {
                                $requeteSQL .= ' ' . $params['searchOperator'] . ' ';
                            }

                            if (strcmp(substr($nom, 0, 5), 'liste') == 0) {
                                $requeteSQL .=
                                    'body REGEXP \'"' . $nom . '":"' . $rawCriteron . '"\'';
                            } else {
                                $requeteSQL .=
                                    'body REGEXP \'"' . $nom . '":("' . $rawCriteron .
                                    '"|"[^"]*,' . $rawCriteron . '"|"' . $rawCriteron . ',[^"]*"|"[^"]*,'
                                    . $rawCriteron . ',[^"]*")\'';
                            }

                            $first = false;
                        }
                        $requeteSQL .= ')';
                    } else {
                        $rawCriteron = $this->convertToRawJSONStringForREGEXP($val);
                        if (strcmp(substr($nom, 0, 5), 'liste') == 0) {
                            $requeteSQL .= ' AND ';
                            if (substr($nom, -1) == '!') {
                                $requeteSQL .= ' NOT ';
                                $nom = substr($nom, 0, -1);
                            }
                            $requeteSQL .= '(body REGEXP \'"' . $nom . '":"' . $rawCriteron . '"\')';
                        } else {
                            $requeteSQL .= ' AND ';
                            if (substr($nom, -1) == '!') {
                                $requeteSQL .= ' NOT ';
                                $nom = substr($nom, 0, -1);
                            }
                            $requeteSQL .= '(body REGEXP \'"' . $nom . '":("' . $rawCriteron .
                                '"|"[^"]*,' . $rawCriteron . '"|"' . $rawCriteron . ',[^"]*"|"[^"]*,'
                                . $rawCriteron . ',[^"]*")\')';
                        }
                    }
                } else {
                    $requeteSQL .= ' AND ';
                    if (substr($nom, -1) == '!') {
                        $requeteSQL .= ' NOT ';
                        $nom = substr($nom, 0, -1);
                    }
                    $requeteSQL .= '(body REGEXP \'"' . $nom . '":""\' ' .
                        'OR NOT (body REGEXP \'"' . $nom . '":"[^"][^"]*"\'))';
                }
            }
        }

        // requete de jointure : reprend la requete precedente et ajoute des criteres
        if (isset($_GET['joinquery'])) {
            $join = $this->dbService->escape($_GET['joinquery']);
            $joinrequeteSQL = '';
            $tableau = array();
            $tab = explode('|', $join);
            //découpe la requete autour des |
            foreach ($tab as $req) {
                $tabdecoup = explode('=', $req, 2);
                $tableau[$tabdecoup[0]] = trim($tabdecoup[1]);
            }
            $first = true;

            foreach ($tableau as $nom => $val) {
                if (!empty($nom) && !empty($val)) {
                    $valcrit = explode(',', $val);
                    if (is_array($valcrit) && count($valcrit) > 1) {
                        foreach ($valcrit as $critere) {
                            if (!$first) {
                                $joinrequeteSQL .= ' AND ';
                            } else {
                                $first = false;
                            }
                            $rawCriteron = $this->convertToRawJSONStringForREGEXP($critere);
                            $joinrequeteSQL .=
                                '(body REGEXP \'"' . $nom . '":"[^"]*' . $rawCriteron .
                                '[^"]*"\')';
                        }
                        $joinrequeteSQL .= ')';
                    } else {
                        if (!$first) {
                            $joinrequeteSQL .= ' AND ';
                        } else {
                            $first = false;
                        }
                        $rawCriteron = $this->convertToRawJSONStringForREGEXP($val);
                        if (strcmp(substr($nom, 0, 5), 'liste') == 0) {
                            $joinrequeteSQL .=
                                '(body REGEXP \'"' . $nom . '":"' . $rawCriteron . '"\')';
                        } else {
                            $joinrequeteSQL .=
                                '(body REGEXP \'"' . $nom . '":("' . $rawCriteron .
                                '"|"[^"]*,' . $rawCriteron . '"|"' . $rawCriteron . ',[^"]*"|"[^"]*,'
                                . $rawCriteron . ',[^"]*")\')';
                        }
                    }
                }
            }
            if ($requeteSQL != '') {
                $requeteSQL .= ' UNION ' . $requete . ' AND (' . $joinrequeteSQL . ')';
            } else {
                $requeteSQL .= ' AND (' . $joinrequeteSQL . ')';
            }
            $requete .= $requeteSQL;
        } elseif ($requeteSQL != '') {
            $requete .= $requeteSQL;
        }

        // $filterOnReadACL
        if (!$this->wiki->UserIsAdmin() && $filterOnReadACL) {
            $requete .= $this->aclService->updateRequestWithACL();
        }

        // debug
        if (isset($_GET['showreq'])) {
            echo '<hr><code style="width:100%;height:100px;">' . $requete . '</code><hr>';
        }

        return $requete;
    }

    /** format data as in sql
     * @param string $rawValue
     * @return string $formatedValue
     */
    private function convertToRawJSONStringForREGEXP(string $rawValue): string
    {
        $valueJSON = substr(json_encode($rawValue), 1, strlen(json_encode($rawValue)) - 2);
        $formattedValue = str_replace(['\\','\''], ['\\\\','\\\''], $valueJSON);
        return $this->dbService->escape($formattedValue);
    }

    /**
     * sanitize formsIds and get forms
     * @param mixed $formsIds
     * @return array $forms
     */
    private function getFormsFromIds($formsIds): array
    {
        $formManager = $this->wiki->services->get(FormManager::class); // not load in contruct to prevent circular loading
        if (!empty($formsIds)) {
            if (is_scalar($formsIds)) {
                $formsIds = [$formsIds];
            }
            if (is_array($formsIds)) {
                $formsIds = array_filter($formsIds, function ($formId) {
                    return is_scalar($formId) && (strval(intval($formId)) == strval($formId));
                });
            } else {
                $formsIds = null;
            }
        }
        if (!empty($formsIds)) {
            return $formManager->getMany($formsIds);
        } else {
            return $formManager->getAll();
        }
    }
}
