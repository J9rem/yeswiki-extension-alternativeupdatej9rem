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

class AdminTagAction__ extends YesWikiAction
{
    public function run()
    {
        if (!$this->wiki->UserIsAdmin()){
            $this->output = '';
            return $this->render('@templates/alert-message.twig',[
                'message' => _t('BAZ_NEED_ADMIN_RIGHTS'),
                'type' => 'danger'
            ]);
        }
    }
}
