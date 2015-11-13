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



    private function init()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        /*
        $collections = $this->db->getCollectionNames(false);

        // ACCOUNTS
        $accountCollection = new \MongoCollection($this->db, 'accounts');
        $accountCollection->ensureIndex(
            array(
                Settings::getMongoKeyAccountAttrTsAdded() => true
            )
        );
        $accountCollection->ensureIndex(
            array(
                Settings::getMongoKeyAccountAttrEmail() => true
            )
        );
        $accountCollection->ensureIndex(
            array(
                Settings::getMongoKeyAccountAttrFirstName() => true
            )
        );
        $accountCollection->ensureIndex(
            array(
                Settings::getMongoKeyAccountAttrLastName() => true
            )
        );


        // STATISTICS
        if(!in_array('statistics', $collections))
        {
        }


        // TARGETS
        $targetCollection = new \MongoCollection($this->db, 'targets');
        $targetCollection->ensureIndex(
            array(
                Settings::getMongoKeyTargetAttrTsAdded() => true
            )
        );
        $targetCollection->ensureIndex(
            array(
                Settings::getMongoKeyTargetAttrUrl() => true
            )
        );
        $targetCollection->ensureIndex(
            array(
                Settings::getMongoKeyTargetAttrCounterUpdated() => true
            )
        );
        $targetCollection->ensureIndex(
            array(
                Settings::getMongoKeyTargetAttrCounterFailed() => true
            )
        );
        $targetCollection->ensureIndex(
            array(
                Settings::getMongoKeyTargetAttrNumRequests() => true
            )
        );
        $targetCollection->ensureIndex(
            array(
                Settings::getMongoKeyTargetAttrTsLastRequested() => true
            )
        );
        $targetCollection->ensureIndex(
            array(
                Settings::getMongoKeyTargetAttrMimeType() => true
            )
        );
        $targetCollection->ensureIndex(
            array(
                'images.id' => true
            )
        );

        $targetCollection->ensureIndex(
            array(
                'referrers.id' => true
            )
        );

        $targetCollection->ensureIndex(
            array(
                'useragents.id' => true
            )
        );

	    $targetCollection->ensureIndex(
		    array(
			    Settings::getMongoKeyTargetAttrTsLastUpdated() => true
		    )
	    );

	    $targetCollection->ensureIndex(
		    array(
			    Settings::getMongoKeyTargetAttrFileId() => true
		    )
	    );

		$targetCollection->ensureIndex(
			array(
				Settings::getMongoKeyTargetAttrTsLastUpdated() => true,
				Settings::getMongoKeyTargetAttrFileId() => true
			)
		);


        //IMAGES
        $imagesCollection = new \MongoCollection($this->db, 'images');
        $imagesCollection->ensureIndex(
            array(
                'targetId' => true
            )
        );


        // REFERRERS
        $referrerCollection = new \MongoCollection($this->db, 'referrers');
        $referrerCollection->ensureIndex(
            array(
                Settings::getMongoKeyReferrerAttrTsAdded() => true
            )
        );
        $referrerCollection->ensureIndex(
            array(
                Settings::getMongoKeyReferrerAttrTsLastSeen() => true
            )
        );
        $referrerCollection->ensureIndex(
            array(
                Settings::getMongoKeyReferrerAttrUrlBase() => true
            )
        );
        $referrerCollection->ensureIndex(
            array(
                Settings::getMongoKeyReferrerAttrNumRequests() => true
            )
        );

//        $referrerCollection->ensureIndex(
//            array(
//                'targets.id' => true
//            )
//        );

        // REFERRER_DEEPLINKS
        $referrerDeeplinkCollection = new \MongoCollection($this->db, 'referrer_deeplinks');
        $referrerDeeplinkCollection->ensureIndex(
            array(
                Settings::getMongoKeyReferrerDeeplinkAttrTsAdded() => true
            )
        );
        $referrerDeeplinkCollection->ensureIndex(
            array(
                Settings::getMongoKeyReferrerDeeplinkAttrUrl() => true
            )
        );
        $referrerDeeplinkCollection->ensureIndex(
            array(
                Settings::getMongoKeyReferrerDeeplinkAttrReferrerId() => true
            )
        );
        $referrerDeeplinkCollection->ensureIndex(
            array(
                Settings::getMongoKeyReferrerDeeplinkAttrNumRequests() => true
            )
        );


        //IMAGES_CACHE
        $imagesCacheCollection = new \MongoCollection($this->db, 'images_cache');
        $imagesCacheCollection->ensureIndex(
            array(
                'tsAdded' => true
            ),
            array(
                'expireAfterSeconds' => Settings::getMongoImageCacheExpire()
            )
        );
        $imagesCacheCollection->ensureIndex(
            array(
                'imageId' => true,
                'branded' => true
            )
        );


        //QUEUE_JOBS_MASTERS
        $queueJobsMasterCollection = new \MongoCollection($this->db, 'queue_jobs_masters');
        $queueJobsMasterCollection->ensureIndex(
            array(
                'mode' => true
            )
        );
        $queueJobsMasterCollection->ensureIndex(
            array(
                'tsAdded' => true
            )
        );
        $queueJobsMasterCollection->ensureIndex(
            array(
                'priority' => -1
            )
        );
        $queueJobsMasterCollection->ensureIndex(
            array(
                'priority' => -1,
                'tsAdded' => 1
            )
        );


        //MASTERS.FILES
        $mastersFilesCollection = new \MongoCollection($this->db, 'masters.files');
        $mastersFilesCollection->ensureIndex(
            array(
                'filename' => true
            )
        );


        //THUMBNAILS.FILES
        $thumbnailsFilesCollection = new \MongoCollection($this->db, 'thumbnails.files');
        $thumbnailsFilesCollection->ensureIndex(
            array(
                'filename' => true
            )
        );


        // USERAGENTS
        $userAgentCollection = new \MongoCollection($this->db, 'useragents');
        $userAgentCollection->ensureIndex(
            array(
                Settings::getMongoKeyUserAgentAttrTsAdded() => true
            )
        );
        $userAgentCollection->ensureIndex(
            array(
                Settings::getMongoKeyUserAgentAttrTsLastSeen() => true
            )
        );
        $userAgentCollection->ensureIndex(
            array(
                Settings::getMongoKeyUserAgentAttrDescription() => true
            )
        );
        $userAgentCollection->ensureIndex(
            array(
                Settings::getMongoKeyUserAgentAttrNumRequests() => true
            )
        );
*/

//        $userAgentCollection->ensureIndex(
//            array(
//                'targets.id' => true
//            )
//        );
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
