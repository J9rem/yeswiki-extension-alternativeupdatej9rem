<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace YesWiki\Alternativeupdatej9rem;

use YesWiki\Core\YesWikiAction;

class BarreRedactionAction__ extends YesWikiAction
{
    public function run()
    {
        $anchor = preg_quote('class="link-edit"><i class="fa fa-pencil-alt"></i><span>'. html_entity_decode(_t('TEMPLATE_EDIT_THIS_PAGE')) .'</span></a>','/');
        $anchor = str_replace(
            '>',
            '>\\s*',
            $anchor);
        $button = '<a title="' . _t('AUJ9_DUPLICATE') . '" href="' . $this->wiki->Href('duplicate'.testUrlInIframe()) . '"><i class="fas fa-copy"></i></a>';
        $match = [];
        $this->output = preg_replace(
            "/($anchor)/",
            "$1$button",
            $this->output
        );
    }
}
