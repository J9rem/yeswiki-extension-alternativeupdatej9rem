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

namespace YesWiki\Alternativeupdatej9rem\Service;

use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use YesWiki\Alternativeupdatej9rem\Field\NbSubscriptionField;
use YesWiki\Alternativeupdatej9rem\Field\SubscribeField;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Core\Controller\AuthController;
use YesWiki\Core\Entity\Event;
use YesWiki\Core\Service\EventDispatcher;
use YesWiki\Wiki;

class SubscriptionManager implements EventSubscriberInterface
{
    protected $authController;
    protected $entryManager;
    protected $eventDispatcher;
    protected $formManager;
    protected $wiki;

    public static function getSubscribedEvents()
    {
        return [
            'subscription.new.asUser' => 'followNewSubscriptionAsUser',
            'subscription.removed.asUser' => 'followRemovedSubscriptionAsUser',
            'subscription.new.asEntry' => 'followNewSubscriptionAsEntry',
            'subscription.removed.asEntry' => 'followRemovedSubscriptionAsEntry'
        ];
    }

    public function __construct(
        AuthController $authController,
        EntryManager $entryManager,
        EventDispatcher $eventDispatcher,
        FormManager $formManager,
        Wiki $wiki
    ) {
        $this->authController = $authController;
        $this->entryManager = $entryManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->formManager = $formManager;
        $this->wiki = $wiki;
    }

    /**
     * @param Event $event
     */
    public function followNewSubscriptionAsUser($event)
    {
        $this->triggerErrorForDebug('subscription.new.asUser',$event);
    }
    /**
     * @param Event $event
     */
    public function followRemovedSubscriptionAsUser($event)
    {
        $this->triggerErrorForDebug('subscription.removed.asUser',$event);
    }

    /**
     * @param Event $event
     */
    public function followNewSubscriptionAsEntry($event)
    {
        $this->triggerErrorForDebug('subscription.new.asEntry',$event);
    }
    /**
     * @param Event $event
     */
    public function followRemovedSubscriptionAsEntry($event)
    {
        $this->triggerErrorForDebug('subscription.removed.asEntry',$event);
    }

    /**
     * trigger error for debug
     * @param string $type
     * @param Event $event
     */
    protected function triggerErrorForDebug(string $type, $event)
    {
        // trigger_error("$type... ".json_encode($event->getData()));
    }

    /**
     * register nuber of subscription in dedicated field if needed
     * by returning the field to update in entry
     * and trigger event
     * @param null|array $entry
     * @param array $values
     * @param SubscribeField $subscribeField
     * @return array
     */
    public function registerNB(?array $entry,array $values, SubscribeField $subscribeField): array
    {
        try {
            $output = $this->getNewEntryContentForNbSubscription($entry,$values);
            $this->generateEvents($entry,$values,$subscribeField);
            return $output;
        } catch (Exception $th) {
            return [];
        }
    }

    /**
     * check if the current user is registered
     * @param SubscribeField $subscribeField
     * @param array $entry
     * @param string $entryId needed if $entry is empty
     * @return bool // false if any error occurs
     */
    public function isRegistered(SubscribeField $subscribeField, array $entry, string $entryId = ''): bool
    {
        $currentUser = $this->authController->getLoggedUser();
        if (empty($currentUser['name'])){
            return false;
        }
        if (empty($entry) || empty($entry['id_fiche']) || empty($entry['id_typeannonce'])){
            if (empty($entryId)){
                return false;
            }
            $entry = $this->entryManager->getOne($entryId);
            if (empty($entry) || empty($entry['id_fiche']) || empty($entry['id_typeannonce'])){
                return false;
            }
        }

        $values = $subscribeField->getValues($entry);
        if (empty($values)){
            return false;
        }
        if ($subscribeField->getIsUserType()){
            return in_array($currentUser['name'],$values);
        } else {
            foreach ($values as $linkedEntryId) {
                if ($this->wiki->UserIsOwner($linkedEntryId)){
                    return true;
                }
            }
            return false;
        }
    }

