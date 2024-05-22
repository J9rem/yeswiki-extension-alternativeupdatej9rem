<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-fix-4-4-3
 */

namespace YesWiki\Alternativeupdatej9rem\Field;

use YesWiki\Bazar\Field\TabsField as BazarTabsField;
use YesWiki\Core\Service\AssetsManager;

/**
 * @Field({"tabs"})
 */
class TabsField extends BazarTabsField
{
    protected function prepareText($mode): ?string
    {
        return '';
    }
    protected function prepareTextNew($mode): ?string
    {
        return $this->tabsController->openTabs($mode, $this);
    }
    protected function renderInput($entry)
    {
        if ($this->getMoveSubmitButtonToLastTab()) {
            $this->getService(AssetsManager::class)->AddJavascriptFile('tools/bazar/presentation/javascripts/bazar-edit-tabs-field.js');
        }
        $this->formText = $this->prepareTextNew('form');
        return $this->formText;
    }

    protected function renderStatic($entry)
    {
        $this->viewText = $this->prepareTextNew('view');
        return $this->viewText;
    }
}
