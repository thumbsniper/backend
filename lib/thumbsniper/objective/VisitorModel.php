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

namespace ThumbSniper\objective;


use ThumbSniper\common\Logger;
use ThumbSniper\common\Settings;
use ThumbSniper\shared\Target;

use Exception;

use MongoDB;
use MongoCollection;
use MongoTimestamp;
use MongoCursor;


class VisitorModel
{
    /** @var Logger */
    protected $logger;

    /** @var MongoDB */
    protected $mongoDB;



    function __construct(MongoDB $mongoDB, Logger $logger)
    {
        $this->mongoDB = $mongoDB;
        $this->logger = $logger;
    }



    private static function load($data) {
        $visitor = new Visitor();

        if(!is_array($data))
        {
            return false;
        }

        $visitor->setId(isset($data[Settings::getMongoKeyVisitorAttrId()]) ? $data[Settings::getMongoKeyVisitorAttrId()] : null);
        $visitor->setAddress(isset($data[Settings::getMongoKeyVisitorAttrAddress()]) ? $data[Settings::getMongoKeyVisitorAttrAddress()] : null);
        $visitor->setAddressType(isset($data[Settings::getMongoKeyVisitorAttrAddressType()]) ? $data[Settings::getMongoKeyVisitorAttrAddressType()] : null);
        $visitor->setNumRequests(isset($data[Settings::getMongoKeyVisitorAttrNumRequests()]) ? $data[Settings::getMongoKeyVisitorAttrNumRequests()] : 0);

        $tsAdded = null;
        if(isset($data[Settings::getMongoKeyVisitorAttrTsAdded()]))
        {
            if($data[Settings::getMongoKeyVisitorAttrTsAdded()] instanceof MongoTimestamp) {
                /** @var MongoTimestamp $mongoTs */
                $mongoTs = $data[Settings::getMongoKeyVisitorAttrTsAdded()];
                $tsAdded = $mongoTs->sec;
            }
        }
        $visitor->setTsAdded($tsAdded);

        $tsLastSeen = null;
        if(isset($data[Settings::getMongoKeyVisitorAttrTsLastSeen()]))
        {
            if($data[Settings::getMongoKeyVisitorAttrTsLastSeen()] instanceof MongoTimestamp) {
                /** @var MongoTimestamp $mongoTs */
                $mongoTs = $data[Settings::getMongoKeyVisitorAttrTsLastSeen()];
                $tsLastSeen = $mongoTs->sec;
            }
        }
        $visitor->setTsLastSeen($tsLastSeen);

        $tsLastUpdated = null;
        if(isset($data[Settings::getMongoKeyVisitorAttrTsLastUpdated()]))
        {
            if($data[Settings::getMongoKeyVisitorAttrTsLastUpdated()] instanceof MongoTimestamp) {
                /** @var MongoTimestamp $mongoTs */
                $mongoTs = $data[Settings::getMongoKeyVisitorAttrTsLastUpdated()];
                $tsLastUpdated = $mongoTs->sec;
            }
        }
        $visitor->setTsLastUpdated($tsLastUpdated);

        return $visitor;
    }



    private function calculateId($address)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        return md5($address);
    }


    public function getById($id)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $visitor = NULL;

        try
        {
            $collection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionVisitors());

            $query = array(
                Settings::getMongoKeyVisitorAttrId() => $id
            );

