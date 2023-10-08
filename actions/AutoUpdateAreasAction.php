<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-autoupdateareas-action
 */

namespace YesWiki\Alternativeupdatej9rem;

use Configuration;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Bazar\Service\ListManager;
use YesWiki\Core\Controller\CsrfTokenController;
use YesWiki\Core\YesWikiAction;
use YesWiki\Security\Controller\SecurityController;

class AutoUpdateAreasAction extends YesWikiAction
{
    public const FRENCH_DEPARTMENTS_TITLE = "Départements français";
    public const FRENCH_DEPARTMENTS_LIST_NAME = "ListeDepartementsFrancais";
    public const FRENCH_AREAS_TITLE = "Régions françaises";
    public const FRENCH_AREAS_LIST_NAME = "ListeRegionsFrancaises";

    protected const GET_KEY = 'appendObject';
    protected const ANTI_CSRF_TOKEN = 'autoupdateareas\\action\\{type}';
    protected const ANTI_CSRF_TOKEN_KEY = 'token';
    
    protected $csrfTokenController;
    protected $csrfTokenManager;
    protected $entryManager;
    protected $formManager;
    protected $listManager;
    protected $securityController;

    public function formatArguments($arg)
    {
        return [
            'type' => (!empty($arg['type']) && in_array($arg['type'],["departments","areas","form"]))
                ? $arg['type']
                : ""
        ];
    }

    public function run()
    {
        // get services
        $this->csrfTokenController = $this->getService(CsrfTokenController::class);
        $this->csrfTokenManager = $this->getService(CsrfTokenManager::class);
        $this->entryManager = $this->getService(EntryManager::class);
        $this->formManager = $this->getService(FormManager::class);
        $this->listManager = $this->getService(ListManager::class);
        $this->securityController = $this->getService(SecurityController::class);

        if (!$this->wiki->UserIsAdmin()){
            return $this->render("@templates/alert-message.twig",[
                'type' => 'danger',
                'message' => _t('AUJ9_AUTOUPDATE_AREAS_RESERVED_TO_ADMIN')
            ]);
        } elseif ($this->securityController->isWikiHibernated()) {
            return $this->render("@templates/alert-message.twig",[
                'type' => 'danger',
                'message' => _t('WIKI_IN_HIBERNATION')
            ]);
        }
        switch ($this->arguments['type']) {
            case 'departments':
                $listName = _t('AUJ9_AUTOUPDATE_AREAS_OF_DEPARTEMENTS');
                break;
            case 'areas':
                $listName = _t('AUJ9_AUTOUPDATE_AREAS_OF_AREAS');
                break;
            case 'form':
                $listName = _t('AUJ9_AUTOUPDATE_AREAS_FORM');
                break;
            
            default:
                return $this->render("@templates/alert-message.twig",[
                    'type' => 'warning',
                    'message' => 'Parameter `type` should be defined for action `{{autoupdateareas}}`!'
                ]);
        }
        
        // add List if not existing
        if (!empty($_GET[self::GET_KEY]) &&
            is_string($_GET[self::GET_KEY])){
            return $this->render("@templates/alert-message.twig",$this->addListIfNotExisting($_GET[self::GET_KEY]));
        }

        $text = _t('AUJ9_AUTOUPDATE_AREAS_TEXT',[
            'listName' => $listName
        ]);
        $token = $this->csrfTokenManager->getToken(str_replace('{type}',$this->arguments['type'],self::ANTI_CSRF_TOKEN))->getValue();
        return $this->callAction('button',[
            'link' => $this->wiki->Href('render',null,[
                'content' => "{{autoupdateareas type=\"{$this->arguments['type']}\"}}",
                self::GET_KEY => $this->arguments['type'],
                self::ANTI_CSRF_TOKEN_KEY => $token
            ],false),
            'text' => $text,
            'title' => $text,
            'class' => 'btn-secondary-2 new-window'
        ]);
    }

