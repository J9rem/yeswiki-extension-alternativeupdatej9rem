<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-fix-4-4-1
 */

namespace YesWiki\Alternativeupdatej9rem\Field;

use YesWiki\Alternativeupdatej9rem\Service\DateService;
use YesWiki\Bazar\Field\ImageField as CoreImageField;

/**
 * changes only for `doryphore 4.4.1`
 */
/**
 * @Field({"image"})
 */
class ImageField extends CoreImageField
{
    public function formatValuesBeforeSave($entry)
    {
        $value = $this->getValue($entry);
        if ((!empty($_FILES[$this->propertyName]['name']) && !empty($entry['id_fiche']))
            || (isset($entry['oldimage_' . $this->propertyName]) && $entry['oldimage_' . $this->propertyName] != '')) {
            return parent::formatValuesBeforeSave($entry);
        } elseif (!empty($value)) {
            $entry[$this->propertyName] = file_exists($this->getBasePath(). $this->getValue($entry)) ? $this->getValue($entry) : '';
        } else {
            $entry[$this->propertyName] = '';
        }
        return [
            $this->propertyName => $this->getValue($entry),
            'fields-to-remove' => ['oldimage_' . $this->propertyName]
        ];
    }
}