//            $fields = array(
//                'targets' => false
//            );

            $data = $collection->findOne($query);

            if(is_array($data)) {
                $visitor = VisitorModel::load($data);
                $visitor->setBlacklisted($this->isBlacklisted($visitor->getId()));
                $this->logger->log(__METHOD__, "found visitor " . $visitor->getId() . " in MongoDB", LOG_DEBUG);
            }
        } catch(Exception $e)
        {
            $this->logger->log(__METHOD__, "exception: " . $e->getMessage(), LOG_ERR);
            die();
        }

        if (!$visitor instanceof Visitor) {
            $this->logger->log(__METHOD__, "visitor does not exist", LOG_INFO);
            return null;
        }

        return $visitor;
    }



    public function getOrCreateByAddress($address, $addressType)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        if ($address == NULL || empty($address)) {
            //TODO: log message
            return null;
        }

        if ($addressType == NULL || empty($addressType)) {
            //TODO: log message
            return null;
        }

        $visitorId = $this->calculateId($address);
        $visitor = $this->getById($visitorId);

        do {
            $this->logger->log(__METHOD__, "start loop", LOG_DEBUG);

            if (!$visitor instanceof Visitor) {
                $this->logger->log(__METHOD__, "creating new visitor record (" . $address . ")", LOG_INFO);

                // save new referrer to DB

                try {
                    $collection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionVisitors());

                    $query = array(
                        Settings::getMongoKeyVisitorAttrId() => $visitorId
                    );

                    $mongoNow = new MongoTimestamp();

                    $data = array(
                        Settings::getMongoKeyVisitorAttrAddress() => $address,
                        Settings::getMongoKeyVisitorAttrAddressType() => $addressType,
                        Settings::getMongoKeyVisitorAttrTsAdded() => $mongoNow,
                        Settings::getMongoKeyVisitorAttrTsLastSeen() => $mongoNow
//                        Settings::getMongoKeyReferrerAttrNumRequests() => 1,
//                        Settings::getMongoKeyReferrerAttrNumRequestsDaily() . "." . $today => 1
                    );

                    $update = array(
                        '$setOnInsert' => $data
                    );

                    $options = array(
                        'upsert' => true
                    );


                    $result = $collection->update($query, $update, $options);

                    if(is_array($result)) {
                        if($result['n'] == true) {
                            $this->logger->log(__METHOD__, "new visitor created: " . $visitorId, LOG_INFO);

                            // update statistics counter
                            $this->incrementNewVisitorsDailyStats();
                        }elseif($result['updatedExisting']) {
                            $this->logger->log(__METHOD__, "updated visitor " . $visitorId . " instead of creating a new one. Works fine. :-)", LOG_INFO);
                        }
                    }

                    //$this->logger->log(__METHOD__, "referrer = " . $referrerId . " - data = " . print_r($result, true), LOG_ERR);
                } catch (Exception $e) {
                    $this->logger->log(__METHOD__, "exception while creating visitor " . $address . ": " . $e->getMessage(), LOG_ERR);
                    $this->logger->log(__METHOD__, "going to die now", LOG_ERR);
                    die();
                }

                $visitor = $this->getById($visitorId);
            }
        }while(!$visitor instanceof UserAgent);
        $this->logger->log(__METHOD__, "end loop", LOG_DEBUG);

        return $visitor;
    }



/*    public function addTargetMapping(UserAgent $userAgent, Target $target)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        try {
            $collection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionUserAgents());

            $data = array(
                'id' => $target->getId()
            );

            $query = array(
                Settings::getMongoKeyUserAgentAttrId() => $userAgent->getId(),
                'targets.id' => array(
                    '$ne' => $target->getId()
                ));

            $update = array(
                '$push' => array(
                    'targets' => $data
                ));

            if($collection->update($query, $update)) {
                $this->logger->log(__METHOD__, "added target " . $target->getId() . " to user agent " . $userAgent->getId(), LOG_DEBUG);
            }

        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while adding target " . $target->getId() . " to user agent " . $userAgent->getId() . ": " . $e->getMessage(), LOG_ERR);
        }

        //TODO: result auswerten
        return true;
    }



    public function removeTargetMapping(Target $target)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        try {
            $collection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionUserAgents());

            $targetData = array(
                'id' => $target->getId()
            );

            $userAgentQuery = array(
                'targets.id' => array(
                    '$eq' => $target->getId()
                ));

            $userAgentUpdate = array(
                '$pull' => array(
                    'targets' => $targetData
                ));

            if($collection->update($userAgentQuery, $userAgentUpdate)) {
                $this->logger->log(__METHOD__, "removed target " . $target->getId() . " from user agents", LOG_DEBUG);
                return true;
            }

        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while removing target " . $target->getId() . " from user agents: " . $e->getMessage(), LOG_ERR);
        }

        //TODO: result auswerten
        return true;
    }



    // what referrers link to a target?
    public function getTargetUserAgents($targetId, $accountId = NULL, $orderby, $orderDirection, $limit, $offset, $where)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $userAgents = array();

        //TODO: $accountId, $search and order

        try {
            $collection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionUserAgents());

            $query = array(
                '$query' => array(
                    'targets.id' => $targetId
                )
            );

            if ($orderDirection == "asc") {
                $query['$orderby'] = array(
                    $orderby => 1
                );
            } else {
                $query['$orderby'] = array(
                    $orderby => -1
                );
            }

            //TODO: also search for _id content?

            if ($where) {
                //TODO: ist es okay, das $query Array hier so neu aufzubauen?
//                $oldQuery = $query['$query'];
//                $query['$query'] = array();
//                $query['$query']['$and'] = $oldQuery;
//                $query['$query']['$and'][] = array(
//                    Settings::getMongoKeyReferrerAttrUrlBase() => array(
//                        '$regex' => $where
//                    )
//                );

                $query['$query'][Settings::getMongoKeyUserAgentAttrDescription()] = array(
                    '$regex' => $where,
                    '$options' => 'i'
                );
            }

            $fields = array(
                Settings::getMongoKeyUserAgentAttrId() => true
            );

            /** @var MongoCursor $cursor */
