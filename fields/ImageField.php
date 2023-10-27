<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-recurrent-events
 */

namespace YesWiki\Alternativeupdatej9rem\Field;

use YesWiki\Bazar\Field\ImageField as BazarImageField;

include_once 'tools/alternativeupdatej9rem/fields/FileField.php';

/**
 * @Field({"image"})
 */
class ImageField extends BazarImageField
{
    use redefineUpdateEntryAfterFileDelete;
}
