<?php

/*
 * Copyright (C) 2015  Thomas Schulte <thomas@cupracer.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace ThumbSniper\db;

require_once('vendor/autoload.php');



use ThumbSniper\common\Logger;
use MongoClient;
use ThumbSniper\common\Settings;


class Mongo
{
    private $logger;

    private $host;
    private $port;
    private $username;
    private $password;
    private $dbname;

    /** @var MongoClient */
    private $client;

    /** @var \MongoDB */
    private $db;


    function __construct(Logger $logger, $host, $port, $username, $password, $dbname)
    {
        $this->logger = $logger;
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->dbname = $dbname;

        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);
    }


    function __destruct()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        //Disabled explicit closing to profit from persistent connections
        //$this->close();
    }


    public function getConnection($disableTimeout = false)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $mongoOptions = array();

        if($disableTimeout) {
            $mongoOptions = array(
                "socketTimeoutMS" => -1
            );
        }

        if ($this->client == NULL) {
            try {
                $client = new MongoClient("mongodb://" . $this->username . ":" . $this->password . "@" . $this->host . ":" . $this->port . "/" . $this->dbname, $mongoOptions);
                $db = $client->selectDB($this->dbname);

                // assign (hopefully) working connection to class var
                $this->client = $client;
                $this->db = $db;

		//TODO: find a well-performing way to create indexes if they don't yet exist.
                //$this->init();

                $this->logger->log(__METHOD__, "created new MongoDB connection", LOG_DEBUG);
            } catch (\Exception $e) {
                $this->logger->log(__METHOD__, "Error while connecting to MongoDB: " . $e->getMessage(), LOG_ERR);
                echo $e->getMessage();
            }
        }

        return $this->db;
    }


    private function ensureAccountIndexes()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $collection = new \MongoCollection($this->db, Settings::getMongoCollectionAccounts());

        $collection->createIndex(
            array(
                Settings::getMongoKeyAccountAttrFirstName() => 1,
                Settings::getMongoKeyAccountAttrLastName() => 1
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyAccountAttrId() => 1,
                Settings::getMongoKeyAccountAttrFirstName() => 1,
                Settings::getMongoKeyAccountAttrLastName() => 1,
                Settings::getMongoKeyAccountAttrEmail() => 1
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyAccountAttrId() => 1,
                Settings::getMongoKeyAccountAttrNumRequestsDaily() => 1
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyAccountAttrEmail() => 1
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyAccountAttrId() => 1,
                Settings::getMongoKeyAccountAttrApiKey() => 1
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyAccountAttrApiKey() => 1
            )
        );
    }


    private function ensureTargetIndexes()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $collection = new \MongoCollection($this->db, Settings::getMongoCollectionTargets());

        $collection->createIndex(
            array(
                Settings::getMongoKeyTargetAttrId() => 1,
                Settings::getMongoKeyTargetAttrTsCheckedOut() => 1
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyTargetAttrUrl() => 1
            )
        );

        $collection->createIndex(
            array(
                'referrers.id' => 1
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyTargetAttrId() => 1,
                'referrers.id' => 1
            )
        );

        $collection->createIndex(
            array(
                'referrers.id' => 1,
                Settings::getMongoKeyReferrerAttrAccountId() => 1
            )
        );

        $collection->createIndex(
            array(
                'referrers.id' => 1,
                Settings::getMongoKeyReferrerAttrAccountId() => 1,
                Settings::getMongoKeyTargetAttrUrl() => 1
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyTargetAttrCounterFailed() => 1
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyTargetAttrId() => 1,
                'useragents.id' => 1
            )
        );

        $collection->createIndex(
            array(
                'useragents.id' => 1
            )
        );

        $collection->createIndex(
            array(
                'useragents.id' => 1,
                Settings::getMongoKeyTargetAttrUrl() => 1
            )
        );
    }


    private function ensureImageIndexes()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $collection = new \MongoCollection($this->db, Settings::getMongoCollectionImages());

        $collection->createIndex(
            array(
                Settings::getMongoKeyImageAttrId() => 1,
                Settings::getMongoKeyImageAttrNumRequestsDaily() => 1
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyImageAttrId() => 1,
                Settings::getMongoKeyImageAttrTsCheckedOut() => 1
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyImageAttrId() => 1,
                Settings::getMongoKeyImageAttrFileNameSuffix() => 1
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyImageAttrTargetId() => 1
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyImageAttrTargetId() => 1,
                Settings::getMongoKeyImageAttrId() => 1,
                Settings::getMongoKeyImageAttrFileNameSuffix() => 1
            )
        );
    }


    private function ensureReferrerIndexes()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $collection = new \MongoCollection($this->db, Settings::getMongoCollectionReferrers());

        $collection->createIndex(
            array(
                Settings::getMongoKeyReferrerAttrId() => 1,
                Settings::getMongoKeyReferrerAttrAccountId() => 1
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyReferrerAttrId() => 1,
                'targets.id' => 1
            )
        );

        $collection->createIndex(
            array(
                'targets.id' => 1
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyReferrerAttrUrlBase() => 1,
                'targets.id' => 1
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyReferrerAttrAccountId() => 1,
                'targets.id' => 1
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyReferrerAttrAccountId() => 1,
                Settings::getMongoKeyReferrerAttrUrlBase() => 1,
                'targets.id' => 1
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyReferrerAttrId() => 1
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyReferrerAttrId() => 1,
                Settings::getMongoKeyReferrerAttrUrlBase() => 1
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyReferrerAttrId() => 1,
                Settings::getMongoKeyReferrerAttrNumRequestsDaily() => 1
            )
        );
    }


    private function ensureReferrerDeeplinkIndexes()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $collection = new \MongoCollection($this->db, Settings::getMongoCollectionReferrerDeeplinks());

        $collection->createIndex(
            array(
                Settings::getMongoKeyReferrerDeeplinkAttrReferrerId() => 1
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyReferrerDeeplinkAttrReferrerId() => 1,
                Settings::getMongoKeyReferrerDeeplinkAttrUrl() => 1
            )
        );
    }


    private function ensureReferrerStatisticsIndexes()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $collection = new \MongoCollection($this->db, Settings::getMongoCollectionReferrerStatistics());

        $collection->createIndex(
            array(
                Settings::getMongoKeyReferrerStatisticsAttrReferrerId() => 1
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyReferrerStatisticsAttrReferrerId() => 1,
                Settings::getMongoKeyReferrerStatisticsAttrTs() => 1
            )
        );
    }


    private function ensureUserAgentIndexes()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $collection = new \MongoCollection($this->db, Settings::getMongoCollectionUserAgents());

        $collection->createIndex(
            array(
                Settings::getMongoKeyUserAgentAttrId() => 1,
                'targets.id' => 1
            )
        );

        $collection->createIndex(
            array(
                'targets.id' => 1
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyUserAgentAttrId() => 1,
                'targets.id' => 1
            )
        );

        $collection->createIndex(
            array(
                'targets.id' => 1,
                Settings::getMongoKeyUserAgentAttrDescription() => 1
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyUserAgentAttrDescription() => 1
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyUserAgentAttrId() => 1,
                Settings::getMongoKeyUserAgentAttrNumRequestsDaily() => 1
            )
        );
    }


    private function ensureUserAgentStatisticsIndexes()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $collection = new \MongoCollection($this->db, Settings::getMongoCollectionUserAgentStatistics());

        $collection->createIndex(
            array(
                Settings::getMongoKeyUserAgentStatisticsAttrUserAgentId() => 1
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyUserAgentStatisticsAttrUserAgentId() => 1,
                Settings::getMongoKeyUserAgentStatisticsAttrTs() => 1
            )
        );
    }

    private function ensurReferrerDeeplinkStatisticsIndexes()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $collection = new \MongoCollection($this->db, Settings::getMongoCollectionReferrerDeeplinkStatistics());

        $collection->createIndex(
            array(
                Settings::getMongoKeyReferrerDeeplinkStatisticsAttrReferrerDeeplinkId() => 1
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyReferrerDeeplinkStatisticsAttrReferrerDeeplinkId() => 1,
                Settings::getMongoKeyReferrerDeeplinkStatisticsAttrTs() => 1
            )
        );
    }


    public function init()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);


