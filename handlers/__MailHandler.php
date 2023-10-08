<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-send-mail-selector-field
 */


namespace YesWiki\Alternativeupdatej9rem;

use YesWiki\Alternativeupdatej9rem\Field\SendMailSelectorField;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Core\YesWikiHandler;

class __MailHandler extends YesWikiHandler
{
    public function run()
    {
        $entryManager = $this->getService(EntryManager::class);
        if ($entryManager->isEntry($this->wiki->GetPageTag())) {
            $entry = $entryManager->getOne($this->wiki->GetPageTag());
            if ((!empty($_POST['mail']) || !empty($_POST['email'])) && isset($_SERVER['HTTP_X_REQUESTED_WITH'])
                && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') && !empty($_GET['field']) && !empty($entry[$_GET['field']])) {
                $formId = $entry['id_typeannonce'];
                $formManager = $this->getService(FormManager::class);
                if (intval($formId) === intval(strval($formId))) {
                    $form = $formManager->getOne($formId);
                    if (!empty($form['prepared'])) {
                        $foundField = null;
                        foreach ($form['prepared'] as $field) {
                            if (($field instanceof SendMailSelectorField) && $field->getPropertyName() === $_GET['field']) {
                                $email = $field->getAssociatedEmail($entry);
                                if (!empty($email)) {
                                    unset($_GET['field']);
                                    $_POST['mail'] = $email;
                                    
                                    $mail_sender = (isset($_POST['email'])) ? trim($_POST['email']) : false;
                                    $_POST['message'] = _t('CONTACT_THIS_MESSAGE').
                                        ' « '.$entry['bf_titre'] . ' ('.$this->wiki->Href().') » ' .
                                         _t('CONTACT_FROM_FORM') .
                                         ' « ' . $form['bn_label_nature'] . ' » '.
                                         _t('CONTACT_FROM_WEBSITE') .
                                         ' « ' . $this->wiki->config['wakka_name'] . ' ». ' .
                                         ($mail_sender
                                            ? _t('CONTACT_REPLY') . ' "' . $mail_sender . '" '
                                                . _t('CONTACT_REPLY2')
                                            : '')."\n\n".
                                            ($_POST['message'] ?? '');
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
