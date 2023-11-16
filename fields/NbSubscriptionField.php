<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-subscribe-to-entry
 */

namespace YesWiki\Alternativeupdatej9rem\Field;

use Psr\Container\ContainerInterface;
use YesWiki\Bazar\Field\TextField;

/**
 * @Field({"nbsubscription"})
 */
class NbSubscriptionField extends TextField
{
    public function __construct(array $values, ContainerInterface $services)
    {
        parent::__construct($values, $services);
        $this->subType = 'text';
    }

    protected function renderInput($entry)
    {
        // not editable
        return '';
    }

    public function formatValuesBeforeSave($entry)
    {
        // do nothing
        return [];
    }
}