    protected function addListIfNotExisting(string $appendCustomSendMailObject): array
    {
        $output = '<b>Mise à jour automatique d\'une liste ou d\'un formulaire</b></br>';
        try {
            $this->csrfTokenController->checkToken(str_replace('{type}',$appendCustomSendMailObject,self::ANTI_CSRF_TOKEN), 'GET', self::ANTI_CSRF_TOKEN_KEY);
        } catch (TokenNotFoundException $th) {
            return [
                'type' => 'danger',
                'message' => "$output&#10060; not possible to update an object : '{$th->getMessage()}' !"
            ];
        }
        switch ($appendCustomSendMailObject) {
            case 'departments':
                $output .= "ℹ️ Updating list of departments... ";
                extract($this->createDepartements());
                if (!$success) {
                    $output .= "&#10060; $error<br/>";
                    return ['type' => 'danger','message' => $output];
                }
                break;
            case 'areas':
                $output .= "ℹ️ Updating list of areas... ";
                extract($this->createAreas());
                if (!$success) {
                    $output .= "&#10060; $error<br/>";
                    return ['type' => 'danger','message' => $output];
                }
                break;
            case 'form':
                $output .= "ℹ️ Updating form associating areas and departments... ";

                extract($this->createFormToAssociateAreasAndDepartments());
                if (!$success) {
                    $output .= "&#10060; $error<br/>";
                    return ['type' => 'danger','message' => $output];
                }
                break;
            default:
                $output .= "&#10060; not possible to update an object : type '$appendCustomSendMailObject' is unknown !<br/>";
                return ['type' => 'danger','message' => $output];
        }

        $output .= '✅ Done !<br />';

        return ['type' => 'success','message' => $output];
    }

    
    /**
     * @return array ['success' => bool, 'error' => string]
     */
    protected function createDepartements(): array
    {
        $success = false;
        $error = '';

        $list = $this->listManager->getOne(self::FRENCH_DEPARTMENTS_LIST_NAME);
        if (!empty($list)) {
            $error = "not possible to create list of departments : '".self::FRENCH_DEPARTMENTS_LIST_NAME."' is alreadyExisting !";
            $success = false;
        } else {
            $this->listManager->create(self::FRENCH_DEPARTMENTS_TITLE, [
                "1"=> "Ain",
                "2"=> "Aisne",
                "3"=> "Allier",
                "4"=> "Alpes-de-Haute-Provence",
                "5"=> "Hautes-Alpes",
                "6"=> "Alpes-Maritimes",
                "7"=> "Ardèche",
                "8"=> "Ardennes",
                "9"=> "Ariège",
                "10"=> "Aube",
                "11"=> "Aude",
                "12"=> "Aveyron",
                "13"=> "Bouches-du-Rhône",
                "14"=> "Calvados",
                "15"=> "Cantal",
                "16"=> "Charente",
                "17"=> "Charente-Maritime",
                "18"=> "Cher",
                "19"=> "Corrèze",
                "2A"=> "Corse-du-Sud",
                "2B"=> "Haute-Corse",
                "21"=> "Côte-d'Or",
                "22"=> "Côtes-d'Armor",
                "23"=> "Creuse",
                "24"=> "Dordogne",
                "25"=> "Doubs",
                "26"=> "Drôme",
                "27"=> "Eure",
                "28"=> "Eure-et-Loir",
                "29"=> "Finistère",
                "30"=> "Gard",
                "31"=> "Haute-Garonne",
                "32"=> "Gers",
                "33"=> "Gironde",
                "34"=> "Hérault",
                "35"=> "Ille-et-Vilaine",
                "36"=> "Indre",
                "37"=> "Indre-et-Loire",
                "38"=> "Isère",
                "39"=> "Jura",
                "40"=> "Landes",
                "41"=> "Loir-et-Cher",
                "42"=> "Loire",
                "43"=> "Haute-Loire",
                "44"=> "Loire-Atlantique",
                "45"=> "Loiret",
                "46"=> "Lot",
                "47"=> "Lot-et-Garonne",
                "48"=> "Lozère",
                "49"=> "Maine-et-Loire",
                "50"=> "Manche",
                "51"=> "Marne",
                "52"=> "Haute-Marne",
                "53"=> "Mayenne",
                "54"=> "Meurthe-et-Moselle",
                "55"=> "Meuse",
                "56"=> "Morbihan",
                "57"=> "Moselle",
                "58"=> "Nièvre",
                "59"=> "Nord",
                "60"=> "Oise",
                "61"=> "Orne",
                "62"=> "Pas-de-Calais",
                "63"=> "Puy-de-Dôme",
                "64"=> "Pyrénnées-Atlantiques",
                "65"=> "Hautes-Pyrénnées",
                "66"=> "Pyrénnées-Orientales",
                "67"=> "Bas-Rhin",
                "68"=> "Haut-Rhin",
                "69"=> "Rhône",
                "70"=> "Haute-Saône",
                "71"=> "Saône-et-Loire",
                "72"=> "Sarthe",
                "73"=> "Savoie",
                "74"=> "Haute-Savoie",
                "75"=> "Paris",
                "76"=> "Seine-Maritime",
                "77"=> "Seine-et-Marne",
                "78"=> "Yvelines",
                "79"=> "Deux-Sèvres",
                "80"=> "Somme",
                "81"=> "Tarn",
                "82"=> "Tarn-et-Garonne",
                "83"=> "Var",
                "84"=> "Vaucluse",
                "85"=> "Vendée",
                "86"=> "Vienne",
                "87"=> "Haute-Vienne",
                "88"=> "Vosges",
                "89"=> "Yonne",
                "90"=> "Territoire-de-Belfort",
                "91"=> "Essonne",
                "92"=> "Hauts-de-Seine",
                "93"=> "Seine-Saint-Denis",
                "94"=> "Val-de-Marne",
                "95"=> "Val-d'Oise",
                "99"=> "Etranger",
                "971"=> "Guadeloupe",
                "972"=> "Martinique",
                "973"=> "Guyane",
                "974"=> "Réunion",
                "975"=> "St-Pierre-et-Miquelon",
                "976"=> "Mayotte",
                "977"=> "Saint-Barthélemy",
                "978"=> "Saint-Martin",
                "986"=> "Wallis-et-Futuna",
                "987"=> "Polynésie-Francaise",
                "988"=> "Nouvelle-Calédonie"
            ]);
            $list = $this->listManager->getOne(self::FRENCH_DEPARTMENTS_LIST_NAME);
            $success = !empty($list);
            if (!$success) {
                $error = "not possible to create list of departments : '".self::FRENCH_DEPARTMENTS_LIST_NAME."' error during creation !";
            }
        }
        return compact(['success','error']);
    }

