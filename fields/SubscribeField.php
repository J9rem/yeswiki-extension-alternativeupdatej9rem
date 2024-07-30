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
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\Service\AclService;
use YesWiki\Core\Service\AssetsManager;
use YesWiki\Core\Service\PageManager;
use YesWiki\Core\Service\UserManager;

/**
 * @Field({"subscribe"})
 */
class SubscribeField extends CheckboxEntryField
{
    protected const FIELD_SHOWLIST = 4;
    protected const FIELD_TYPE_SUBSCRIPTION = 7;
    protected const FIELD_ENTRY_CREATION_PAGE = 13;
    protected const FIELD_CAN_EDIT_LIST = 15;

    protected $canEditList;
    protected $isUserType;
    protected $optionsNotSecured;
    protected $pageToCreateEntry;
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
        $this->pageToCreateEntry = (empty($values[self::FIELD_ENTRY_CREATION_PAGE])
            || !is_string($values[self::FIELD_ENTRY_CREATION_PAGE]))
            ? ''
            : trim($values[self::FIELD_ENTRY_CREATION_PAGE]);
        $this->canEditList = (empty($values[self::FIELD_CAN_EDIT_LIST])
            || !is_string($values[self::FIELD_CAN_EDIT_LIST]))
            ? ''
            : trim($values[self::FIELD_CAN_EDIT_LIST]);
        if($this->canEditList) {
            $this->canEditList = '%'; // default owner and admins
        }
        $this->maxChars = '';
        $this->keywords = '';
        $this->queries = '';
        $this->propertyName = $this->extractRightPropertyName();
        $this->wiki = $this->getWiki();

