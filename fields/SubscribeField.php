<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-subscribe-to-entry
 */

namespace YesWiki\Alternativeupdatej9rem\Field;

use DateTimeImmutable;
use Psr\Container\ContainerInterface;
use YesWiki\Alternativeupdatej9rem\Service\SubscriptionManager;
use YesWiki\Bazar\Field\CheckboxEntryField;
use YesWiki\Core\Service\AssetsManager;
use YesWiki\Core\Service\UserManager;

/**
 * @Field({"subscribe"})
 */
class SubscribeField extends CheckboxEntryField
{
    protected const FIELD_SHOWLIST = 4;
    protected const FIELD_TYPE_SUBSCRIPTION = 7;

    protected $isUserType;
    protected $optionsNotSecured;
    protected $showList;
    protected $wiki;

    public function __construct(array $values, ContainerInterface $services)
    {
        parent::__construct($values, $services);
        $this->displayMethod = 'dragndrop';
        $this->isDistantJson = false;
        $this->isUserType = empty($values[self::FIELD_TYPE_SUBSCRIPTION])
            || $values[self::FIELD_TYPE_SUBSCRIPTION] !== 'entry';
        $this->showList = empty($values[self::FIELD_SHOWLIST])
            || $values[self::FIELD_SHOWLIST] !== 'no';
        $this->maxChars = '';
        $this->propertyName = $this->type . $this->name . $this->listLabel;
        $this->wiki = $this->getWiki();

        $this->options = null;
        $this->optionsNotSecured = null;
    }

    public function getOptions()
    {
        // load options only when needed but not at construct to prevent infinite loops
        if (is_null($this->options)) {
            if ($this->isUserType){
                $this->options = $this->getOptionsFromUsers();
            } else {
                $this->options = [];
            }
        }
        return $this->options;
    }

    protected function getFullOptionsNotSecured()
    {
        // load options only when needed but not at construct to prevent infinite loops
        if (is_null($this->optionsNotSecured)) {
            if ($this->isUserType){
                $this->optionsNotSecured = $this->getOptionsFromUsers(true);
            } else {
                $this->optionsNotSecured = [];
            }
        }
        return $this->optionsNotSecured;
    }

    /**
     * get options from users
     * @param bool $forceExtraction force extraction if not admin
     * @return array
     */
    protected function getOptionsFromUsers(bool $forceExtraction = false)
    {
        if ($forceExtraction || $this->wiki->UserIsAdmin()){
            $names = array_values(array_map(
                function($user){
                    return $user['name'] ?? '';
                },
                $this->getService(UserManager::class)->getAll()
            ));
            $names = array_filter($names,function ($name) {
                return !empty($name);
            });
            return array_combine($names,$names);
        }
        return [];
    }

    protected function renderStatic($entry)
    {
        $subscriptionManager = $this->getService(SubscriptionManager::class);
        if (!$this->isUserType && !$subscriptionManager->checkIfFormIsOnlyOneEntry($this->getLinkedObjectName())){
            return $this->showErroMessageForForm();
        }
        $output= '';
        if ($this->showList){
            if ($this->isUserType){
                $savedOptions = $this->options;
                $this->options = $this->getFullOptionsNotSecured();
                $output = parent::renderStatic($entry);
                $this->options = $savedOptions;
            } else {
                $output = parent::renderStatic($entry);
            }
            $output = $this->formatOutput($output ?? '');
        }
        $output .= $this->render(
            '@alternativeupdatej9rem/button-for-subcription.twig',
            [
                'isRegistered' => $subscriptionManager->isRegistered($this,$entry),
                'entryId' => $entry['id_fiche'] ?? '',
                'propertyName' => $this->getPropertyName(),
                'noPlace' => !$subscriptionManager->isThereAvailablePlace($entry,$this)
            ]
        );
        return $output;
    }

