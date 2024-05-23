<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-duplicate
 */


namespace YesWiki\Alternativeupdatej9rem;

use YesWiki\Alternativeupdatej9rem\Service\RevisionChecker;
use YesWiki\Core\YesWikiAction;

class BarreRedactionAction__ extends YesWikiAction
{
    public function run()
    {
        if ($this->wiki->services->has(RevisionChecker::class)
            && $this->getService(RevisionChecker::class)->isRevisionHigherThan('doryphore', 4, 4, 0)
            && $this->canShowDuplicate()) {
            $anchor = preg_quote('class="link-edit"><i class="fa fa-pencil-alt"></i><span>'. html_entity_decode(_t('TEMPLATE_EDIT_THIS_PAGE')) .'</span></a>', '/');
            $anchor = str_replace(
                '>',
                '>\\s*',
                $anchor
            );
            $button = '<a title="' . _t('AUJ9_DUPLICATE') . '" href="' . $this->wiki->Href('duplicate'.testUrlInIframe()) . '"><i class="fas fa-copy"></i></a>';
            $match = [];
            $this->output = preg_replace(
                "/($anchor)/",
                "$1$button",
                $this->output
            );
        }
    }

    /**
     * test if current tag correspond to wiki page
     * and if vue = consulter (because it could display the duplication button and
     * let think that is for the new page)
     * @return bool
     */
    protected function canShowDuplicate(): bool
    {
        $tag = $this->wiki->tag;
        return (
            empty($tag)
            || empty($_GET['wiki'])
            || !is_string($_GET['wiki'])
            || !(substr($_GET['wiki'], 0, strlen($tag)) === $tag)
            || empty($_GET['vue'])
            || !($_GET['vue'] === 'consulter')
        );
    }
}
