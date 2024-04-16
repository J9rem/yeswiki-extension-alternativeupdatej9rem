<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-can-choose-to-hide-link-to-content-page
 */


namespace YesWiki\Alternativeupdatej9rem;

use YesWiki\Core\Service\PageManager;
use YesWiki\Core\YesWikiAction;

class LoginAction__ extends YesWikiAction
{
    /**
     * @var string MY_CONTENT_TAG
     */
    public const MY_CONTENT_TAG = 'MesContenus';
    public function run()
    {
        /**
         * @var array $myContentPage
         */
        $myContentPage = $this->getService(PageManager::class)->getOne(
            self::MY_CONTENT_TAG, // tag
            null, // time
            false, // cache
            true // bypassAcls
        );
        /**
         * @var bool $showLinkToContentPageIfExisting
         */
        $showLinkToContentPageIfExisting = in_array(
            $this->params->get('showLinkToContentPageIfExisting'),
            [true, 'true', 1, '1'],
            true
        );
        if (empty($myContentPage) || !$showLinkToContentPageIfExisting){
            // replace output
            $this->output = str_replace(
                "<li><a href=\"{$this->wiki->Href('',self::MY_CONTENT_TAG)}\" title=\""
                    . _t('LOGIN_MY_CONTENTS') . '">'
                    . _t('LOGIN_MY_CONTENTS') . '</a></li>',
                '',
                $this->output
            );
        }
    }
}