    /**
     * change output to append a system of dropdown list of registration
     * @param string $outputToFormat
     * @return string $output
     */
    protected function formatOutput(string $outputToFormat): string
    {
        $anchor = preg_quote('<span class="BAZ_texte">','/');
        $anchorUl = preg_quote('<ul>','/');
        $randomStr = rand(1000,100000).'-'.(new DateTimeImmutable())->getTimestamp();
        $seeListTxt = _t('AUJ9_SUBSCRIBE_SEE_LIST');
        $hideListTxt = _t('AUJ9_SUBSCRIBE_HIDE_LIST');
        $this->getService(AssetsManager::class)->AddCSSFile('tools/alternativeupdatej9rem/styles/subscribe-field.css');
        $newUl = <<<HTML
        <div 
            class="btn btn-xs btn-info collapsed" 
            id ="{$randomStr}-heading" 
            role="tab" 
            data-toggle="collapse"
            href="#$randomStr"
            aria-expanded="false"
            aria-controls="$randomStr"
            >
            <span class="when-collapsed">$seeListTxt <i class="fas fa-chevron-down"></i></span>
            <span class="when-not-collapsed">$hideListTxt <i class="fas fa-chevron-up"></i></span>
        </div>
        <ul id="$randomStr" class="collapse">
        HTML;
        return preg_replace("/($anchor)(\s*)($anchorUl)/","$1$2$newUl",$outputToFormat);
    }

    protected function renderInput($entry)
    {
        if ($this->isUserType){
            $savedOptions = $this->options;
            $keys = $this->getValues($entry);
            $availablesOptions = $this->getFullOptionsNotSecured();
            $this->options = $this->wiki->UserIsAdmin()
                ? $availablesOptions
                : array_filter(
                    $availablesOptions,
                    function($key) use($keys){
                        return in_array($key,$keys);
                    }
                );
            $output = parent::renderInput($entry);
            $this->options = $savedOptions;
            $askForLimit = $this->render(
                '@bazar/inputs/text.twig',
                [
                    'field' => [
                        'type' => 'text',
                        'hint' => _t('AUJ9_SUBSCRIBE_HINT_FOR_MAX'),
                        'label' => _t('AUJ9_SUBSCRIBE_LABEL_FOR_MAX'),
                        'subType' => 'number',
                        'name' => $this->getPropertyName().'_data[max]',
                        'size' => -1
                    ],
                    'value' => intval($entry[$this->getPropertyName().'_data']['max'] ?? -1)
                ]
            );
            return $askForLimit.$output;
        }
        
        $subscriptionManager = $this->getService(SubscriptionManager::class);
        if (!$subscriptionManager->checkIfFormIsOnlyOneEntry($this->getLinkedObjectName())){
            return $this->showErroMessageForForm();
        }
        return parent::renderInput($entry);
    }

    protected function showErroMessageForForm():string
    {
        return $this->render('@templates/alert-message.twig',[
            'type' => 'danger',
            'message' => _t('AUJ9_SIBSCRIBE_BAD_CONFIG_FORM')
        ]);
    }

    public function formatValuesBeforeSaveIfEditable($entry, bool $isCreation = false)
    {
        $subsciptionManager = $this->getService(SubscriptionManager::class);
        $output = parent::formatValuesBeforeSaveIfEditable($entry,$isCreation);
        $values = $this->getValues($output);
        $output = $subsciptionManager->keepOnlyBellowMax($entry,$values,$this,$output);
        $values = $this->getValues($output);
        $output = array_merge(
            $output,
            $subsciptionManager->registerNB($entry,$values,$this)
        );
        return $output;
    }

    public function getIsUserType():bool
    {
        return $this->isUserType;
    }

    public function getShowList():bool
    {
        return $this->showList;
    }

    // change return of this method to keep compatible with php 7.3 (mixed is not managed)
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'isUserType' => $this->getIsUserType(),
                'showList' => $this->getShowList()
            ]
        );
    }
}
