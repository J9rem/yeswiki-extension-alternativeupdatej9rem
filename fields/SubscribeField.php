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
use YesWiki\Bazar\Field\CheckboxEntryField;

/**
 * @Field({"subscribe"})
 */
class SubscribeField extends CheckboxEntryField
{
    protected const FIELD_SHOWLIST = 4;
    protected const FIELD_TYPE_SUBSCRIPTION = 7;

    protected $isUserType;
    protected $showList;

    public function __construct(array $values, ContainerInterface $services)
    {
        parent::__construct($values, $services);
        $this->displayMethod = 'dragndrop';
        $this->isUserType = empty($values[self::FIELD_TYPE_SUBSCRIPTION])
            || $values[self::FIELD_TYPE_SUBSCRIPTION] !== 'entry';
        $this->showList = empty($values[self::FIELD_SHOWLIST])
            || $values[self::FIELD_SHOWLIST] !== 'no';
        $this->maxChars = '';
        $this->propertyName = $this->type . $this->name . $this->listLabel;
    }

    public function getOptions()
    {
        return  [];
    }

    public function getIsUserType():bool
    {
        return $this->isUserType;
    }

    public function getShowList():bool
    {
        return $this->showList;
    }

    // change return of this method to keep compatible with php 7.3 (mixed is not managed)
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'isUserType' => $this->getIsUserType(),
                'showList' => $this->getShowList()
            ]
        );
    }
}
