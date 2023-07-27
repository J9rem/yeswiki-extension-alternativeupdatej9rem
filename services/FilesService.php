<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YesWiki\Alternativeupdatej9rem\Service;

use AutoUpdate\Files;

if (!class_exists(Files::class,false) && file_exists('tools/autoupdate/app/Files.php')){
    require_once 'tools/autoupdate/app/Files.php';
}

class FilesService extends Files
{
    public function __construct(
    ) {
    }
    public function delete($path)
    {
        return parent::delete($path);
    }
}