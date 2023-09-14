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

/**
 * only for `doryohre 4.4.1`
 */

class DateService441
{
    public function __construct(
    ) {
    }


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
