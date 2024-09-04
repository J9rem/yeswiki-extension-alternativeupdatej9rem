<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-repair-yw-official-url
 */

namespace YesWiki\Alternativeupdatej9rem;

use YesWiki\Core\YesWikiAction;

class YesWikiVersionAction__ extends YesWikiAction
{
    /**
     * @var string NEW_URL
     */
    public const NEW_URL = 'https://yeswiki.net';
    /**
     * @var string OLD_URL
     */
    public const OLD_URL = 'https://www.yeswiki.net';
    public function run()
    {
        // replace output
        $this->output = str_replace(
            self::OLD_URL,
            self::NEW_URL,
            $this->output
        );
    }
}
