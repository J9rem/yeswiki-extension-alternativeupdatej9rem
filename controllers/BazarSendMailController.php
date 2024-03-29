<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-bazar-list-send-mail-dynamic
 */
namespace YesWiki\Alternativeupdatej9rem\Controller;

use Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Throwable;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use YesWiki\Alternativeupdatej9rem\Entity\DataContainer;
use YesWiki\Bazar\Field\EmailField;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Core\ApiResponse;
use YesWiki\Core\Service\EventDispatcher;
use YesWiki\Core\Service\UserManager;
use YesWiki\Core\YesWikiController;

class BazarSendMailController extends YesWikiController
{
    protected $eventDispatcher;

    public function __construct(
        EventDispatcher $eventDispatcher
    ) {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function isActivated():bool
    {
        $params = $this->wiki->services->get(ParameterBagInterface::class);
        $sendMailParams = $params->has('sendMail')
             ? $params->get('sendMail')
             : [];
        return !empty($sendMailParams['activated']) && $sendMailParams['activated'] === true;
    }

    public function previewEmail()
    {
        if (!$this->isActivated()) {
            throw new Exception("Send mail is not activated");
        }
        extract($this->getParams());
        if (empty($contacts) && !$this->wiki->UserIsAdmin()) {
            $html = _t('AUJ9_SEND_MAIL_TEMPLATE_NOCONTACTS');
            $size = strlen($html);
            return new ApiResponse(['html' => $html,'size'=>$size]);
        }
        if ($addsendertocontact) {
            $contacts[] = $senderEmail;
        }
        $contactsLegend = $sendtogroup ? _t('AUJ9_SEND_MAIL_TEMPLATE_ONEEMAIL') : _t('AUJ9_SEND_MAIL_TEMPLATE_ONEBYONE');
        // $message = htmlspecialchars_decode(html_entity_decode($message));
        $message = $this->replaceLinks($message, $sendtogroup, "EntryIdExample");
        $contactFromMail = !empty($this->wiki->config['contact_from']) ? $this->wiki->config['contact_from'] : '';
        $realSenderEmail = !empty($this->wiki->config['contact_from']) ? $contactFromMail : $senderEmail;
        $replyto = [];
        if (!empty($this->wiki->config['contact_reply_to'])) {
            $replyto[] = $this->wiki->config['contact_reply_to'];
        }
        if ($addsendertoreplyto) {
            $replyto[] = $senderEmail;
        }
        if ($sendtogroup && $addcontactstoreplyto) {
            $replyto = array_merge($replyto, array_values($contacts));
        }
        if (!empty($this->wiki->config['contact_from'])) {
            $replyto[] = $senderEmail;
        }
        $hiddenEmails = $receivehiddencopy ? [$senderEmail] : [];
        if (!$sendtogroup && $groupinhiddencopy) {
            foreach (array_values($contacts) as $contact) {
                if (!in_array($contact, $hiddenEmails)) {
                    $hiddenEmails[] = $contact;
                }
            }
            $contacts = [];
        }
        $hiddenCopy = implode(', ', $hiddenEmails);

        $html = "";
        $html .= "<div><strong>"._t('AUJ9_SEND_MAIL_TEMPLATE_SENDERNAME')."</strong> : $senderName</div>";
        $html .= "<div><strong>"._t('AUJ9_SEND_MAIL_TEMPLATE_SENDEREMAIL')."</strong> : $realSenderEmail</div>";
        $html .= "<div><strong>"._t('AUJ9_SEND_MAIL_TEMPLATE_CONTACTEMAIL')."</strong> : ".implode(', ', $contacts)." (&lt;$contactsLegend&gt;)</div>";
        $html .= empty($replyto) ? "" : "<div><strong>"._t('AUJ9_SEND_MAIL_TEMPLATE_REPLYTO')."</strong> : ".implode(', ', $replyto)."</div>";
        $html .= empty($hiddenCopy) ? "" : "<div><strong>"._t('AUJ9_SEND_MAIL_TEMPLATE_HIDDENCOPY')."</strong> : $hiddenCopy</div>";
        $html .= "<div><strong>"._t('AUJ9_SEND_MAIL_TEMPLATE_MESSAGE_SUBJECT')."</strong> : $subject</div>";
        $html .= "<div><strong>"._t('AUJ9_SEND_MAIL_TEMPLATE_MESSAGE')."</strong> :<br/><hr/>";
        $html .= "$message</div>";
        $size = strlen($html);
        return new ApiResponse(['html' => $html,'size'=>$size]);
    }

    public function sendmailApi()
    {
        if (!$this->isActivated()) {
            throw new Exception("Send mail is not activated");
        }
        $isAdmin = $this->wiki->UserIsAdmin();
        $entryManager = $this->getService(EntryManager::class);
        $dataContainer = new DataContainer([
            'isAdmin' => $isAdmin,
            'canOverrideAdminRestriction' => true,
            'errorMessage' => '',
            'callbackIfNotOverridden' => null
        ]);
        $errors = $this->eventDispatcher->yesWikiDispatch('auj9.sendmail.filterentries', compact(['dataContainer']));
        if (!empty($errors)) {
            return new ApiResponse(
                [
                    'error' => true,
                    'message' => $errors['exception']['message'] ?? '',
                    'file' => $errors['exception']['file'] ?? '',
                    'line' => $errors['exception']['line'] ?? ''
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
        $data = $dataContainer->getData();

        if (!empty($data['errorMessage'])) {
            return new ApiResponse(['error' => $data['errorMessage']], Response::HTTP_UNAUTHORIZED);
        }

        $params = $this->getParams();

        // TODO manage type
        $contacts = [];
        $fieldCache = [];
        $emailfieldname = filter_input(INPUT_POST, 'emailfieldname', FILTER_UNSAFE_RAW);
        $emailfieldname = in_array($emailfieldname, [null,false], true) ? "" : htmlspecialchars(strip_tags($emailfieldname));
        if ($data['canOverrideAdminRestriction']) {
            $forms = [];
            foreach($params['contacts'] as $entryId) {
                $entry = $entryManager->getOne($entryId, false, null, true, true);
                if (!empty($entry['id_typeannonce']) &&
                    is_scalar($entry['id_typeannonce']) &&
                    intval($entry['id_typeannonce']) > 0) {
                    $formId = strval($entry['id_typeannonce']);
                    if (empty($forms[$formId])) {
                        $formManager = $this->getService(FormManager::class);
                        $form = $formManager->getOne($formId);
                        if (!empty($form['prepared'])) {
                            $forms[$formId] = $form;
                        }
                    }
                    if (!empty($forms[$formId])) {
                        $form = $forms[$formId];
                        $this->updateContactsFromForm($form, $entry, $contacts, $fieldCache, $emailfieldname, $entryManager);
                    }
                }
            }
        } elseif ($data['callbackIfNotOverridden'] !== null && is_callable($data['callbackIfNotOverridden'])) {
            $data['callbackIfNotOverridden'](
                $params['contacts'],
                function ($entry, $form) use (&$contacts, &$fieldCache, $emailfieldname, $entryManager) {
                    $this->updateContactsFromForm($form, $entry, $contacts, $fieldCache, $emailfieldname, $entryManager);
                }
            );
        }
        unset($fieldCache);

        if (empty($contacts)) {
            return new ApiResponse(['error' => 'No contacts'], Response::HTTP_BAD_REQUEST);
        }

        $startLink = preg_quote("<a", '/');
        $endStart = preg_quote(">", '/');
        $endLink = preg_quote("</a>", '/');
        $startP = preg_quote("<p", '/');
        $endP = preg_quote("</p>", '/');
        $link = preg_quote("href=\"", '/')."([^\"]*)".preg_quote("\"", '/');
        $messageTxt = strip_tags($params['message'], '<br><a><p>');
        $messageTxt = preg_replace("/{$startLink}[^>]*{$link}[^>]*{$endStart}([^<]*){$endLink}/", "$2 ($1)", $messageTxt);
        $messageTxt = str_replace(['<br>','<br\>'], "\n", $messageTxt);
        $messageTxt = str_replace('&nbsp;', " ", $messageTxt);
        $messageTxt = preg_replace("/{$startP}[^>]*{$endStart}([^<]*){$endP}/", "$1\n", $messageTxt);
        $messageTxt = html_entity_decode($messageTxt);
        if ($params['addsendertocontact']) {
            $contacts['sender-email'] = $params['senderEmail'];
        }
        $hiddenCopies = $params['receivehiddencopy'] ? [$params['senderEmail']] : [];
        $repliesTo = $params['addsendertoreplyto'] ? [$params['senderEmail']] : [];
        if ($params['sendtogroup'] && $params['addcontactstoreplyto']) {
            $repliesTo = array_merge($repliesTo, array_values($contacts));
        }
        $doneFor = [];
        $error = false;
        try {
            if ($params['sendtogroup']) {
                $message = $this->replaceLinksGeneric($params['message'], false);
                $messageTxt = $this->replaceLinksGeneric($messageTxt, true);
                if (!empty($contacts) && $this->sendMail($params['senderEmail'], $params['senderName'], $contacts, $repliesTo, $hiddenCopies, $params['subject'], $messageTxt, $message)) {
                    $doneFor = array_merge($doneFor, array_keys($contacts));
                } else {
                    $error = true;
                }
            } elseif ($params['groupinhiddencopy']) {
                foreach ($contacts as $id => $contact) {
                    if (!in_array($contact, $hiddenCopies)) {
                        $hiddenCopies[] = $contact;
                    }
                }
                $message = $this->replaceLinksGeneric($params['message'], false);
                $messageTxt = $this->replaceLinksGeneric($messageTxt, true);
                if (!empty($hiddenCopies) && $this->sendMail($params['senderEmail'], $params['senderName'], [], $repliesTo, $hiddenCopies, $params['subject'], $messageTxt, $message)) {
                    $doneFor = array_merge($doneFor, array_keys($contacts));
                } else {
                    $error = true;
                }
            } else {
                $startTime = time();
                foreach ($contacts as $id => $contact) {
                    $message = $this->replaceLinks($params['message'], false, $id == "sender-email" ? "" : $id);
                    $messageTxtReplaced = $this->replaceLinks($messageTxt, false, $id == "sender-email" ? "" : $id, true);
                    if ($this->sendMail($params['senderEmail'], $params['senderName'], [$contact], $repliesTo, $hiddenCopies, $params['subject'], $messageTxtReplaced, $message)) {
                        $doneFor[] = $id;
                        if (time() - $startTime > 15) {
                            break;
                        }
                    } else {
                        $error = true;
                    }
                }
            }
        } catch (Throwable $th) {
            if ($isAdmin) {
                return new ApiResponse(['error' => 'message not sent','exceptionMessage' => $th->__toString()], Response::HTTP_INTERNAL_SERVER_ERROR);
            } else {
                $error = true;
            }
        }

        if (!$error && !empty($doneFor)) {
            return new ApiResponse(['sent for'=> implode(',', $doneFor)]);
        } elseif ($error && !empty($doneFor)) {
            return new ApiResponse(['error' => 'Part of messages not sent'], Response::HTTP_INTERNAL_SERVER_ERROR);
        } else {
            return new ApiResponse(['error' => 'message not sent'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function updateContactsFromForm(
        array $form,
        array $entry,
        array &$contacts,
        array &$fieldCache,
        string $emailfieldname,
        $entryManager
    ) {
        $field = $this->getEmailField($form, $fieldCache, $emailfieldname);
        if (!empty($field)) {
            $propName = $field->getPropertyName();
            $realEntry = $entryManager->getOne($entry['id_fiche'], false, null, true, true);
            if (!empty($realEntry[$propName]) && !empty($entry['id_fiche']) && !isset($contacts[$entry['id_fiche']]) && !in_array($realEntry[$propName], $contacts)) {
                $contacts[$entry['id_fiche']] = $realEntry[$propName];
            }
        }
    }

    private function getEmailField($form, array &$fieldCache, string $emailfieldname): ?EmailField
    {
        if (empty($form['bn_id_nature'])) {
            return null;
        }
        $formId = $form['bn_id_nature'];
        if (!array_key_exists($formId, $fieldCache)) {
            $fieldCache[$formId] = null;
            foreach ($form['prepared'] as $field) {
                $propName = $field->getPropertyName();
                if ($field instanceof EmailField && !empty($propName) && (
                    empty($emailfieldname) || (
                        !empty($emailfieldname) && $propName == $emailfieldname
                    )
                )) {
                    $fieldCache[$formId] = $field;
                    break;
                }
            }
        }
        return $fieldCache[$formId];
    }

    private function getContacts(string $contactslist, string $emailfieldname, bool $isAdmin, ?array $filteredEntries): array
    {
        $contactsIds = explode(',', $contactslist);
        $entryManager = $this->getService(EntryManager::class);
        $formManager = $this->getService(FormManager::class);
        $contacts = [];
        $formsCache = [];
        foreach ($contactsIds as $entryId) {
            if ($entryManager->isEntry($entryId)) {
                $entry = $entryManager->getOne($entryId, false, null, false, true);
                if (!empty($entry['id_typeannonce']) && strval($entry['id_typeannonce']) == strval(intval($entry['id_typeannonce']))) {
                    $formId = $entry['id_typeannonce'];
                    if (!isset($formsCache[$formId])) {
                        if (!empty($emailfieldname)) {
                            $field = $formManager->findFieldFromNameOrPropertyName($emailfieldname, $formId);
                            $formsCache[$formId] = [
                                'prepared' => empty($field) ? [] : [
                                    $field
                                ]
                            ];
                        } else {
                            $formsCache[$formId] = $formManager->getOne($formId);
                        }
                        if (!empty($formsCache[$formId]['prepared'])) {
                            foreach ($formsCache[$formId]['prepared'] as $field) {
                                if ($field instanceof EmailField) {
                                    $formsCache[$formId]['email field'] = $field;
                                    break;
                                }
                            }
                        }
                    }
                    if (!empty($formsCache[$formId]['email field']) &&
                        !empty($entry[($formsCache[$formId]['email field'])->getPropertyName()])) {
                        $email = $entry[($formsCache[$formId]['email field'])->getPropertyName()];
                        $email = filter_var($email, FILTER_VALIDATE_EMAIL);
                        if (!empty(trim($email))) {
                            $contacts[$entryId] = $email;
                        }
                    }
                }
            }
        }

        return $contacts;
    }

    public function filterAuthorizedEntries()
    {
        if (!$this->isActivated()) {
            throw new Exception("Send mail is not activated");
        }
        foreach (['entriesIds','params'] as $key) {
            $tmp = (isset($_POST[$key]) && is_array($_POST[$key])) ? $_POST[$key] : [];
            $tmp = array_filter($tmp, function ($val) {
                return is_string($val);
            });
            extract([$key => $tmp]);
            unset($tmp);
        }
        $isAdmin = $this->wiki->UserIsAdmin();
        $dataContainer = new DataContainer([
            'isAdmin' => $isAdmin,
            'entriesIds' => $entriesIds,
            'params' => $params,
            'filteredEntriesIds' => $isAdmin ? $entriesIds : [] // empty by default
        ]);
        $this->eventDispatcher->yesWikiDispatch('auj9.sendmail.filterAuthorizedEntries', compact(['dataContainer']));
        $data = $dataContainer->getData();
        $entriesIds = $data['filteredEntriesIds'];
        return new ApiResponse(['entriesIds' => $entriesIds]);
    }

    public function getCurrentUserEmail()
    {
        $userManager = $this->getService(UserManager::class);
        $user = $userManager->getLoggedUser();
        $email = "";
        $name = "";
        if (!empty($user['name'])) {
            $user = $userManager->getOneByName($user['name']);
            if (!empty($user['email'])) {
                $email = $user['email'];
                $name = $user['name'];
            }
        }
        return new ApiResponse(['email' => $email,'name'=>$name]);
    }

    private function sendMail(
        string $mail_sender,
        string $name_sender,
        array $contacts,
        array $repliesTo,
        array $hiddenCopies,
        string $subject,
        string $message_txt,
        string $message_html
    ): bool {
        if (empty($contacts) && empty($hiddenCopies)) {
            return false;
        }
        //Create a new PHPMailer instance
        $mail = new PHPMailer(true);
        try {
            $mail->set('CharSet', 'utf-8');

            if ($this->wiki->config['contact_mail_func'] == 'smtp') {
                //Tell PHPMailer to use SMTP
                $mail->isSMTP();
                //Enable SMTP debugging
                // 0 = off (for production use)
                // 1 = client messages
                // 2 = client and server messages
                $mail->SMTPDebug = $this->wiki->config['contact_debug'];
                //Ask for HTML-friendly debug output can be a function($str, $level)
                $mail->Debugoutput = 'html';
                //Set the hostname of the mail server
                $mail->Host = $this->wiki->config['contact_smtp_host'];
                //Set the SMTP port number - likely to be 25, 465 or 587
                $mail->Port = $this->wiki->config['contact_smtp_port'];
                //Whether to use SMTP authentication
                if (!empty($this->wiki->config['contact_smtp_user'])) {
                    $mail->SMTPAuth = true;
                    //Username to use for SMTP authentication
                    $mail->Username = $this->wiki->config['contact_smtp_user'];
                    //Password to use for SMTP authentication
                    $mail->Password = $this->wiki->config['contact_smtp_pass'];
                } else {
                    $mail->SMTPAuth = false;
                }
            } elseif ($this->wiki->config['contact_mail_func'] == 'sendmail') {
                // Set PHPMailer to use the sendmail transport
                $mail->isSendmail();
            }

            //Set an alternative reply-to address
            if (!empty($this->wiki->config['contact_reply_to'])) {
                $mail->addReplyTo($this->wiki->config['contact_reply_to']);
            }
            if (count($repliesTo) > 1) {
                foreach ($repliesTo as $contact) {
                    $mail->addReplyTo($contact, $contact);
                }
            }
            // Set always the same 'from' address (to avoid spam, it's a good practice to set the from field with an address from
            // the same domain than the sending mail server)
            if (!empty($this->wiki->config['contact_from'])) {
                if (empty($repliesTo)) {
                    $mail->addReplyTo($mail_sender, $name_sender);
                }
                $mail_sender = $this->wiki->config['contact_from'];
            }
            //Set who the message is to be sent from
            if (empty($name_sender)) {
                $name_sender = $mail_sender;
            }
            $mail->setFrom($mail_sender, $name_sender);

            //Set who the message is to be sent to
            foreach ($contacts as $key => $mail_receiver) {
                $mail->addAddress($mail_receiver, $mail_receiver);
            }

            foreach ($hiddenCopies as $contact) {
                $mail->addBCC($contact, $contact);
            }
            //Set the subject line
            $mail->Subject = $subject;

            // That's bad if only text passed to function: Linebreaks won't be rendered.
            //if (empty($message_html)) {
            //  $message_html = $message_txt;
            //}

            if (empty($message_html)) {
                $mail->isHTML(false);
                $mail->Body = $message_txt ;
            } else {
                $mail->isHTML(true);
                $mail->Body = $message_html ;
                if (!empty($message_txt)) {
                    $mail->AltBody = $message_txt;
                }
            }

            $mail->send();
            return true;
        } catch (Exception $e) {
            if ($this->wiki->UserIsAdmin()) {
                throw $e;
            }
            return false;
        }
    }

    private function getParams(): array
    {
        $message = (isset($_POST['message']) && is_string($_POST['message'])) ? $_POST['message'] : '';
        $senderName = filter_input(INPUT_POST, 'senderName', FILTER_UNSAFE_RAW);
        $senderName = in_array($senderName, [false,null], true) ? "" : htmlspecialchars(strip_tags($senderName));
        $senderEmail = filter_input(INPUT_POST, 'senderEmail', FILTER_VALIDATE_EMAIL);
        $subject = filter_input(INPUT_POST, 'subject', FILTER_UNSAFE_RAW);
        $subject = in_array($subject, [false,null], true) ? "" : htmlspecialchars(strip_tags($subject));
        $contacts = empty($_POST['contacts'])
            ? []
            : (
                is_string($_POST['contacts'])
                ? explode(',', $_POST['contacts'])
                : (
                    is_array($_POST['contacts'])
                    ? array_filter($_POST['contacts'], 'is_string')
                    : []
                )
            );
        $contacts = array_map('htmlspecialchars', array_map('strip_tags', $contacts));
        $addsendertocontact = filter_input(INPUT_POST, 'addsendertocontact', FILTER_VALIDATE_BOOL);
        $sendtogroup =  filter_input(INPUT_POST, 'sendtogroup', FILTER_VALIDATE_BOOL);
        $groupinhiddencopy =  filter_input(INPUT_POST, 'groupinhiddencopy', FILTER_VALIDATE_BOOL);
        $addsendertoreplyto = filter_input(INPUT_POST, 'addsendertoreplyto', FILTER_VALIDATE_BOOL);
        $addcontactstoreplyto =  filter_input(INPUT_POST, 'addcontactstoreplyto', FILTER_VALIDATE_BOOL);
        $receivehiddencopy = filter_input(INPUT_POST, 'receivehiddencopy', FILTER_VALIDATE_BOOL);
        return compact([
            'message',
            'senderName',
            'senderEmail',
            'subject',
            'contacts',
            'addsendertocontact',
            'sendtogroup',
            'groupinhiddencopy',
            'addsendertoreplyto',
            'addcontactstoreplyto',
            'receivehiddencopy'
        ]);
    }

    private function replaceLinksGeneric(string $message, ?bool $modeTxt = false): string
    {
        $params = $this->wiki->services->get(ParameterBagInterface::class);
        $output = $message;
        $output = str_replace(
            ['{baseUrl}'],
            [$params->get('base_url')],
            $output
        );
        if (preg_match_all('/\[([^\]]+)\]\(([^\)]+)\)/', $output, $matches)) {
            foreach($matches[0] as $idx => $match) {
                $output = str_replace(
                    $match,
                    ($modeTxt)
                    ? "{$matches[1][$idx]} ({$matches[2][$idx]})"
                    : "<a href=\"{$matches[2][$idx]}\">{$matches[1][$idx]}</a>",
                    $output
                );
            }
        }
        return $output;
    }

    private function replaceLinks(string $message, ?bool $sendtogroup, string $entryId, ?bool $modeTxt = false): string
    {
        $output = $message;
        $entryManager = $this->getService(EntryManager::class);
        if (!$sendtogroup) {
            $isEntry = $entryManager->isEntry($entryId);
            if ($isEntry) {
                $entry = $entryManager->getOne($entryId);
                $title = $entry['bf_titre'] ?? $entryId;
            } else {
                $title = $entryId;
            }
            $link = $this->wiki->Href('', $entryId);
            $editLink = $this->wiki->Href('edit', $entryId);
            $output = str_replace(
                ['{entryId}','{entryLink}','{entryLinkWithTitle}','{entryEditLink}','{entryEditLinkWithText}','{entryLinkWithText}'],
                ($modeTxt)
                ? [$entryId,$link,"$title ($link)",$editLink, _t('BAZ_MODIFIER_LA_FICHE'). " \"$title\" ($editLink)",_t('BAZ_SEE_ENTRY'). " \"$title\" ($link)"]
                : [$entryId,"<a href=\"$link\" title=\"$title\" target=\"blank\">$link</a>","<a href=\"$link\" target=\"blank\">$title</a>","<a href=\"$editLink\" target=\"blank\">$editLink</a>","<a href=\"$editLink\" target=\"blank\">"._t('BAZ_MODIFIER_LA_FICHE'). " \"$title\"</a>","<a href=\"$link\" target=\"blank\">"._t('BAZ_SEE_ENTRY'). " \"$title\"</a>"],
                $output
            );
            $matches = [];
            if ($isEntry && preg_match_all('/{entry\\[([A-Za-z0-9-_]+)\\]}/', $output, $matches)) {
                foreach ($matches[0] as $key => $match) {
                    if (!empty($matches[1][$key])) {
                        $newValue = $entry[$matches[1][$key]] ?? '';
                        try {
                            $newValue = strval($newValue);
                        } catch (Throwable $th) {
                            $newValue = '';
                        }
                        
                        $output = str_replace(
                            $match,
                            $newValue,
                            $output
                        );
                    }
                }
            }
        }
        $output = $this->replaceLinksGeneric($output, $modeTxt);
        return $output;
    }
}