    /**
     * toogle registration state
     * @param string $entryId
     * @param string $propertyName
     * @return array [bool $newState, bool $isError, string $errorMsg]
     */
    public function toggleRegistrationState(string $entryId, $propertyName): array
    {
        $user = $this->authController->getLoggedUser();
        $output = [
            'newState' => false,
            'isError' => true,
            'errorMsg' => ''
        ];
        if (empty($user['name'])){
            return array_merge($output,['errorMsg' => 'not connected']);
        }
        if (empty($entryId) || empty($propertyName)){
            return array_merge($output,['errorMsg' => 'empty entryId']);
        }
        $entry = $this->entryManager->getOne(
            $entryId,
            false, // not semantic
            null, // latest time
            false, // no cache
            true // byPass acls
        );
        if (empty($entry) || empty($entry['id_typeannonce'])){
            return array_merge($output,['errorMsg' => 'Not found entry']);
        }
        $subscribeField = $this->formManager->findFieldFromNameOrPropertyName($propertyName,$entry['id_typeannonce']);
        if (empty($subscribeField) || !($subscribeField instanceof SubscribeField)){
            return array_merge($output,['errorMsg' => 'Field not found']);
        }
        if (!$subscribeField->canRead($entry,$user['name']) || !$subscribeField->canEdit($entry)){
            return array_merge($output,['errorMsg' => 'User can not read or write this field']);
        }
        if ($subscribeField->getIsUserType()){
            $values = $subscribeField->getValues($entry);
            if ($this->isRegistered($subscribeField,$entry)){
                $newValues = array_filter(
                    $values,
                    function($userName) use ($user){
                        return $userName != $user['name'];
                    }
                );
            } else {
                $newValues = $values;
                $newValues[] = $user['name'];
            }
            $newEntry = $entry;
            $newEntry[$subscribeField->getPropertyName()] = implode(',', $newValues);
            $modifiedEntry = $this->saveEntryInDb($newEntry);
            $nbSubscriptionField = $this->getNbSubscriptionField($modifiedEntry);
            $newValues = $subscribeField->getValues($modifiedEntry);
            return array_merge($output,['newState' => in_array($user['name'],$newValues),'isError' => false]+(
                empty($nbSubscriptionField)
                ? []
                : ['nb' => [$nbSubscriptionField->getPropertyName(),$modifiedEntry[$nbSubscriptionField->getPropertyName()] ?? '']]
            ));
        }
        return array_merge($output,['errorMsg' => 'Part not already supported']);
    }

    /**
     * update entry in database not takig in count current GET and POST
     * @param array $newEntry
     * @return array $modifiedEntry
     */
    protected function saveEntryInDb(array $newEntry): array
    {
        $previousGet = $_GET;
        $_GET = ['wiki' => $newEntry['id_fiche']];
        $previousPost = $_POST;
        $_POST= [];
        $previousRequest = $_REQUEST;
        $_REQUEST = [];
        $newEntry['antispam'] = 1;
        $newEntry['date_maj_fiche'] = date('Y-m-d H:i:s', time());
        $modifiedEntry = $this->entryManager->update($newEntry['id_fiche'], $newEntry);

        $_GET = $previousGet;
        $_POST = $previousPost;
        $_REQUEST = $previousRequest;

        return empty($modifiedEntry) ? [] : $modifiedEntry;
    }

    /**
     * keep only new values bellow maximum number
     * @param null|array $entry
     * @param array $values
     * @param SubscribeField $subscribeField
     * @param array $fieldsToRegister
     * @return array
     */
    public function keepOnlyBellowMax(?array $entry,array $values, SubscribeField $subscribeField, array $fieldsToRegister): array
    {
        $fields = $fieldsToRegister;
        $data = $this->getChangesOnValues($entry,$values,$subscribeField);
        if (!empty($data)){
            $nbMax = $this->getMaximumNumberOfSubscriptions($entry,$subscribeField);
            if ($nbMax >= 0 && count($values) > $nbMax){
                $nbToRemoveFromValues = count($values) - $nbMax;
                $nbToRemoveFromNew = min($nbToRemoveFromValues,count($data['newValues']));
                $notToAdd = [];
                $newValues = $data['newValues'];
                for ($i=0; $i < $nbToRemoveFromNew ; $i++) { 
                    $notToAdd[] = array_pop($newValues);
                }
                $fields[$subscribeField->getPropertyName()] = implode(
                    ',',
                    array_filter(
                        $values,
                        function($v) use ($notToAdd){
                            return !in_array($v,$notToAdd);
                        }
                    )
                );
            }
        }
        return $fields;
    }

