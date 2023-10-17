<?php

/*
 * This file is part of the YesWiki Extension Alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-bazar-list-send-mail-dynamic
 */

namespace YesWiki\Alternativeupdatej9rem\Entity;

class DataContainer
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data)
    {
        return $this->data = $data;
    }
}
