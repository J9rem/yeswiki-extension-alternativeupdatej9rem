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


 namespace YesWiki\Alternativeupdatej9rem\Field;

use Psr\Container\ContainerInterface;
use YesWiki\Bazar\Field\EnumField;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FormManager;

class SendMailSelectorFieldObjectForSave implements \JsonSerializable
{
    private $value;
    private $email;

    public function __construct(string $value, string $email)
    {
        $this->email = $email;
        $this->value = $value;
    }
    public function __toString()
    {
        return (string) $this->email;
    }

    // change return of this method to keep compatible with php 7.3 (mixed is not managed)
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->value;
    }
}

/**
 * @Field({"sendmailselector"})
 */
class SendMailSelectorField extends EnumField
{
    protected $linkedLabel;        // 3
    protected $linkedLabelInForm;  // 4
    protected $subType;            // 7
    protected $formId;
    public $showContactForm; // 9

    protected const FIELD_LINKED_LABEL = 3;
    protected const FIELD_LINKED_LABEL_IN_FORM = 4;
    protected const FIELD_SUB_TYPE = 7;
    protected const FIELD_SHOW_CONTACT_FORM = 9;
    protected const KEY_FOR_LIST = 'list' ;
    protected const KEY_FOR_FORM = 'form' ;

    public function __construct(array $values, ContainerInterface $services)
    {
        parent::__construct($values, $services);
        $this->size = null;
        $this->maxChars = null;
        $this->propertyName = $this->listLabel;
        $this->linkedLabel = trim($values[self::FIELD_LINKED_LABEL]);
        $this->linkedLabelInForm = trim($values[self::FIELD_LINKED_LABEL_IN_FORM]);
        $this->linkedLabelInForm = empty($this->linkedLabelInForm) ? 'bf_mail' : $this->linkedLabelInForm;
        $this->subType = $values[self::FIELD_SUB_TYPE];
        $this->showContactForm = $values[self::FIELD_SHOW_CONTACT_FORM] === 'form';
        $this->searchable = null;

        // lazy loading for options
        $this->options = null;
    }

    private function loadOptions()
    {
        $this->options = [];
        switch ($this->subType) {
            case self::KEY_FOR_LIST:
                $this->loadOptionsFromList();
                break;
            case self::KEY_FOR_FORM:
                $this->formId = intval($this->name);
                if ($this->formId < 1) {
                    $this->options['error'] = 'The name contains \'' . $this->formId . '\' but is not the id of a form : should be positive integer.';
                } else {
                    $this->loadOptionsFromEntries();
                    if (empty($this->options)) {
                        $formManager = $this->getService(FormManager::class);
                        $form = $formManager->getOne($this->formId);
                        if (empty($form)) {
                            $this->options['error'] = 'The name contains \'' . $this->formId . '\' but is not the id of an existing form.';
                        }
                    }
                }
                break;
            default:
                $this->options['error'] = 'SubType \'' . $this->subType . '\' is not allowed.';
        }
        // add the linked label at the beginning
        if (!isset($this->options['error']) && !empty($this->linkedLabel)) {
            $this->options = [$this->linkedLabel => "Cette fiche"] + $this->options;
        }
    }

    public function getOptions()
    {
        if (is_null($this->options)) {
            $this->loadOptions();
        }
        return  $this->options;
    }

    public function formatValuesBeforeSave($entry)
    {
        // add propertyName to the list of emails if several sendmail in same form
        $sendmailList = !empty($entry['sendmail']) ?
            $entry['sendmail'] . ',' . $this->propertyName
            : $this->propertyName;
        $sendmailArray = ['sendmail' => $sendmailList];
        $value = $this->getValue($entry);
        $email = $this->getAssociatedEmail($entry);
        if (!empty($email)) {
            $value =new SendMailSelectorFieldObjectForSave($value, $email);
        }
        return array_merge(
            [$this->propertyName => $value],
            $sendmailArray
        );
    }

    protected function renderInput($entry)
    {
        return $this->render('@bazar/inputs/select.twig', [
            'value' => $this->getValue($entry),
            'options' => $this->getOptions(),
        ]);
    }

    protected function renderStatic($entry)
    {
        return $this->render('@bazar/fields/email.twig', [
            'value' => $this->getAssociatedEmail($entry),
        ]);
    }

    public function getAssociatedEmail($entry)
    {
        $value = $this->getValue($entry) ;
        if (!$value) {
            return null;
        }
        if (!empty($value) && $value === $this->linkedLabel) {
            $value = $entry[$value] ?? '';
        } elseif ($this->subType === self::KEY_FOR_LIST) {
            $value = $this->getOptions()[$value] ?? null;
        } elseif ($this->subType === self::KEY_FOR_FORM) {
            if (in_array($value, array_keys($this->getOptions()), true)) {
                $entryManager = $this->getService(EntryManager::class);
                $linkedEntry = $entryManager->getOne($value);
                $value = $linkedEntry[$this->linkedLabelInForm] ?? null;
            } else {
                $value = null;
            }
        }
        return $value ;
    }

    // change return of this method to keep compatible with php 7.3 (mixed is not managed)
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $data = parent::jsonSerialize();
        if (!$this->getWiki()->UserIsAdmin() && $this->subType === self::KEY_FOR_LIST) {
            if (is_array($data['options'])) {
                // sanitize email in api if not admin
                $data['options'] = array_map(function ($email) {
                    return "***@***.***";
                }, $data['options']);
            }
        }
        return array_merge(
            $data,
            [
                'linkedLabel' => $this->linkedLabel,
                'linkedLabelInForm' => $this->linkedLabelInForm,
                'subType' => $this->subType
            ]
        );
    }
}