//        $collections = $this->db->getCollectionNames(false);

        $this->ensureAccountIndexes();
        $this->ensureTargetIndexes();
        $this->ensureImageIndexes();
        $this->ensureReferrerIndexes();
        $this->ensureReferrerDeeplinkIndexes();
        $this->ensureUserAgentIndexes();

        $this->ensureReferrerStatisticsIndexes();
        $this->ensureUserAgentStatisticsIndexes();
        $this->ensurReferrerDeeplinkStatisticsIndexes();

        /*



        // STATISTICS
        if(!in_array('statistics', $collections))
        {
        }

        
        //QUEUE_JOBS_MASTERS
        $queueJobsMasterCollection = new \MongoCollection($this->db, 'queue_jobs_masters');
        $queueJobsMasterCollection->createIndex(
            array(
                'mode' => true
            )
        );
        $queueJobsMasterCollection->createIndex(
            array(
                'tsAdded' => true
            )
        );
        $queueJobsMasterCollection->createIndex(
            array(
                'priority' => -1
            )
        );
        $queueJobsMasterCollection->createIndex(
            array(
                'priority' => -1,
                'tsAdded' => 1
            )
        );


        //MASTERS.FILES
        $mastersFilesCollection = new \MongoCollection($this->db, 'masters.files');
        $mastersFilesCollection->createIndex(
            array(
                'filename' => true
            )
        );


        //THUMBNAILS.FILES
        $thumbnailsFilesCollection = new \MongoCollection($this->db, 'thumbnails.files');
        $thumbnailsFilesCollection->createIndex(
            array(
                'filename' => true
            )
        );
*/

    }



    public function close()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        if($this->client) {
            $this->client->close();
        }
        $this->client = NULL;
    }
}