    /**
     * @return array ['success' => bool, 'error' => string]
     */
    protected function createAreas(): array
    {
        $success = false;
        $error = '';

        $list = $this->listManager->getOne(self::FRENCH_AREAS_LIST_NAME);
        if (!empty($list)) {
            $error = "not possible to create list of areas : '".self::FRENCH_AREAS_LIST_NAME."' is alreadyExisting !";
            $success = false;
        } else {
            // CODE ISO 3166-2
            $this->listManager->create(self::FRENCH_AREAS_TITLE, [
                "ARA"=> "Auvergne-Rhône-Alpes",
                "BFC"=> "Bourgogne-Franche-Comté",
                "BRE"=> "Bretagne",
                "CVL"=> "Centre-Val de Loire",
                "COR"=> "Corse",
                "GES"=> "Grand Est",
                "HDF"=> "Hauts-de-France",
                "IDF"=> "Île-de-France",
                "NOR"=> "Normandie",
                "NAQ"=> "Nouvelle-Aquitaine",
                "OCC"=> "Occitanie",
                "PDL"=> "Pays de la Loire",
                "PAC"=> "Provence-Alpes-Côte d'Azur",
                "GUA"=> "Guadeloupe",
                "GUF"=> "Guyane",
                "LRE"=> "La Réunion",
                "MTQ"=> "Martinique",
                "MAY"=> "Mayotte",
                "COM"=> "Collectivités d'outre-mer",
            ]);
            $list = $this->listManager->getOne(self::FRENCH_AREAS_LIST_NAME);
            $success = !empty($list);
            if (!$success) {
                $error = "not possible to create list of areas : '".self::FRENCH_AREAS_LIST_NAME."' error during creation !";
            }
        }
        return compact(['success','error']);
    }

    
    /**
     * @return array ['success' => bool, 'error' => string]
     */
    protected function createFormToAssociateAreasAndDepartments(): array
    {
        $success = false;
        $error = '';

        $formId = $this->params->get('formIdAreaToDepartment');
        if (!empty($formId) && empty($this->getFormIdAreaToDepartment())) {
            $error = 'parameter \'formIdAreaToDepartment\' is defined but with a bad format !';
        } elseif (!empty($formId)) {
            $form = $this->formManager->getOne($formId);
            if (!empty($form)) {
                $error = 'not possible to create the form because already existing !';
            }
        }
        if (empty($error)) {
            $listDept = $this->listManager->getOne(self::FRENCH_DEPARTMENTS_LIST_NAME);
            if (empty($listDept) && ($res = $this->createDepartements()) && !$res['success']) {
                $error = $res['error'];
            }
        }
        if (empty($error)) {
            $listArea = $this->listManager->getOne(self::FRENCH_AREAS_LIST_NAME);
            if (empty($listArea) && ($res = $this->createAreas()) && !$res['success']) {
                $error = $res['error'];
            }
        }
        if (empty($error)) {
            $deptListName = self::FRENCH_DEPARTMENTS_LIST_NAME;
            $arealistName = self::FRENCH_AREAS_LIST_NAME;
            if (empty($formId)) {
                $formId = $this->formManager->findNewId();
            }
            $form = $this->formManager->create([
                'bn_id_nature' => $formId,
                'bn_label_nature' => 'Correspondance régions - départements',
                'bn_template' =>
                <<<TXT
                titre***Départements de {{bf_region}}***Titre Automatique***
                liste***$arealistName***Région*** *** *** ***bf_region*** ***1*** *** *** * *** * *** *** *** ***
                checkbox***$deptListName***Départements*** *** *** ***bf_departement*** ***1*** *** *** * *** * *** *** *** ***
                acls*** * ***@admins***comments-closed***
                TXT,
                'bn_description' => '',
                'bn_sem_context' => '',
                'bn_sem_type' => '',
                'bn_condition' => ''
            ]);
            $form = $this->formManager->getOne($formId);
            if (empty($form)) {
                $error = "not possible to create the form : error during creation !";
            } else {
                $this->saveFormIdInConfig($formId);
                $this->createEntriesForAssociation($formId);
                $success = true;
            }
        }
        return compact(['success','error']);
    }

