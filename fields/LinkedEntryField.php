<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-fix-linkedentry-empty
 */

namespace YesWiki\Alternativeupdatej9rem\Field;

use YesWiki\Bazar\Field\LinkedEntryField as BazarLinkedEntryField;

/**
 * @Field({"listefichesliees", "listefiches"})
 */
class LinkedEntryField extends BazarLinkedEntryField
{
    protected function isEmptyOutput(string $output): bool
    {
        return empty($output) || preg_match('/<div id="[^"]+" class="bazar-list[^"]*"[^>]*>\\s*<div class="list">\\s*<\\/div>\\s*<\\/div>/', $output);
    }
}
