<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-recurrent-events
 */

namespace YesWiki\Alternativeupdatej9rem\Field;

use YesWiki\Alternativeupdatej9rem\Service\DateService;
use YesWiki\Bazar\Field\DateField as CoreDateField;

/**
 * changes only for `doryphore 4.4.1` or recurrent events
 */

/**
 * @Field({"jour", "listedatedeb", "listedatefin"})
 */
class DateField extends CoreDateField
{
    /**
     * test if core contains recurrent events
     * @return bool
     */
    protected function coreHasRecurrentEvents(): bool
    {
        return is_file('tools/bazar/templates/inputs/_date_recurrent_part.twig');
    }

    protected function renderInput($entry)
    {
        if ($this->coreHasRecurrentEvents()) {
            return parent::renderInput($entry);
        }
        $day = "";
        $hour = 0;
        $minute = 0;
        $hasTime = false;
        $value = $this->getValue($entry);

        $dateService = $this->getService(DateService::class);

        if (!empty($value)) {
            // Default value when entry exist
            $day = $dateService->getDateTimeWithRightTimeZone($value)->format('Y-m-d H:i');
            $hasTime = (strlen($value) > 10);
            if ($hasTime) {
                $result = explode(' ', $day);
                list($hour, $minute) = array_map('intval', explode(':', $result[1]));
                $day = $result[0];
            } else {
                $day = substr($day, 0, 10);
            }
        } elseif (!empty($this->default)) {
            // Default value when new entry
            // 0 and 1 are present to manage olf format of this field
            if (in_array($this->default, ['today','1'])) {
                $day = date("Y-m-d");
            } else {
                $day = date("Y-m-d", strtotime($this->default));
            }
        }

        return $this->render('@bazar/inputs/date.twig', [
            'day' => $day,
            'hour' => $hour,
            'minute' => $minute,
            'hasTime' => $hasTime,
            'value' => $value,
            'data' => $entry["{$this->getPropertyName()}_data"] ?? [],
            'canRegisterMultipleEntries' => $dateService->canRegisterMultipleEntries($entry)
        ]);
    }

    public function formatValuesBeforeSave($entry)
    {
        if ($this->coreHasRecurrentEvents()) {
            return parent::formatValuesBeforeSave($entry);
        }
        $return = [];
        if ($this->getPropertyname() === 'bf_date_fin_evenement') {
            if(!empty($entry['id_fiche'])
                    && is_string($entry['id_fiche'])) {
                $this->getService(DateService::class)->followId($entry['id_fiche']);
            }
            if (!$this->getService(DateService::class)->canRegisterMultipleEntries($entry)) {
                // clean data from entry because not possible to create repetition
                if (isset($entry['bf_date_fin_evenement_data'])) {
                    unset($entry['bf_date_fin_evenement_data']);
                }
            } elseif (!empty($entry['bf_date_fin_evenement_data']['other'])) {
                unset($entry['bf_date_fin_evenement_data']['other']);
                if (!empty($entry['bf_date_fin_evenement_data'])) {
                    $return['bf_date_fin_evenement_data'] = $entry['bf_date_fin_evenement_data'];
                }
            }
        }
        $value = $this->getValue($entry);
        if (!empty($value) && isset($entry[$this->propertyName . '_allday']) && $entry[$this->propertyName . '_allday'] == 0
             && isset($entry[$this->propertyName . '_hour']) && isset($entry[$this->propertyName . '_minutes'])) {
            $value = $this->getService(DateService::class)->getDateTimeWithRightTimeZone("$value {$entry[$this->propertyName . '_hour']}:{$entry[$this->propertyName . '_minutes']}")->format('c');
        }
        $return[$this->propertyName] = $value;
        $return['fields-to-remove'] = [
            $this->propertyName . '_allday',
            $this->propertyName . '_hour',
            $this->propertyName . '_minutes'
        ];
        if (empty($entry['bf_date_fin_evenement_data'])) {
            $return['fields-to-remove'][] = 'bf_date_fin_evenement_data';
        }
        return $return;
    }

    protected function renderStatic($entry)
    {
        if ($this->coreHasRecurrentEvents()) {
            return parent::renderStatic($entry);
        }
        $value = $this->getValue($entry);
        if (!$value) {
            return "";
        }

        if (strlen($value) > 10) {
            $value = $this->getService(DateService::class)->getDateTimeWithRightTimeZone($value)->format('d.m.Y - H:i');
        } else {
            $value =  date('d.m.Y', strtotime($value));
        }

        $matches = [];
        $recurrenceBaseId = '';
        $data = [];
        if ($this->getPropertyname() === 'bf_date_fin_evenement'
                && !empty($entry['bf_date_fin_evenement_data'])) {
            if(is_string($entry['bf_date_fin_evenement_data'])
                && preg_match('/\{\\"recurrentParentId\\":\\"([^"]+)\\"\}/', $entry['bf_date_fin_evenement_data'], $matches)) {
                $recurrenceBaseId = $matches[1];
            } elseif (is_array($entry['bf_date_fin_evenement_data'])) {
                $data = $entry['bf_date_fin_evenement_data'];
            }
        }
        return $this->render('@bazar/fields/date.twig', [
            'value' => $value,
            'recurrenceBaseId' => $recurrenceBaseId,
            'data' => $data
        ]);
    }

    /**
     * changes for duplicateHandler
     * Feature UUID : auj9-duplicate
     */

    protected function getValue($entry)
    {
        // do not take default for this field
        return $entry[$this->propertyName] ?? $_REQUEST[$this->propertyName] ?? null;
    }
}
