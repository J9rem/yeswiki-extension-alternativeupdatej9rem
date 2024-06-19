<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-keep-only-wanted-langs
 * Feature UUID : auj9-dont-show-stmp-params
 */

use YesWiki\Alternativeupdatej9rem\Service\YesWikiParamsCompilerPass; //Feature UUID : auj9-dont-show-stmp-params

if (!defined('WIKINI_VERSION')) {
    die('acc&egrave;s direct interdit');
}

/* === Feature UUID : auj9-keep-only-wanted-langs === */
// reset languages
if (!empty($GLOBALS['available_languages'])
    && !empty($GLOBALS['languages_list'])
    && is_iterable($GLOBALS['available_languages'])
    && is_iterable($GLOBALS['languages_list'])) {
    $GLOBALS['available_languages'] = array_filter(
        $GLOBALS['available_languages'],
        function ($lang) {
            return is_string($lang)
                && !empty($lang)
                && array_key_exists($lang, $GLOBALS['languages_list']);
        }
    );
    if (
        !empty($GLOBALS['prefered_language'])
        && !in_array($GLOBALS['prefered_language'], $GLOBALS['available_languages'])
    ) {
        $GLOBALS['prefered_language'] = 'fr';
        if (isset($_GET['lang'])) {
            unset($_GET['lang']);
        }
    }
}
/* === END FOR Feature UUID : auj9-keep-only-wanted-langs === */

/* === Feature UUID : auj9-dont-show-stmp-params === */
// add compiler pass
$this->services->addCompilerPass(new YesWikiParamsCompilerPass());
/* === END FOR Feature UUID : auj9-dont-show-stmp-params === */
