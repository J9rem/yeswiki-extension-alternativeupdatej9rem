<?php

namespace YesWiki\alternativeupdatej9rem\Field;

use Psr\Container\ContainerInterface;
use YesWiki\Bazar\Field\EnumField;

/**
 * @Field({"customsendmail"})
 */
class CustomSendMailField extends EnumField
{
    protected $linkedLabels;        // 3 separated by a coma
    protected $askForCurrentSave;        // 4
    protected $labelForOption;        // 7
    protected $idForOption;

    protected const FIELD_LINKED_LABEL = 3;
    protected const FIELD_ASK_FOR_CURRENT_SAVE = 4;
    protected const FIELD_LABEL_FOR_OPTION = 7;
    protected const KEY_FOR_YES = 'yes' ;
    protected const KEY_FOR_NO = 'no' ;

    public function __construct(array $values, ContainerInterface $services)
    {
        parent::__construct($values, $services);
        $this->size = null;
        $this->maxChars = null;
        $this->propertyName = $this->name;
        $this->linkedLabels = $values[self::FIELD_LINKED_LABEL];
        $this->askForCurrentSave = ($values[self::FIELD_ASK_FOR_CURRENT_SAVE] == self::KEY_FOR_YES);
        $this->labelForOption = $values[self::FIELD_LABEL_FOR_OPTION];
        $this->options = [
            self::KEY_FOR_YES => _t('YES'),
            self::KEY_FOR_NO => _t('NO')
        ];
        $this->idForOption = $this->getPropertyName() . '_option';
    }

    protected function renderInput($entry)
    {
        $output = $this->render('@bazar/inputs/customSendMailRadio.twig', [
            'options' => $this->options,
            'value' => $this->getValue($entry),
            'idForOption' => ($this->askForCurrentSave) ? $this->idForOption : null
        ]);
        if ($this->askForCurrentSave) {
            // extract value before temp changes
            $value = $this->getValue($entry) ;
            // temp values
            $oldLabel = $this->getLabel() ;
            $this->label = $this->labelForOption;
            $this->propertyName = $this->idForOption;
            // render
            $output .= $this->render('@bazar/inputs/customSendMailSelect.twig', [
                'options' => $this->options,
                'value' => $value
            ]);
            // reset values for field
            $this->label = $oldLabel;
            $this->propertyName = $this->name;
        }
        return $output;
    }

    protected function renderStatic($entry)
    {
        $value = $this->getValue($entry) ;
        if (!$value) {
            return null;
        }
        return $this->render('@bazar/fields/radio.twig', [
            'value' => $this->options[$value]
        ]);
    }

    public function formatValuesBeforeSave($entry)
    {
        $value = $this->getValue($entry) ;
        // extract linked labels
        $linkedLabels = explode(',', $this->linkedLabels) ;
        if (count($linkedLabels) == 0 || (count($linkedLabels) == 1 && empty($linkedLabels[0]))) {
            $formattedValues = null ;
        } else {

            // extract previous sendmail and remove labels included in $linkedLabels
            if (!empty($entry['sendmail'])) {
                $emailsLabels = array_unique(explode(',', $entry['sendmail']));
                // remove associated labels
                $emailsLabels = array_filter($emailsLabels, function ($val) use ($linkedLabels) {
                    return !in_array($val, $linkedLabels) ;
                }) ;
            } else {
                $emailsLabels = null ;
            }

            // check if we must sendmail
            if (($value == self::KEY_FOR_YES && !$this->askForCurrentSave) ||
                    ($this->askForCurrentSave && isset($entry[$this->idForOption]) &&
                    $entry[$this->idForOption] == self::KEY_FOR_YES)) {
                foreach ($linkedLabels as $linkedLabel) {
                    if (!empty($entry[$linkedLabel])) {
                        $emailsLabels[] = $linkedLabel ;
                    }
                }
            }
            // prepare formatted values
            if (!empty($entry['sendmail']) || !empty($emailsLabels)) {
                if (count($emailsLabels) == 0) {
                    // erase sendmail
                    $formattedValues = ['fields-to-remove' => ['sendmail',$this->idForOption]] ;
                } else {
                    // update sendmail
                    $formattedValues = ['sendmail' => implode(',', $emailsLabels),
                        'fields-to-remove' => ['sendmail',$this->idForOption]] ; // to be sure to erase previous data
                }
            } else {
                $formattedValues = ['fields-to-remove' => [$this->idForOption]] ;
            }
        }
        return array_merge(
            [$this->propertyName => $this->getValue($entry)],
            $formattedValues
        );
    }

    public function jsonSerialize()
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'linkedLabels' => $this->linkedLabels,
                'askForCurrentSave' => $this->askForCurrentSave,
                'labelForOption' => $this->labelForOption
            ]
        );
    }
}
