<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YesWiki\alternativeupdatej9rem\Field;


use Psr\Container\ContainerInterface;
use YesWiki\Bazar\Field\LinkField;

/**
 * @Field({"video"})
 */
class VideoField extends LinkField
{
    public function __construct(array $values, ContainerInterface $services)
    {
        parent::__construct($values, $services);
        $this->type = 'video';
    }

    protected function renderInput($entry)
    {
    	return $this->render("@bazar/inputs/link.twig", [
            'value' => $this->getValue($entry)
        ]);
    }
}
