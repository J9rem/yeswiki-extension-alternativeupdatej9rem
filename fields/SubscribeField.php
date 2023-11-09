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
        if (!$this->showList){
            return '';
        }
        if ($this->isUserType){
            $savedOptions = $this->options;
            $this->options = $this->getFullOptionsNotSecured();
            $output = parent::renderStatic($entry);
            $this->options = $savedOptions;
        } else {
            $output = parent::renderStatic($entry);
        }
        return $this->formatOutput($output ?? '');
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
            return $output;
        }
        return parent::renderInput($entry);
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
