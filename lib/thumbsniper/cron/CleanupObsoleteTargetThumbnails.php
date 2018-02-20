<?php

/**
 *  Copyright (C) 2016  Thomas Schulte <thomas@cupracer.de>
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

namespace ThumbSniper\cron;

use ThumbSniper\common\Settings;
use ThumbSniper\api\ApiV3;
use MongoCollection;
use MongoTimestamp;
use DateTime;
use Exception;


class CleanupObsoleteTargetThumbnails extends ApiV3
{
    public function log($source, $msg, $severity)
    {
        $now = microtime();
        list($ms, $timestamp) = explode(" ", $now);
        $ms = substr($ms, 1, 5);

        echo date("Y-m-d H:i:s", $timestamp) . $ms . " - " . $source . " - " . $msg . "\n";
    }
    
    
    private function cleanup()
    {
        $numTargetsLeft = 0;
        $targetModel = $this->getTargetModel();

        try {
            $collection = new MongoCollection($this->getMongoDB(), Settings::getMongoCollectionTargets());
            $td = new DateTime();
            $td->modify(Settings::getObsoleteTargetThumbnailsExireStr());
            $td->setTime(0, 0, 0);

            $query = array(
                Settings::getMongoKeyTargetAttrTsLastRequested() => array(
                    '$lt' => new MongoTimestamp($td->getTimestamp())
                ),
                Settings::getMongoKeyTargetAttrTsLastUpdated() => array(
                    '$exists' => true
                )
            );

            $fields = array(
                Settings::getMongoKeyTargetAttrId() => true
            );

            $this->log(__METHOD__, "Searching for targets to clean up (threshold: " . Settings::getObsoleteTargetThumbnailsExireStr() . ")", LOG_INFO);
            $cursor = $collection->find($query, $fields);
            $cursor->timeout(-1);
            $numTargetsLeft = $cursor->count();
            $this->log(__METHOD__, "Number of targets to clean up: " . $numTargetsLeft, LOG_INFO);

            foreach ($cursor as $doc) {
                if(array_key_exists(Settings::getMongoKeyTargetAttrId(), $doc)) {                    
                    $oldForceDebug = $this->isForceDebug();
                    $this->setForceDebug(true);
                    if($targetModel->cleanupImageThumbnails($doc[Settings::getMongoKeyTargetAttrId()])) {
                        $this->log(__METHOD__, "(" . $numTargetsLeft . ") RESULT: success", LOG_INFO);
                    }else {
                        $this->log(__METHOD__, "(" . $numTargetsLeft . ") RESULT: failed", LOG_ERR);
                    }
                    $this->setForceDebug($oldForceDebug);
                    $numTargetsLeft--;
                }
            }

        } catch (Exception $e) {
            $this->log(__METHOD__, "exception while searching for targets: " . $e->getMessage(), LOG_ERR);
            return $numTargetsLeft;
        }

        return $numTargetsLeft;
    }


    public function run()
    {
        if(!Settings::isCleanupObsoleteTargetThumbnails())
        {
            $this->log(__METHOD__, "Cleanup of obsolete target thumbnails is disabled.", LOG_INFO);
            return true;
        }else
        {
            $this->log(__METHOD__, "Starting cleanup of obsolete target thumbnails.", LOG_INFO);
        }
        
        $targetsLeft = $this->cleanup();
        $runs = 1;
        $sleep = 60;

        while($targetsLeft > 0)
        {
            $runs++;
            $this->log(__METHOD__, "Sleeping " . $sleep . " seconds before " . $runs . ". run (" . $targetsLeft . " targets left).", LOG_ERR);
            sleep($sleep);
            $targetsLeft = $this->cleanup();
        }

        $this->log(__METHOD__, "No more targets left to clean up.", LOG_INFO);
        return true;
    }

}
