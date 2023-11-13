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
use YesWiki\Core\Entity\Event;
use YesWiki\Core\Service\EventDispatcher;
use YesWiki\Wiki;

class SubscriptionManager implements EventSubscriberInterface
{
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
        EntryManager $entryManager,
        EventDispatcher $eventDispatcher,
        FormManager $formManager,
        Wiki $wiki
    ) {
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
     * generate events for new or removed subscriptions
     * @param null|array $entry
     * @param array $values
     * @param SubscribeField $subscribeField
     */
    protected function generateEvents(?array $entry,array $values, SubscribeField $subscribeField)
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

            foreach([
                'subscription.new.asUser' => $newValues,
                'subscription.removed.asUser' => $removedValues
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