        $this->options = null;
        $this->optionsNotSecured = null;
    }

    protected function extractRightPropertyName(): string
    {
        if (isset($this->listLabel)) {
            return $this->listLabel;
        }
        $name = $this->name;
        return preg_replace(
            '/^'
            . preg_quote($this->getType(), '/')
            . preg_quote($this->getLinkedObjectName(), '/')
            . '/',
            '',
            $name
        );
    }

    public function getOptions()
    {
        // load options only when needed but not at construct to prevent infinite loops
        if (is_null($this->options)) {
            if ($this->isUserType) {
                $this->options = $this->getOptionsFromUsers();
            } else {
                $this->options = parent::getOptions();
            }
        }
        return $this->options;
    }

    protected function getFullOptionsNotSecured()
    {
        // load options only when needed but not at construct to prevent infinite loops
        if (is_null($this->optionsNotSecured)) {
            if ($this->isUserType) {
                $this->optionsNotSecured = $this->getOptionsFromUsers(true);
            } else {
                $this->optionsNotSecured = $this->getAllOptionsFromEntries();
            }
        }
        return $this->optionsNotSecured;
    }

    public function getAllOptionsFromEntries()
    {
        $entryManager = $this->getService(EntryManager::class);

        $tabquery = [];
        if (!empty($this->queries)) {
            $tableau = array();
            $tab = explode('|', $this->queries);
            //découpe la requete autour des |
            foreach ($tab as $req) {
                $tabdecoup = explode('=', $req, 2);
                $tableau[$tabdecoup[0]] = isset($tabdecoup[1]) ? trim($tabdecoup[1]) : '';
            }
            $tabquery = array_merge($tabquery, $tableau);
        } else {
            $tabquery = '';
        }

        $fiches = $entryManager->search(
            [
                'queries' => $tabquery,
                'formsIds' => $this->getLinkedObjectName(),
                'keywords' => (!empty($this->keywords)) ? $this->keywords : ''
            ],
            false, // filter on read ACL
            false  // use Guard
        );

        $options = [];
        foreach ($fiches as $fiche) {
            $options[$fiche['id_fiche']] = $fiche['bf_titre'];
        }
        if (is_array($options)) {
            asort($options);
        }
        return $options;
    }

    /**
     * get options from users
     * @param bool $forceExtraction force extraction if not admin
     * @return array
     */
    protected function getOptionsFromUsers(bool $forceExtraction = false)
    {
        if ($forceExtraction || $this->wiki->UserIsAdmin()) {
            $names = array_values(array_map(
                function ($user) {
                    return $user['name'] ?? '';
                },
                $this->getService(UserManager::class)->getAll()
            ));
            $names = array_filter($names, function ($name) {
                return !empty($name);
            });
            return array_combine($names, $names);
        }
        return [];
    }

    protected function renderStatic($entry)
    {
        $subscriptionManager = $this->getService(SubscriptionManager::class);
        if (!$this->isUserType) {
            if (!$subscriptionManager->checkIfFormIsOnlyOneEntry($this->getLinkedObjectName())) {
                return $this->showErroMessageForForm();
            }
            if (empty($this->pageToCreateEntry)
                || empty($this->getService(PageManager::class)->getOne($this->pageToCreateEntry))) {
                return $this->showErroMessageForMissingEntryCreationPage();
            }
        }
        if (!$this->isUserType) {
            $entry = $subscriptionManager->updateEntryWithLinkedValues($entry, $this);
        }
        $output = '';
        if ($this->showList) {
            $savedOptions = $this->options;
            $this->options = $this->getFullOptionsNotSecured();
            $output = parent::renderStatic($entry);
            $this->options = $savedOptions;
            $output = $this->formatOutput($output ?? '');
        }
        $output .= $this->render(
            '@alternativeupdatej9rem/button-for-subcription.twig',
            [
                'isRegistered' => $subscriptionManager->isRegistered($this, $entry),
                'canRegister' => $this->isUserType ? true : $subscriptionManager->canRegister($this),
                'entryId' => $entry['id_fiche'] ?? '',
                'propertyName' => $this->getPropertyName(),
                'noPlace' => !$subscriptionManager->isThereAvailablePlace($entry, $this),
                'canEditEntry' => empty($entry['id_fiche']) || $this->getService(AclService::class)->hasAccess('write', $entry['id_fiche'])
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
        $anchor = preg_quote('<span class="BAZ_texte">', '/');
        $anchorUl = preg_quote('<ul>', '/');
        $randomStr = rand(1000, 100000) . '-' . (new DateTimeImmutable())->getTimestamp();
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
        return preg_replace("/($anchor)(\s*)($anchorUl)/", "$1$2$newUl", $outputToFormat);
    }

    protected function renderInput($entry)
    {
        if (!$this->getService(AclService::class)
            ->check(
                $this->canEditList,
                null,
                true,
                $entry['id_fiche'] ?? ''
            )
        ) {
            return '';
        }
        $askForLimit = $this->render(
            '@bazar/inputs/text.twig',
            [
                'field' => [
                    'type' => 'text',
                    'hint' => _t('AUJ9_SUBSCRIBE_HINT_FOR_MAX'),
                    'label' => _t('AUJ9_SUBSCRIBE_LABEL_FOR_MAX'),
                    'subType' => 'number',
                    'name' => $this->getPropertyName() . '_data[max]',
                    'size' => -1
                ],
                'value' => intval($entry[$this->getPropertyName() . '_data']['max'] ?? -1)
            ]
        );
        if ($this->isUserType) {
            $savedOptions = $this->options;
            $keys = $this->getValues($entry);
            $availablesOptions = $this->getFullOptionsNotSecured();
            $this->options = $this->wiki->UserIsAdmin()
                ? $availablesOptions
                : array_filter(
                    $availablesOptions,
                    function ($key) use ($keys) {
                        return in_array($key, $keys);
                    }
                );
            $output = parent::renderInput($entry);
            $this->options = $savedOptions;
            return $askForLimit . $output;
        }

        $subscriptionManager = $this->getService(SubscriptionManager::class);
        if (!$subscriptionManager->checkIfFormIsOnlyOneEntry($this->getLinkedObjectName())) {
            return $this->showErroMessageForForm();
        }
        if (empty($this->pageToCreateEntry)
            || empty($this->getService(PageManager::class)->getOne($this->pageToCreateEntry))) {
            return $this->showErroMessageForMissingEntryCreationPage();
        }
        $entry = $subscriptionManager->updateEntryWithLinkedValues($entry, $this);
        return $askForLimit . parent::renderInput($entry);
    }

    protected function showErroMessageForForm(): string
    {
        return $this->render('@templates/alert-message.twig', [
            'type' => 'danger',
            'message' => _t('AUJ9_SUBSCRIBE_BAD_CONFIG_FORM')
        ]);
    }

    protected function showErroMessageForMissingEntryCreationPage(): string
    {
        return $this->render('@templates/alert-message.twig', [
            'type' => 'danger',
            'message' => _t('AUJ9_SUBSCRIBE_BAD_CONFIG_ENTRY_CREATION_PAGE')
        ]);
    }

    public function formatValuesBeforeSaveIfEditable($entry, bool $isCreation = false)
    {
        $subsciptionManager = $this->getService(SubscriptionManager::class);
        $output = parent::formatValuesBeforeSaveIfEditable($entry, $isCreation);
        $values = $this->getValues($output);
        $output = $subsciptionManager->keepOnlyBellowMax($entry, $values, $this, $output);
        $values = $this->getValues($output);
        $output = array_merge(
            $output,
            $subsciptionManager->registerNB($entry, $values, $this)
        );
        return $output;
    }

    public function getIsUserType(): bool
    {
        return $this->isUserType;
    }

    public function getShowList(): bool
    {
        return $this->showList;
    }

    public function getPageToCreateEntry(): string
    {
        return $this->pageToCreateEntry;
    }

    public function getCanEditList(): string
    {
        return $this->canEditList;
    }

    // change return of this method to keep compatible with php 7.3 (mixed is not managed)
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'isUserType' => $this->getIsUserType(),
                'pageToCreateEntry' => $this->getPageToCreateEntry(),
                'showList' => $this->getShowList(),
                'canEditList' => $this->getCanEditList()
            ]
        );
    }
}