    private function createEntriesForAssociation($formId)
    {
        foreach ([
            'ARA' => "1,3,7,15,26,38,42,43,63,69,73,74",
            'BFC' => "21,25,39,58,70,71,89,90",
            'BRE' => "22,29,35,44,56",
            "CVL" => "18,28,36,37,41,45",
            "COR" => "2A,2B",
            "GES" => "8,10,51,52,54,55,57,67,68,88",
            "HDF" => "2,59,60,62,80",
            "IDF" => "75,77,78,91,92,93,94,95",
            "NOR" => "14,27,50,61,76",
            "NAQ" => "16,17,19,23,24,33,40,47,64,79,86,87",
            "OCC" => "9,11,12,30,31,32,34,46,48,65,66,81,82",
            "PDL" => "44,49,53,72,85",
            "PAC" => "4,5,6,13,83,84",
            "GUA" => "971",
            "GUF" => "973",
            "LRE" => "974",
            "MTQ" => "972",
            "MAY" => "976",
            "COM" => "975,977,978,986,987",
        ] as $areaCode => $depts) {
            $this->entryManager->create(
                $formId,
                [
                    'antispam' => 1,
                    'bf_titre' => "Départements de {{bf_region}}",
                    'liste'.self::FRENCH_AREAS_LIST_NAME.'bf_region' => $areaCode,
                    'checkbox'.self::FRENCH_DEPARTMENTS_LIST_NAME.'bf_departement' => $depts,
                ],
            );
        }
    }

    private function getFormIdAreaToDepartment(): string
    {
        $formId = $this->params->get('formIdAreaToDepartment');
        return (
            !empty($formId) &&
            is_scalar($formId) &&
            (strval($formId) == strval(intval($formId))) &&
            intval($formId)>0
        )
            ? strval($formId)
            : "";
    }   
    private function saveFormIdInConfig($formId)
    {
        // default acls in wakka.config.php
        include_once 'tools/templates/libs/Configuration.php';
        $config = new Configuration('wakka.config.php');
        $config->load();

        $baseKey = 'formIdAreaToDepartment';
        $config->$baseKey = $formId;
        $config->write();
        unset($config);
    }
}
