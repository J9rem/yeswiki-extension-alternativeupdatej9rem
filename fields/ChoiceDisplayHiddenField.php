<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

 /* to use with in form
choicedisplayhidden*** *** *** *** *** *** *** *** *** *** ***@admins***@admins*** *** *** ***
labelhtml***<div class="hidden-field-specific" style="display:none;">*** ***<div class="hidden-field-specific" style="display:none;">***
...
labelhtml***</div>*** ***</div>***
  */

namespace YesWiki\alternativeupdatej9rem\Field;

use Psr\Container\ContainerInterface;
use YesWiki\Bazar\Field\BazarField;

/**
 * @Field({"choicedisplayhidden"})
 */
class ChoiceDisplayHiddenField extends BazarField
{
    public function __construct(array $values, ContainerInterface $services)
    {
        parent::__construct($values, $services);
        $this->name = null;
        $this->label = null ;
        $this->propertyName = null;
    }

    protected function renderInput($entry)
    {
        return $this->render('@bazar/fields/choice-display-hidden.twig',[]);
    }
    
    protected function renderStatic($entry)
    {
        return $this->render('@bazar/fields/choice-display-hidden.twig',[]);
    }
}