/*            $cursor = $collection->find($query, $fields);
            $cursor->skip($offset);
            $cursor->limit($limit);

            foreach($cursor as $data)
            {
                //$this->logger->log(__METHOD__, "HIER: " . print_r($data, true), LOG_DEBUG);
                $userAgent = $this->getById($data[Settings::getMongoKeyUserAgentAttrId()]);

                if($userAgent instanceof UserAgent)
                {
                    $userAgents[] = $userAgent;
                }
            }
        }catch (Exception $e) {
            $this->logger->log(__METHOD__, "could not find user agents of target " . $targetId . ": " . $e->getMessage(), LOG_DEBUG);
        }

        return $userAgents;
    }*/

/*
    /**
     * @param null $targetId
     * @param null $where
     * @param null $accountId
     * @return int
     */
/*    public function getNumUserAgents($targetId = null, $where = null, $accountId = null)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $numUserAgents = 0;

        //TODO: filter by targetId

        try {
            $collection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionUserAgents());

            $query = array();

            if($targetId)
            {
                $query['targets.id'] = $targetId;
            }

            if ($where)
            {
                if($targetId) {
                    //TODO: ist es okay, das $query Array hier so neu aufzubauen?
                    $oldQuery = $query;
                    $query = array('$and' => array());
                    $query['$and'][] = $oldQuery;
                    $query['$and'][][Settings::getMongoKeyUserAgentAttrDescription()] = array(
                        '$regex' => $where,
                        '$options' => 'i'
                    );
                }else {
                    $query[Settings::getMongoKeyUserAgentAttrDescription()] = array(
                        '$regex' => $where,
                        '$options' => 'i'
                    );
                }
            }

            $numUserAgents = $collection->count($query);
            $this->logger->log(__METHOD__, "successfully counted user agents", LOG_DEBUG);
        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while counting user agents: " . $e->getMessage(), LOG_ERR);
        }

        return $numUserAgents;
    }*/



    public function getVisitors($orderby = 'tsAdded', $orderDirection = 'asc', $limit = PHP_INT_MAX, $offset = 0, $where = NULL, $accountId = null)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $visitors = array();

        try {
            $collection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionVisitors());

            $query = array(
                '$query' => array()
            );

            if ($orderDirection == "asc") {
                $query['$orderby'] = array(
                    $orderby => 1
                );
            } else {
                $query['$orderby'] = array(
                    $orderby => -1
                );
            }

            //TODO: also search for _id content?

            if ($where) {
                //TODO: ist es okay, das $query Array hier so neu aufzubauen?
                $oldQuery = $query['$query'];
                $query['$query'] = array();
                if(!empty($oldQuery)) {
                    $query['$query']['$and'][] = $oldQuery;
                }
                $query['$query']['$and'][] = array(
                    Settings::getMongoKeyVisitorAttrAddress() => array(
                        '$regex' => $where,
                        '$options' => 'i'
                    )
                );
            }

            $fields = array(
                Settings::getMongoKeyVisitorAttrId() => true
            );

            //$this->logger->log(__METHOD__, "HIER: " . print_r($query, true), LOG_DEBUG);

            /** @var MongoCursor $referrerCursor */
            $cursor = $collection->find($query, $fields); //$query, $fields);
            $cursor->skip($offset);
            $cursor->limit($limit);

            foreach ($cursor as $doc) {
                $v = $this->getById($doc[Settings::getMongoKeyVisitorAttrId()]);
                //$this->logger->log(__METHOD__, "HIER: " . print_r($r, true), LOG_DEBUG);

                if ($v instanceof Visitor) {
                    $visitors[] = $v;
                }
            }
        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while searching for visitors: " . $e->getMessage(), LOG_ERR);
        }

        return $visitors;
    }



    private function incrementNewVisitorsDailyStats()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $now = time();
        $today = date("Y-m-d", $now);

        try {
            $statsCollection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionStatistics());

            $statsQuery = array(
                '_id' => 'visitors'
            );

            $statsUpdate = array(
                '$inc' => array(
                    'new' . "." . $today => 1
                )
            );

            $statsOptions = array(
                'upsert' => true
            );

            if($statsCollection->update($statsQuery, $statsUpdate, $statsOptions)) {
                $this->logger->log(__METHOD__, "incremented new visitors daily stats", LOG_INFO);
            }

        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while incrementing new visitors daily: " . $e->getMessage(), LOG_ERR);
        }
    }


