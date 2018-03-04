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


use DateTime;
use DateTimeZone;
use MongoDate;
use ThumbSniper\common\Logger;
use ThumbSniper\common\Settings;
use ThumbSniper\shared\Target;

use Exception;

use MongoDB;
use MongoCollection;
use MongoTimestamp;
use MongoCursor;


class UserAgentModel
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
        $userAgent = new UserAgent();

        if(!is_array($data))
        {
            return false;
        }

        $userAgent->setId(isset($data[Settings::getMongoKeyUserAgentAttrId()]) ? $data[Settings::getMongoKeyUserAgentAttrId()] : null);
        $userAgent->setDescription(isset($data[Settings::getMongoKeyUserAgentAttrDescription()]) ? $data[Settings::getMongoKeyUserAgentAttrDescription()] : null);
        $userAgent->setNumRequests(isset($data[Settings::getMongoKeyUserAgentAttrNumRequests()]) ? $data[Settings::getMongoKeyUserAgentAttrNumRequests()] : 0);

        $tsAdded = null;
        if(isset($data[Settings::getMongoKeyUserAgentAttrTsAdded()]))
        {
            if($data[Settings::getMongoKeyUserAgentAttrTsAdded()] instanceof MongoTimestamp) {
                /** @var MongoTimestamp $mongoTs */
                $mongoTs = $data[Settings::getMongoKeyUserAgentAttrTsAdded()];
                $tsAdded = $mongoTs->sec;
            }
        }
        $userAgent->setTsAdded($tsAdded);

        $tsLastSeen = null;
        if(isset($data[Settings::getMongoKeyUserAgentAttrTsLastSeen()]))
        {
            if($data[Settings::getMongoKeyUserAgentAttrTsLastSeen()] instanceof MongoTimestamp) {
                /** @var MongoTimestamp $mongoTs */
                $mongoTs = $data[Settings::getMongoKeyUserAgentAttrTsLastSeen()];
                $tsLastSeen = $mongoTs->sec;
            }
        }
        $userAgent->setTsLastSeen($tsLastSeen);

        $tsLastUpdated = null;
        if(isset($data[Settings::getMongoKeyUserAgentAttrTsLastUpdated()]))
        {
            if($data[Settings::getMongoKeyUserAgentAttrTsLastUpdated()] instanceof MongoTimestamp) {
                /** @var MongoTimestamp $mongoTs */
                $mongoTs = $data[Settings::getMongoKeyUserAgentAttrTsLastUpdated()];
                $tsLastUpdated = $mongoTs->sec;
            }
        }
        $userAgent->setTsLastUpdated($tsLastUpdated);

        return $userAgent;
    }



    private function calculateId($description)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        return md5($description);
    }


    public function getById($id)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $userAgent = NULL;

        try
        {
            $collection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionUserAgents());

            $query = array(
                Settings::getMongoKeyUserAgentAttrId() => $id
            );

            $fields = array(
                'targets' => false
            );

            $data = $collection->findOne($query, $fields);

            if(is_array($data)) {
                $userAgent = UserAgentModel::load($data);
                $userAgent->setBlacklisted($this->isBlacklisted($userAgent->getId()));
                $this->logger->log(__METHOD__, "found user agent " . $userAgent->getId() . " in MongoDB", LOG_DEBUG);
            }
        } catch(Exception $e)
        {
            $this->logger->log(__METHOD__, "exception: " . $e->getMessage(), LOG_ERR);
            die();
        }

        if (!$userAgent instanceof UserAgent) {
            $this->logger->log(__METHOD__, "user agent does not exist", LOG_INFO);
            return null;
        }

        return $userAgent;
    }



    public function getOrCreateByDescription($description)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        if ($description == NULL || empty($description)) {
            //TODO: log message
            return null;
        }

        $userAgentId = $this->calculateId($description);
        $userAgent = $this->getById($userAgentId);

        try {
            do {
                $this->logger->log(__METHOD__, "start loop", LOG_DEBUG);

                if (!$userAgent instanceof UserAgent) {
                    $this->logger->log(__METHOD__, "creating new user agent record (" . $description . ")", LOG_INFO);

                    // save new referrer to DB

                    $collection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionUserAgents());

                    $query = array(
                        Settings::getMongoKeyUserAgentAttrId() => $userAgentId
                    );

                    $mongoNow = new MongoTimestamp();

                    $data = array(
                        Settings::getMongoKeyUserAgentAttrDescription() => $description,
                        Settings::getMongoKeyUserAgentAttrTsAdded() => $mongoNow,
                        Settings::getMongoKeyUserAgentAttrTsLastSeen() => $mongoNow
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

                    if (is_array($result)) {
                        if ($result['n'] == true) {
                            $this->logger->log(__METHOD__, "new user agent created: " . $userAgentId, LOG_INFO);

                            // update statistics counter
                            $this->incrementNewUserAgentsDailyStats();
                        } elseif ($result['updatedExisting']) {
                            $this->logger->log(__METHOD__, "updated user agent " . $userAgentId . " instead of creating a new one. Works fine. :-)", LOG_INFO);
                        }
                    }

                    $userAgent = $this->getById($userAgentId);
                }
            } while (!$userAgent instanceof UserAgent);
        }catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while creating user agent " . $description . ": " . $e->getMessage(), LOG_ERR);
            $this->logger->log(__METHOD__, "going to die now", LOG_ERR);
            die();
        }

        $this->logger->log(__METHOD__, "end loop", LOG_DEBUG);

        return $userAgent instanceof UserAgent ? $userAgent : false;
    }



    public function addTargetMapping(UserAgent $userAgent, Target $target)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        try {
            $collection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionUserAgentTargets());

            $data = array(
                Settings::getMongoKeyUserAgentTargetsAttrUserAgentId() => $userAgent->getId(),
                Settings::getMongoKeyUserAgentTargetsAttrTargetId() => $target->getId(),
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
            $cursor = $collection->find($query, $fields);
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
    }


    /**
     * @param null $targetId
     * @param null $where
     * @param null $accountId
     * @return int
     */
    public function getNumUserAgents($targetId = null, $where = null, $accountId = null)
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
    }



    public function getUserAgents($orderby = 'tsAdded', $orderDirection = 'asc', $limit = PHP_INT_MAX, $offset = 0, $where = NULL, $accountId = null)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $userAgents = array();

        try {
            $collection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionUserAgents());

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
                    Settings::getMongoKeyUserAgentAttrDescription() => array(
                        '$regex' => $where,
                        '$options' => 'i'
                    )
                );
            }

            $fields = array(
                Settings::getMongoKeyUserAgentAttrId() => true
            );

            //$this->logger->log(__METHOD__, "HIER: " . print_r($query, true), LOG_DEBUG);

            /** @var MongoCursor $referrerCursor */
            $cursor = $collection->find($query, $fields); //$query, $fields);
            $cursor->skip($offset);
            $cursor->limit($limit);

            foreach ($cursor as $doc) {
                $u = $this->getById($doc[Settings::getMongoKeyUserAgentAttrId()]);
                //$this->logger->log(__METHOD__, "HIER: " . print_r($r, true), LOG_DEBUG);

                if ($u instanceof UserAgent) {
                    $userAgents[] = $u;
                }
            }
        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while searching for user agents: " . $e->getMessage(), LOG_ERR);
        }

        return $userAgents;
    }



    private function incrementNewUserAgentsDailyStats()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $now = time();
        $today = date("Y-m-d", $now);

        try {
            $statsCollection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionStatistics());

            $statsQuery = array(
                '_id' => 'useragents'
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
                $this->logger->log(__METHOD__, "incremented new user agents daily stats", LOG_INFO);
            }

        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while incrementing new user agents daily: " . $e->getMessage(), LOG_ERR);
        }
    }



    public function incrementRequestsStats($userAgentId)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $dtNow = new DateTime();
        $dtNow->setTimezone(new DateTimeZone('GMT'));
        $beginOfDay = clone $dtNow;
        $beginOfDay->modify('today');
        $mongoToday = new MongoDate($beginOfDay->getTimestamp());

        try {
            $statsCollection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionUserAgentStatistics());

            $statsQuery = array(
                Settings::getMongoKeyUserAgentStatisticsAttrUserAgentId() => $userAgentId,
                Settings::getMongoKeyUserAgentStatisticsAttrTs() => $mongoToday
            );

            $statsData = array(
                Settings::getMongoKeyUserAgentStatisticsAttrUserAgentId() => $userAgentId,
                Settings::getMongoKeyUserAgentStatisticsAttrTs() => $mongoToday
            );

            $statsUpdate = array(
                '$setOnInsert' => $statsData,
                '$inc' => array(
                    Settings::getMongoKeyUserAgentStatisticsAttrNumRequests() => 1,
                )
            );

            $statsOptions = array(
                'upsert' => true
            );

            if($statsCollection->update($statsQuery, $statsUpdate, $statsOptions)) {
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
