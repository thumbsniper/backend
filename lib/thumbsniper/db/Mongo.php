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


    public function getConnection()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        if ($this->client == NULL) {
            try {
                $client = new MongoClient("mongodb://" . $this->username . ":" . $this->password . "@" . $this->host . ":" . $this->port . "/" . $this->dbname);
                $db = $client->selectDB($this->dbname);

                // assign (hopefully) working connection to class var
                $this->client = $client;
                $this->db = $db;

                $this->init();

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
                Settings::getMongoKeyAccountAttrFirstName() => true,
                Settings::getMongoKeyAccountAttrLastName() => true
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyAccountAttrId() => true,
                Settings::getMongoKeyAccountAttrFirstName() => true,
                Settings::getMongoKeyAccountAttrLastName() => true,
                Settings::getMongoKeyAccountAttrEmail() => true
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyAccountAttrId() => true,
                Settings::getMongoKeyAccountAttrNumRequestsDaily() => true
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyAccountAttrEmail() => true
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyAccountAttrId() => true,
                Settings::getMongoKeyAccountAttrApiKey() => true
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyAccountAttrApiKey() => true
            )
        );
    }


    private function ensureTargetIndexes()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $collection = new \MongoCollection($this->db, Settings::getMongoCollectionTargets());

        $collection->createIndex(
            array(
                Settings::getMongoKeyTargetAttrId() => true,
                Settings::getMongoKeyTargetAttrTsCheckedOut() => true
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyTargetAttrUrl() => true
            )
        );

        $collection->createIndex(
            array(
                'referrers.id' => true
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyTargetAttrId() => true,
                'referrers.id' => true
            )
        );

        $collection->createIndex(
            array(
                'referrers.id' => true,
                Settings::getMongoKeyReferrerAttrAccountId() => true
            )
        );

        $collection->createIndex(
            array(
                'referrers.id' => true,
                Settings::getMongoKeyReferrerAttrAccountId() => true,
                Settings::getMongoKeyTargetAttrUrl() => true
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyTargetAttrCounterFailed() => true
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyTargetAttrId() => true,
                'useragents.id' => true
            )
        );

        $collection->createIndex(
            array(
                'useragents.id' => true
            )
        );

        $collection->createIndex(
            array(
                'useragents.id' => true,
                Settings::getMongoKeyTargetAttrUrl() => true
            )
        );
    }


    private function ensureImageIndexes()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $collection = new \MongoCollection($this->db, Settings::getMongoCollectionImages());

        $collection->createIndex(
            array(
                Settings::getMongoKeyImageAttrId() => true,
                Settings::getMongoKeyImageAttrNumRequestsDaily() => true
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyImageAttrId() => true,
                Settings::getMongoKeyImageAttrTsCheckedOut() => true
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyImageAttrId() => true,
                Settings::getMongoKeyImageAttrFileNameSuffix() => true
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyImageAttrTargetId() => true
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyImageAttrTargetId() => true,
                Settings::getMongoKeyImageAttrId() => true,
                Settings::getMongoKeyImageAttrFileNameSuffix() => true
            )
        );
    }


    private function ensureReferrerIndexes()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $collection = new \MongoCollection($this->db, Settings::getMongoCollectionReferrers());

        $collection->createIndex(
            array(
                Settings::getMongoKeyReferrerAttrId() => true,
                Settings::getMongoKeyReferrerAttrAccountId() => true
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyReferrerAttrId() => true,
                'targets.id' => true
            )
        );

        $collection->createIndex(
            array(
                'targets.id' => true
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyReferrerAttrUrlBase() => true,
                'targets.id' => true
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyReferrerAttrAccountId() => true,
                'targets.id' => true
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyReferrerAttrAccountId() => true,
                Settings::getMongoKeyReferrerAttrUrlBase() => true,
                'targets.id' => true
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyReferrerAttrId() => true
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyReferrerAttrId() => true,
                Settings::getMongoKeyReferrerAttrUrlBase()
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyReferrerAttrId() => true,
                Settings::getMongoKeyReferrerAttrNumRequestsDaily() => true
            )
        );
    }


    private function ensureReferrerDeeplinkIndexes()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $collection = new \MongoCollection($this->db, Settings::getMongoCollectionReferrerDeeplinks());

        $collection->createIndex(
            array(
                Settings::getMongoKeyReferrerDeeplinkAttrReferrerId() => true
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyReferrerDeeplinkAttrReferrerId() => true,
                Settings::getMongoKeyReferrerDeeplinkAttrUrl() => true
            )
        );
    }


    private function ensureUserAgentIndexes()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $collection = new \MongoCollection($this->db, Settings::getMongoCollectionUserAgents());

        $collection->createIndex(
            array(
                Settings::getMongoKeyUserAgentAttrId() => true,
                'targets.id' => true
            )
        );

        $collection->createIndex(
            array(
                'targets.id' => true
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyUserAgentAttrId() => true,
                'targets.id' => true
            )
        );

        $collection->createIndex(
            array(
                'targets.id' => true,
                Settings::getMongoKeyUserAgentAttrDescription() => true
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyUserAgentAttrDescription() => true
            )
        );

        $collection->createIndex(
            array(
                Settings::getMongoKeyUserAgentAttrId() => true,
                Settings::getMongoKeyUserAgentAttrNumRequestsDaily() => true
            )
        );
    }


    private function init()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);


//        $collections = $this->db->getCollectionNames(false);

        $this->ensureAccountIndexes();
        $this->ensureTargetIndexes();
        $this->ensureImageIndexes();
        $this->ensureReferrerIndexes();
        $this->ensureReferrerDeeplinkIndexes();
        $this->ensureUserAgentIndexes();

        /*



        // STATISTICS
        if(!in_array('statistics', $collections))
        {
        }



        //IMAGES_CACHE
        $imagesCacheCollection = new \MongoCollection($this->db, 'images_cache');
        $imagesCacheCollection->createIndex(
            array(
                'tsAdded' => true
            ),
            array(
                'expireAfterSeconds' => Settings::getMongoImageCacheExpire()
            )
        );
        $imagesCacheCollection->createIndex(
            array(
                'imageId' => true,
                'branded' => true
            )
        );


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
