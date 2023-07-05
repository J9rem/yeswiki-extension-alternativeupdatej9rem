<?php

namespace YesWiki\Alternativeupdatej9rem;

use Exception;
use YesWiki\Bazar\Exception\ParsingMultipleException;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\YesWikiAction;

class __BazarListeAction extends YesWikiAction
{
    public function formatArguments($arg)
    {
        $entryManager = $this->getService(EntryManager::class);

        // ICONS FIELD
        $iconField = $_GET['iconfield'] ?? $arg['iconfield'] ?? null ;

        // ICONS
        $icon = $_GET['icon'] ?? $arg['icon'] ??  null;
        $iconAlreadyDefined = ($icon == $this->params->get('baz_marker_icon') || is_array($icon)) ;
        if (!$iconAlreadyDefined) {
            if (!empty($icon)) {
                try {
                    $tabparam = $entryManager->getMultipleParameters($icon, ',', '=');
                    if (count($tabparam) > 0 && !empty($iconField)) {
                        // on inverse cle et valeur, pour pouvoir les reprendre facilement dans la carto
                        foreach ($tabparam as $key=>$data) {
                            $tabparam[$data] = $key;
                        }
                        $icon = $tabparam;
                    } else {
                        $icon = trim(array_values($tabparam)[0]);
                    }
                } catch (ParsingMultipleException $th) {
                    throw new Exception('action bazarliste : le paramètre icon est mal rempli.<br />Il doit être de la forme icon="nomIcone1=valeur1, nomIcone2=valeur2"<br/>('.$th->getMessage().')');
                }
            } else {
                $icon = $this->params->get('baz_marker_icon');
            }
        }

        // COLORS FIELD
        $colorField = $_GET['colorfield'] ?? $arg['colorfield'] ?? null ;

        // COLORS
        $color = $_GET['color'] ?? $arg['color'] ?? null ;
        $colorAlreadyDefined = ($color == $this->params->get('baz_marker_color') || is_array($color)) ;
        if (!$colorAlreadyDefined) {
            if (!empty($color)) {
                try {
                    $tabparam = $entryManager->getMultipleParameters($color, ',', '=');
                    if (count($tabparam) > 0 && !empty($colorField)) {
                        // on inverse cle et valeur, pour pouvoir les reprendre facilement dans la carto
                        foreach ($tabparam as $key=>$data) {
                            $tabparam[$data] = $key;
                        }
                        $color = $tabparam;
                    } else {
                        $color = trim(array_values($tabparam)[0]);
                    }
                } catch (ParsingMultipleException $th) {
                    throw new Exception('action bazarliste : le paramètre color est mal rempli.<br />Il doit être de la forme color="couleur1=valeur1, couleur2=valeur2"<br/>('.$th->getMessage().')');
                }
            } else {
                $color = $this->params->get('baz_marker_color');
            }
        }
        $this->arguments['icon'] = $icon;
        $this->arguments['color'] = $color;

        return compact(['icon','color']);
    }

    public function run(){

    }
}
