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



class MigrateImageData extends ApiV3
{
    public function mainLoop()
    {
        try {
            $collection = new MongoCollection($this->getMongoDB(true), Settings::getMongoCollectionImages());

            $query = array(
                Settings::getMongoKeyImageAttrNumRequestsDaily() => array(
                    '$exists' => true
                )
            );

            $fields = array(
                Settings::getMongoKeyImageAttrId() => true,
                Settings::getMongoKeyImageAttrNumRequestsDaily() => true
            );

            $cursor = $collection->find($query, $fields);

            $counter = $cursor->count();

            foreach ($cursor as $doc) {
                $this->getLogger()->log(__METHOD__, "* " . $counter . " images left", LOG_DEBUG);

                if(!$this->migrateDailyRequests($doc))
                {
                    break;
                }

                $counter--;
            }

        } catch (Exception $e) {
            $this->getLogger()->log(__METHOD__, "exception while migrating images: " . $e->getMessage(), LOG_ERR);
        }
    }


    private function migrateDailyRequests($doc)
    {
        try {
            foreach ($doc[Settings::getMongoKeyImageAttrNumRequestsDaily()] as $day => $numRequests) {

                $dateTimeObject = DateTime::createFromFormat("Y-m-d", $day, new DateTimeZone("UTC"));;
                $beginOfDayString = $dateTimeObject->format('Y-m-d 00:00:00');
                $beginOfDayObject = DateTime::createFromFormat('Y-m-d H:i:s', $beginOfDayString);

                if (!$this->setRequestsStats($doc[Settings::getMongoKeyImageAttrId()], $beginOfDayObject, $numRequests)) {
                    throw(new Exception("something went wrong 1"));
                }
            }

            return $this->deleteOldDailyRequestsContainer($doc[Settings::getMongoKeyImageAttrId()]);

        } catch (Exception $e) {
            $this->getLogger()->log(__METHOD__, "exception while migrating image daily request stats for " . $doc[Settings::getMongoKeyImageAttrId()] . ": " . $e->getMessage(), LOG_ERR);
            return false;
        }
    }



    private function setRequestsStats($imageId, DateTime $date, $numRequests)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $originalTimezone = date_default_timezone_get();
        date_default_timezone_set("UTC");
        $this->getLogger()->log(__METHOD__,$imageId . " - " . $date->format("d.m.Y H:I:s") . " (" . $numRequests . ")", LOG_DEBUG);
        $mongoDate = new MongoDate($date->getTimestamp());
        date_default_timezone_set($originalTimezone);

        try {
            $statsCollection = new MongoCollection($this->getMongoDB(true), Settings::getMongoCollectionImageStatistics());

            $statsQuery = array(
                Settings::getMongoKeyImageStatisticsAttrImageId() => $imageId,
                Settings::getMongoKeyImageStatisticsAttrTs() => $mongoDate
            );

            $statsData = array(
                Settings::getMongoKeyImageStatisticsAttrImageId() => $imageId,
                Settings::getMongoKeyImageStatisticsAttrTs() => $mongoDate
            );

            $statsUpdate = array(
                '$setOnInsert' => $statsData,
                '$set' => array(
                    Settings::getMongoKeyImageStatisticsAttrNumRequests() => $numRequests
                )
            );

            $statsOptions = array(
                'upsert' => true
            );

            if($statsCollection->update($statsQuery, $statsUpdate, $statsOptions)) {
                $this->getLogger()->log(__METHOD__, "set image daily request stats for " . $imageId, LOG_DEBUG);
                return true;
            }

        } catch (Exception $e) {
            $this->getLogger()->log(__METHOD__, "exception while setting image daily request stats for " . $imageId . ": " . $e->getMessage(), LOG_ERR);
        }

        return false;
    }


    private function deleteOldDailyRequestsContainer($imageId)
    {
        try {
            $collection = new MongoCollection($this->getMongoDB(true), Settings::getMongoCollectionImages());

            $query = array(
                Settings::getMongoKeyImageAttrId() => $imageId,
                Settings::getMongoKeyImageAttrNumRequestsDaily() => array(
                    '$exists' => true
                )
            );

            $update = array(
                '$unset' => array(
                    Settings::getMongoKeyImageAttrNumRequestsDaily() => true
                )
            );

            if($collection->update($query, $update)) {
                $this->getLogger()->log(__METHOD__, "deleted image daily request stats container for " . $imageId, LOG_DEBUG);
                return true;
            }

        } catch (Exception $e) {
            $this->getLogger()->log(__METHOD__, "exception while deleting image daily request stats container for " . $imageId . ": " . $e->getMessage(), LOG_ERR);
        }

        return false;
    }

}


$main = new MigrateImageData(true);
$main->mainLoop();