    /**
     * get maximum number of subsciptions
     * @param array $entry
     * @param SubscribeField $subscribeField
     * @return int 
     */
    protected function getMaximumNumberOfSubscriptions(array $entry,SubscribeField $subscribeField): int
    {
        $propName = $subscribeField->getPropertyName();
        if (isset($entry[$propName.'_data']['max'])){
            $max = $entry[$propName.'_data']['max'];
            if (strval($max) === strval(intval($max)) && intval($max) >= 0){
                return intval($max);
            }
        }
        return -1;
    }

    /**
     * get changes of values
     * @param null|array $entry
     * @param array $values
     * @param SubscribeField $subscribeField
     * @return array [array $newValues, array $removedValues]
     */
    protected function getChangesOnValues(?array $entry,array $values, SubscribeField $subscribeField): array
    {
        if (!empty($entry['id_fiche']) && is_string($entry['id_fiche'])){
            $oldEntry = $this->entryManager->getOne($entry['id_fiche']);
            $previousValues = empty($oldEntry)
                ? []
                : $subscribeField->getValues($oldEntry);
            $newValues = array_filter($values,function($v) use($previousValues){
                return !in_array($v,$previousValues);
            });
            $removedValues = array_filter($previousValues,function($v) use($values){
                return !in_array($v,$values);
            });
            return compact(['newValues','removedValues']);
        }
        return [];
    }

    /**
     * generate events for new or removed subscriptions
     * @param null|array $entry
     * @param array $values
     * @param SubscribeField $subscribeField
     */
    protected function generateEvents(?array $entry,array $values, SubscribeField $subscribeField)
    {
        $data = $this->getChangesOnValues($entry,$values,$subscribeField);
        if (!empty($data)){
            $postFix = $subscribeField->getIsUserType() ? 'asUser' : 'asEntry';
            foreach([
                "subscription.new.$postFix" => $data['newValues'],
                "subscription.removed.$postFix" => $data['removedValues']
            ] as $eventName => $values){
                foreach ($values as $value) {
                    $errors = $this->eventDispatcher->yesWikiDispatch($eventName, [
                        'id' => $entry['id_fiche'],
                        'data' => [
                            'value' => $value,
                            'entry' => $entry ?? [],
                            'oldEntry' => $oldEntry ?? []
                        ]
                    ]);
                    if (!empty($errors) && $this->wiki->UserIsAdmin()){
                        trigger_error(json_encode($errors));
                    }
                }
            }
        }
    }

    /**
     * return new entry content for NB Subsciption
     * @param null|array $entry
     * @param array $values
     * @return array
     */
    protected function getNewEntryContentForNbSubscription(?array $entry,array $values): array
    {
        $nbSubscriptionField = $this->getNbSubscriptionField($entry);
        if (!empty($nbSubscriptionField) && !empty($nbSubscriptionField->getPropertyName())){
            return [
                $nbSubscriptionField->getPropertyName() => empty($values)
                    ? _t('AUJ9_SUBSCRIBE_EMPTY')
                    : (
                        count($values) === 1
                        ? _t('AUJ9_SUBSCRIBE_ONE_SUBSCRIPTION')
                        : _t('AUJ9_SUBSCRIBE_MANY_SUBSCRIPTIONS',[
                            'X' => count($values)
                        ])
                    )
                ];
        }
        return [];
    }

    /**
     * find if existing NbSubscriptionField
     * @param null|array $entry
     * @return null|NbSubscriptionField
     */
    protected function getNbSubscriptionField(?array $entry): ?NbSubscriptionField
    {
        try {
            foreach ($this->getFieldsSanitized($entry) as $field) {
                if ($field instanceof NbSubscriptionField){
                    return $field;
                }
            }
            return null;
        } catch (Exception $th) {
            return null;
        }
    }

    /**
     * get form['prepared']
     * @param null|array $entry
     * @return array $fields
     * @throws Exception
     */
    protected function getFieldsSanitized(?array $entry): array
    {
        if (empty($entry)){
            throw new Exception('emtpy entry');
        }
        $formId = $entry['id_typeannonce'] ?? '';
        if (empty($formId) || strval($formId) !== strval(intval($formId)) || intval($formId) <= 0){
            throw new Exception('formId badly formatted');
        }
        $form = $this->formManager->getOne(strval($formId));
        if (empty($form['prepared'])){
            throw new Exception('emtpy form prepared');
        }
        return $form['prepared'];
    }

}
