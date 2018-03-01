<?php
/**
 *  Copyright (C) 2015  Thomas Schulte <thomas@cupracer.de>
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

ini_set('include_path', ".:/usr/share/php5:/usr/share/php5/PEAR:/opt/thumbsniper");

define('DIRECTORY_ROOT', dirname(__DIR__));

require_once(DIRECTORY_ROOT . '/config/backend-config.inc.php');

use ThumbSniper\common\Settings;
use ThumbSniper\api\ApiV3;
use ThumbSniper\shared\Target;



class MigrateReferrerData extends ApiV3
{
    public function mainLoop()
    {
        try {
            $collection = new MongoCollection($this->getMongoDB(true), Settings::getMongoCollectionReferrers());

            $query = array(
                Settings::getMongoKeyReferrerAttrNumRequestsDaily() => array(
                    '$exists' => true
                )
            );

            $fields = array(
                Settings::getMongoKeyReferrerAttrId() => true,
                Settings::getMongoKeyReferrerAttrNumRequestsDaily() => true
            );

            $cursor = $collection->find($query, $fields);

            $counter = $cursor->count();

            foreach ($cursor as $doc) {
                $this->getLogger()->log(__METHOD__, "* " . $counter . " referrers left", LOG_DEBUG);

                if(!$this->migrateDailyRequests($doc))
                {
                    break;
                }

                $counter--;
            }

        } catch (Exception $e) {
            $this->getLogger()->log(__METHOD__, "exception while migrating referrers: " . $e->getMessage(), LOG_ERR);
        }
    }


    private function migrateDailyRequests($doc)
    {
        try {
            foreach ($doc[Settings::getMongoKeyReferrerAttrNumRequestsDaily()] as $day => $numRequests) {

                $dateTimeObject = DateTime::createFromFormat("Y-m-d", $day, new DateTimeZone("UTC"));;
                $beginOfDayString = $dateTimeObject->format('Y-m-d 00:00:00');
                $beginOfDayObject = DateTime::createFromFormat('Y-m-d H:i:s', $beginOfDayString);

                if (!$this->setRequestsStats($doc[Settings::getMongoKeyReferrerAttrId()], $beginOfDayObject, $numRequests)) {
                    throw(new Exception("something went wrong 1"));
//                } else {
//                    if (!$this->deleteOldDailyRequests($doc[Settings::getMongoKeyReferrerAttrId()], $day)) {
//                        throw(new Exception("something went wrong 2"));
//                    }
                }
            }

            return $this->deleteOldDailyRequestsContainer($doc[Settings::getMongoKeyReferrerAttrId()]);

        } catch (Exception $e) {
            $this->getLogger()->log(__METHOD__, "exception while migrating referrer daily request stats for " . $doc[Settings::getMongoKeyReferrerAttrId()] . ": " . $e->getMessage(), LOG_ERR);
            return false;
        }
    }



    private function setRequestsStats($referrerId, DateTime $date, $numRequests)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $originalTimezone = date_default_timezone_get();
        date_default_timezone_set("UTC");
        $this->getLogger()->log(__METHOD__,$referrerId . " - " . $date->format("d.m.Y H:I:s") . " (" . $numRequests . ")", LOG_DEBUG);
        $mongoDate = new MongoDate($date->getTimestamp());
        date_default_timezone_set($originalTimezone);

        try {
            $statsCollection = new MongoCollection($this->getMongoDB(true), Settings::getMongoCollectionReferrerStatistics());

            $statsQuery = array(
                Settings::getMongoKeyReferrerStatisticsAttrReferrerId() => $referrerId,
                Settings::getMongoKeyReferrerStatisticsAttrTs() => $mongoDate
            );

            $statsData = array(
                Settings::getMongoKeyReferrerStatisticsAttrReferrerId() => $referrerId,
                Settings::getMongoKeyReferrerStatisticsAttrTs() => $mongoDate
            );

            $statsUpdate = array(
                '$setOnInsert' => $statsData,
                '$set' => array(
                    Settings::getMongoKeyReferrerStatisticsAttrNumRequests() => $numRequests
                )
            );

            $statsOptions = array(
                'upsert' => true
            );

            if($statsCollection->update($statsQuery, $statsUpdate, $statsOptions)) {
                $this->getLogger()->log(__METHOD__, "set referrer daily request stats for " . $referrerId, LOG_DEBUG);
                return true;
            }

        } catch (Exception $e) {
            $this->getLogger()->log(__METHOD__, "exception while setting referrer daily request stats for " . $referrerId . ": " . $e->getMessage(), LOG_ERR);
        }

        return false;
    }


    private function deleteOldDailyRequests($referrerId, $date)
    {
        try {
            $collection = new MongoCollection($this->getMongoDB(true), Settings::getMongoCollectionReferrers());

            $query = array(
                Settings::getMongoKeyReferrerAttrId() => $referrerId,
                Settings::getMongoKeyReferrerAttrNumRequestsDaily() . "." . $date => array(
                    '$exists' => true
                )
            );

            $update = array(
                '$unset' => array(
                    Settings::getMongoKeyReferrerAttrNumRequestsDaily() . "." . $date => true
                )
            );

            if($collection->update($query, $update)) {
                $this->getLogger()->log(__METHOD__, "deleted referrer daily request stats for " . $referrerId . "(date: " . $date . ")", LOG_DEBUG);
                return true;
            }

        } catch (Exception $e) {
            $this->getLogger()->log(__METHOD__, "exception while deleting referrer daily request stats for " . $referrerId . "(date: " . $date . "): " . $e->getMessage(), LOG_ERR);
        }

        return false;
    }


    private function deleteOldDailyRequestsContainer($referrerId)
    {
        try {
            $collection = new MongoCollection($this->getMongoDB(true), Settings::getMongoCollectionReferrers());

            $query = array(
                Settings::getMongoKeyReferrerAttrId() => $referrerId,
                Settings::getMongoKeyReferrerAttrNumRequestsDaily() => array(
                    '$exists' => true
                )
            );

            $update = array(
                '$unset' => array(
                    Settings::getMongoKeyReferrerAttrNumRequestsDaily() => true
                )
            );

            if($collection->update($query, $update)) {
                $this->getLogger()->log(__METHOD__, "deleted referrer daily request stats container for " . $referrerId, LOG_DEBUG);
                return true;
            }

        } catch (Exception $e) {
            $this->getLogger()->log(__METHOD__, "exception while deleting referrer daily request stats container for " . $referrerId . ": " . $e->getMessage(), LOG_ERR);
        }

        return false;
    }

}


$main = new MigrateReferrerData(true);
$main->mainLoop();
