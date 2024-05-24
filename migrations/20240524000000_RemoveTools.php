<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-autoupdate-system
 */

use YesWiki\Alternativeupdatej9rem\Service\UpdateHandlerService;
use YesWiki\Core\YesWikiMigration;

class RemoveTools extends YesWikiMigration
{
    public function run()
    {
        $updateHandlerService = $this->wiki->services->get(UpdateHandlerService::class);
        $messages = [];
        $updateHandlerService->removeNotUpToDateTools($messages);
        $errors = array_column(
            array_filter(
                $messages,
                function ($message) {
                    return $message['status'] != 'ok';
                }
            ),
            'text'
        );
        if (!empty($errors)){
            throw new Exception('Error Processing '.implode('|',$errors));
        }
    }
}
