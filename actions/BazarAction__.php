<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace YesWiki\Alternativeupdatej9rem;

use BazarAction;
use YesWiki\Core\YesWikiAction;

/**
 * register specific fields
 */
class BazarAction__ extends YesWikiAction
{
    public function run()
    {
        if (!$this->isWikiHibernated()
            && $this->wiki->UserIsAdmin()
            && isset($this->arguments[BazarAction::VARIABLE_VOIR]) && $this->arguments[BazarAction::VARIABLE_VOIR] === BazarAction::VOIR_FORMULAIRE
            && isset($this->arguments[BazarAction::VARIABLE_ACTION]) && in_array($this->arguments[BazarAction::VARIABLE_ACTION], [BazarAction::ACTION_FORM_CREATE,BazarAction::ACTION_FORM_EDIT], true)
        ) {
            $this->wiki->AddJavascriptFile('tools/alternativeupdatej9rem/javascripts/fields/form-edit-template-register-field.js');
            // Feature UUID : auj9-send-mail-selector-field
            $this->wiki->AddJavascriptFile('tools/alternativeupdatej9rem/javascripts/fields/sendmailselectorfield.js');
            // Feature UUID : auj9-custom-sendmail
            $this->wiki->AddJavascriptFile('tools/alternativeupdatej9rem/javascripts/fields/customsendmailfield.js');
            // Feature UUID : auj9-video-field
            $this->wiki->AddJavascriptFile('tools/alternativeupdatej9rem/javascripts/fields/urlfield.js');
            if (file_exists('tools/bazar/presentation/javascripts/form-edit-template/fields/commons/render-helper.js')) {
                $this->wiki->AddJavascriptFile('tools/alternativeupdatej9rem/javascripts/fields/form-edit-template-module.js', false, true);
            } else {
                $this->wiki->AddJavascriptFile('tools/alternativeupdatej9rem/javascripts/fields/form-edit-template.js');
            }
        }
    }
}