//HIER
    public function incrementRequestsStats($userAgentId)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $now = time();
        $today = date("Y-m-d", $now);

        try {
            $statsCollection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionUserAgents());

            $statsQuery = array(
                Settings::getMongoKeyUserAgentAttrId() => $userAgentId
            );

            $statsUpdate = array(
                '$inc' => array(
                    Settings::getMongoKeyUserAgentAttrNumRequests() => 1,
                    Settings::getMongoKeyUserAgentAttrNumRequestsDaily() . "." . $today => 1
                )
            );

            if($statsCollection->update($statsQuery, $statsUpdate)) {
                $this->logger->log(__METHOD__, "incremented user agent daily request stats for " . $userAgentId, LOG_DEBUG);
                return true;
            }

        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while incrementing user agent daily request stats for " . $userAgentId . ": " . $e->getMessage(), LOG_ERR);
        }

        return false;
    }



    public function getNumRequestsDaily($userAgentId, $days)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $stats = array();

        $now = time();

        try {
            $collection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionUserAgents());

            $query = array(
                Settings::getMongoKeyUserAgentAttrId() => $userAgentId,
                Settings::getMongoKeyUserAgentAttrNumRequestsDaily() => array(
                    '$exists' => true
                )
            );

            $fields = array(
                Settings::getMongoKeyUserAgentAttrId() => false,
                Settings::getMongoKeyUserAgentAttrNumRequestsDaily() => true
            );

            $doc = $collection->findOne($query, $fields);

            for ($i = 0; $i < $days; $i++) {
                $day = date("Y-m-d", $now - (86400 * $i));
                if (isset($doc[Settings::getMongoKeyUserAgentAttrNumRequestsDaily()][$day])) {
                    $stats[$day] = $doc[Settings::getMongoKeyUserAgentAttrNumRequestsDaily()][$day];
                }
            }
        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while getting user agent daily request stats for " . $userAgentId . ": " . $e->getMessage(), LOG_ERR);
        }

        return $stats;
    }



    public function updateLastSeen($userAgentId)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        try {
            $collection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionUserAgents());

            $query = array(
                Settings::getMongoKeyUserAgentAttrId() => $userAgentId
            );

            $update = array(
                '$set' => array(
                    Settings::getMongoKeyUserAgentAttrTsLastSeen()  => new MongoTimestamp()
                ));

            $collection->update($query, $update);
        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while setting tsLastSeen on user agent " . $userAgentId . ": " . $e->getMessage(), LOG_ERR);
        }

        $this->logger->log(__METHOD__, "updated tsLastSeen for user agent " . $userAgentId, LOG_DEBUG);

        //TODO: check result
        return true;
    }



    private function isBlacklisted($userAgentId)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionUserAgentsBlacklist());

            $query = array(
                '_id' => $userAgentId
            );

            $blacklistData = $collection->findOne($query);

            if(is_array($blacklistData))
            {
                $this->logger->log(__METHOD__, "user agent is blacklisted: " . $userAgentId, LOG_INFO);
                return true;
            }
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception while searching for blacklisted user agent " . $userAgentId . ": " . $e->getMessage(), LOG_ERR);
            return true;
        }

        //not blacklisted
        return false;
    }
}
