<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YesWiki\Alternativeupdatej9rem\Service;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\Entity\Event;
use YesWiki\Core\Service\PageManager;

class DateService implements EventSubscriberInterface
{
    protected $entryManager;
    protected $followedIds;
    
    public static function getSubscribedEvents()
    {
        return [
            'entry.created' => 'followEntryChange',
            'entry.updated' => 'followEntryChange',
            'entry.deleted' => 'followEntryDeletion',
        ];
    }

    public function __construct(
        EntryManager $entryManager,
    ) {
        $this->entryManager = $entryManager;
        $this->followedIds = [];
    }

    /**
     * @param Event $event
     */
    public function followEntryChange($event)
    {
        $entry = $this->getEntry($event);
        if ($this->shouldFollowEntry($entry)){
            $this->deleteLinkedEntries($entry);
            $this->createRepetitions($entry);
        }
    }

    /**
     * @param Event $event
     */
    public function followEntryDeletion($event)
    {
        $entryBeforeDeletion = $this->getEntry($event);
        if (!empty($entryBeforeDeletion)){
            $this->deleteLinkedEntries($entryBeforeDeletion);
        }
    }

    /**
     * @param string $entryId
     */
    public function followId(string $entryId)
    {
        if (!in_array($entryId,$this->followedIds)){
            $this->followedIds[] = $entryId;
        }
    }

    /**
     * @param Event $event
     * @return array $entry
     */
    protected function getEntry(Event $event): array
    {
        $data = $event->getData();
        $entry = $data['data'] ?? [];
        return is_array($entry) ? $entry : [];
    }

    /**
     * @param array $entry
     * @return bool
     */
    protected function shouldFollowEntry(array $entry): bool
    {
        return !empty($entry['id_fiche'])
            && in_array($entry['id_fiche'],$this->followedIds);
    }

    /**
     * get changes for repetition
     * @param array $entry
     */
    protected function createRepetitions(array $entry)
    {
        if(empty($entry['bf_date_fin_evenement_data'])
            || empty($entry['bf_date_fin_evenement'])
            || empty($entry['bf_date_debut_evenement'])){
            return;
        }
        $currentStartDate = new DateTimeImmutable($entry['bf_date_debut_evenement']);
        $currentEndDate = new DateTimeImmutable($entry['bf_date_fin_evenement']);
        if (empty($currentDate) || empty($currentStartDate)){
            return;
        }
        $data = $entry['bf_date_fin_evenement_data'];
        if (empty($data['isRecurrent']) || $data['isRecurrent'] !== '1'){
            return ;
        }
        // check repetition format
        if (empty($data['repetition']) || !in_array($data['repetition'],['d','w','m','y'],true)){
            return ;
        }
        // check step format
        if (empty($data['step']) || !is_scalar($data['step']) || intval($data['step']) <= 0){
            return ;
        }
        $step = intval($data['step']);
        // check nbmaw format
        if (empty($data['nbmax']) || !is_scalar($data['nbmax']) || intval($data['nbmax']) <= 0){
            return ;
        }
        $nbmax = intval($data['nbmax']);
        if ($nbmax > 50){
            $nbax = 50;
        }
        $newStartDate = DateTime::createFromInterface($currentStartDate);
        $newEndDate = DateTime::createFromInterface($currentEndDate);
        for ($i=1; $i < $nbmax; $i++) {
            switch ($data['repetition']) {
                case 'y':
                    # code...
                    break;
                case 'm':
                    # code...
                    break;
                case 'w':
                    $currentStartYear = intval($newStartDate->format('Y'));
                    $currentStartWeek = intval($newStartDate->format('W'));
                    $nextStartWeek = $currentStartWeek + $step;
                    if ($nextStartWeek > 52){
                        $nextStartWeek = $nextStartWeek - 52;
                        $currentStartYear = $currentStartYear + 1;
                    }
                    // TODO manage day
                    $calculateNewStartDate = $newStartDate->setISODate($currentStartYear,$nextStartWeek);
                    $delta = $newStartDate->diff($calculateNewStartDate);
                    $newStartDate = $calculateNewStartDate;
                    $newEndDate = $newEndDate->add($delta);
                    break;
                case 'd':
                default:
                    $delta = new DateInterval("P{$step}D");
                    $newStartDate = $newStartDate->add($delta);
                    $newEndDate= $newEndDate->add($delta);
                    break;
            }
        }
    }

    /**
     * remove linked entries
     * @param array $entry
     */
    protected function deleteLinkedEntries(array $entry)
    {
        $entryId = $entry['id_fiche'];
        $formId = $entry['id_typeannonce'];
        $hasEndDateField = isset($entry['bf_date_fin_evenement']);
        if ($hasEndDateField && !empty($entryId) && !empty($formId)){
            $entriesToDelete = $this->entryManager->search([
                    'formsIds' => [$formId],
                    'queries' => [
                        'bf_date_fin_evenement_data' => "{\"recurrentParentId\":\"$entryId\"}"
                    ]
                ],
                true, // filter on read Acl
                false
            );
            if (!empty($entriesToDelete)){
                dump($entriesToDelete);
                exit;
            }
            foreach($entriesToDelete as $entryToDelete){
                // $this->entryManager->delete($entryToDelete['id_fiche']);
            }
        }
    }

    /**
     * only for `doryphore 4.4.1`
     */
    public function getDateTimeWithRightTimeZone(string $date): DateTimeImmutable
    {
        $dateObj = new DateTimeImmutable($date);
        if (!$dateObj){
            throw new Exception("date '$date' can not be converted to DateImmutable !");
        }
        // retrieve right TimeZone from parameters
        $defaultTimeZone = new DateTimeZone(date_default_timezone_get());
        if (!$defaultTimeZone){
            $defaultTimeZone = new DateTimeZone('GMT');
        }
        $newDate = $dateObj->setTimeZone($defaultTimeZone);
        $anchor = '+00:00';
        if (substr($date,-strlen($anchor)) == $anchor){
            // it could be an error
            $offsetToGmt = $defaultTimeZone->getOffset($newDate);
            // be careful to offset time because time is changed by setTimeZone
            $offSetAbs = abs($offsetToGmt);
            return ($offsetToGmt == 0)
            ? $newDate
            : (
                $offsetToGmt > 0
                ? $newDate->sub(new DateInterval("PT{$offSetAbs}S"))
                : $newDate->add(new DateInterval("PT{$offSetAbs}S"))
            );
        }
        return $newDate;
    }
}
