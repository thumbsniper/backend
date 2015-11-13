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

use MongoDB;
use MongoTimestamp;
use MongoCollection;
use MongoCursor;
use Exception;


class ReferrerDeeplinkModel
{
    /** @var Logger */
    protected $logger;

    /** @var MongoDB */
    protected $mongoDB;


    function __construct(MongoDB $mongoDB, Logger $logger)
    {
        $this->mongoDB = $mongoDB;
        $this->logger = $logger;
    } // function



    private static function load($data) {
        $referrerDeeplink = new ReferrerDeeplink();

        if(!is_array($data))
        {
            return false;
        }

        $referrerDeeplink->setId(isset($data[Settings::getMongoKeyReferrerDeeplinkAttrId()]) ? $data[Settings::getMongoKeyReferrerDeeplinkAttrId()] : null);
        $referrerDeeplink->setUrl(isset($data[Settings::getMongoKeyReferrerDeeplinkAttrUrl()]) ? $data[Settings::getMongoKeyReferrerDeeplinkAttrUrl()] : null);
        $referrerDeeplink->setNumRequests(isset($data[Settings::getMongoKeyReferrerDeeplinkAttrNumRequests()]) ? $data[Settings::getMongoKeyReferrerDeeplinkAttrNumRequests()] : 0);

        $tsAdded = null;
        if(isset($data[Settings::getMongoKeyReferrerDeeplinkAttrTsAdded()]))
        {
            if($data[Settings::getMongoKeyReferrerDeeplinkAttrTsAdded()] instanceof MongoTimestamp) {
                /** @var MongoTimestamp $mongoTs */
                $mongoTs = $data[Settings::getMongoKeyReferrerDeeplinkAttrTsAdded()];
                $tsAdded = $mongoTs->sec;
            }
        }
        $referrerDeeplink->setTsAdded($tsAdded);

        $tsLastSeen = null;
        if(isset($data[Settings::getMongoKeyReferrerDeeplinkAttrTsLastSeen()]))
        {
            if($data[Settings::getMongoKeyReferrerDeeplinkAttrTsLastSeen()] instanceof MongoTimestamp) {
                /** @var MongoTimestamp $mongoTs */
                $mongoTs = $data[Settings::getMongoKeyReferrerDeeplinkAttrTsLastSeen()];
                $tsLastSeen = $mongoTs->sec;
            }
        }
        $referrerDeeplink->setTsLastSeen($tsLastSeen);

        //TODO: add referrerId to object, too?

        return $referrerDeeplink;
    }



    private function calculateId($url)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        return md5(Settings::getReferrerDeeplinkIdPrefix() . $url);
    }



    public function getById($id)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $referrerDeeplink = NULL;

        try
        {
            $referrerDeeplinkCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionReferrerDeeplinks());

            $referrerDeeplinkQuery = array(
                Settings::getMongoKeyReferrerDeeplinkAttrId() => $id
            );

            $referrerDeeplinkData = $referrerDeeplinkCollection->findOne($referrerDeeplinkQuery);

            if(is_array($referrerDeeplinkData)) {
                $referrerDeeplink = ReferrerDeeplinkModel::load($referrerDeeplinkData);
                $this->logger->log(__METHOD__, "found referrer deeplink " . $referrerDeeplink->getId() . " in MongoDB", LOG_DEBUG);
            }
        } catch(\Exception $e)
        {
            $this->logger->log(__METHOD__, "exception: " . $e->getMessage(), LOG_ERR);
            die();
        }

        if (!$referrerDeeplink instanceof ReferrerDeeplink) {
            $this->logger->log(__METHOD__, "referrer deeplink " . $id . " does not exist", LOG_DEBUG);
            return null;
        }

        return $referrerDeeplink;
    }



    public function getOrCreateByUrl($referrerId, $url)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $urlparts = parse_url($url);

        if(!is_array($urlparts))
        {
            $this->logger->log(__METHOD__, "invalid URL (invalid URL parts)", LOG_ERR);
            return false;
        }

        if(!isset($urlparts['path']) || empty($urlparts['path']))
        {
            $urlparts['path'] = "/";
        }

        $url = $urlparts['scheme'] . "://" . $urlparts['host'] . $urlparts['path'];

        if(isset($urlparts['query']) && !empty($urlparts['query']))
        {
            $url.= '?' . $urlparts['query'];
        }

        if(isset($urlparts['fragment']) && !empty($urlparts['fragment']))
        {
            $url.= '#' . $urlparts['fragment'];
        }

        $referrerDeeplinkId = $this->calculateId($url);
        $referrerDeeplink = $this->getById($referrerDeeplinkId);

        do {
            $this->logger->log(__METHOD__, "start loop", LOG_DEBUG);

            if (!$referrerDeeplink instanceof ReferrerDeeplink) {
                $this->logger->log(__METHOD__, "creating new referrer deeplink record (" . $url . ")", LOG_DEBUG);

                // save new referrer to DB

                try {
                    $referrerDeeplinkCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionReferrerDeeplinks());

                    $referrerDeeplinkQuery = array(
                        Settings::getMongoKeyReferrerAttrId() => $referrerDeeplinkId
                    );

                    $mongoNow = new MongoTimestamp();

                    $referrerDeeplinkData = array(
                        Settings::getMongoKeyReferrerDeeplinkAttrReferrerId() => $referrerId,
                        Settings::getMongoKeyReferrerDeeplinkAttrUrl() => $url,
                        Settings::getMongoKeyReferrerDeeplinkAttrTsAdded() => $mongoNow,
                        Settings::getMongoKeyReferrerDeeplinkAttrTsLastSeen() => $mongoNow,
                    );

                    $referrerDeeplinkUpdate = array(
                        '$setOnInsert' => $referrerDeeplinkData
                    );

                    $referrerDeeplinkOptions = array(
                        'upsert' => true
                    );


                    $result = $referrerDeeplinkCollection->update($referrerDeeplinkQuery, $referrerDeeplinkUpdate, $referrerDeeplinkOptions);

                    if(is_array($result)) {
                        if($result['n'] == true) {
                            $this->logger->log(__METHOD__, "new referrer deeplink created: " . $referrerId, LOG_INFO);
                        }elseif($result['updatedExisting']) {
                            $this->logger->log(__METHOD__, "updated referrer deeplink " . $referrerDeeplinkId . " instead of creating a new one. Works fine. :-)", LOG_INFO);
                        }
                    }

                } catch (\Exception $e) {
                    $this->logger->log(__METHOD__, "exception while creating referrer deeplink " . $url . ": " . $e->getMessage(), LOG_ERR);
                }

                $referrerDeeplink = $this->getById($referrerDeeplinkId);
            }
        }while(!$referrerDeeplink instanceof ReferrerDeeplink);
        $this->logger->log(__METHOD__, "end loop", LOG_DEBUG);

        return $referrerDeeplink;
    }



    public function getReferrerDeeplinks($referrerId, $orderby, $orderDirection, $limit, $offset, $where)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $deeplinks = array();

        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionReferrerDeeplinks());

            $query = array(
                '$query' => array(
                    Settings::getMongoKeyReferrerDeeplinkAttrReferrerId() => $referrerId
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
                $oldQuery = $query['$query'];
                $query['$query'] = array();
                if(!empty($oldQuery)) {
                    $query['$query']['$and'][] = $oldQuery;
                }
                $query['$query']['$and'][] = array(
                    Settings::getMongoKeyReferrerDeeplinkAttrUrl() => array(
                        '$regex' => $where,
                        '$options' => 'i'
                    )
                );
            }

            $fields = array(
                Settings::getMongoKeyReferrerDeeplinkAttrId() => true
            );

            //$this->logger->log(__METHOD__, "HIER: " . print_r($query, true), LOG_ERR);

            /** @var MongoCursor $referrerDeeplinkCursor */
            $referrerDeeplinkCursor = $collection->find($query, $fields);
            $referrerDeeplinkCursor->skip($offset);
            $referrerDeeplinkCursor->limit($limit);

            foreach ($referrerDeeplinkCursor as $referrerDeeplinkDoc) {
                $d = $this->getById($referrerDeeplinkDoc[Settings::getMongoKeyReferrerDeeplinkAttrId()]);
                //$this->logger->log(__METHOD__, "HIER: " . print_r($d, true), LOG_DEBUG);

                if ($d instanceof ReferrerDeeplink) {
                    $deeplinks[] = $d;
                }
            }

        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception while loading referrer deeplinks: " . $e->getMessage(), LOG_ERR);
        }

        //$this->logger->log(__METHOD__, "DEEPLINKS: " . print_r($deeplinks, true), LOG_DEBUG);
        return $deeplinks;
    }



    public function getNumReferrerDeeplinks($referrerId = null, $where = null)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $numReferrerDeeplinks = 0;

        try {
            $collection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionReferrerDeeplinks());

            $query = array(
            );

            if($referrerId)
            {
                $query[Settings::getMongoKeyReferrerDeeplinkAttrReferrerId()] = $referrerId;
            }

            if ($where)
            {
                if($referrerId) {
                    //TODO: ist es okay, das $query Array hier so neu aufzubauen?
                    $oldQuery = $query;
                    $query = array('$and' => array());
                    $query['$and'][] = $oldQuery;
                    $query['$and'][][Settings::getMongoKeyReferrerDeeplinkAttrUrl()] = array(
                        '$regex' => $where,
                        '$options' => 'i'
                    );
                }else {
                    $query[Settings::getMongoKeyReferrerDeeplinkAttrUrl()] = array(
                        '$regex' => $where,
                        '$options' => 'i'
                    );
                }
            }

            $numReferrerDeeplinks = $collection->count($query);
            $this->logger->log(__METHOD__, "successfully counted referrer deeplinks", LOG_DEBUG);
        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while counting referrer deeplinks: " . $e->getMessage(), LOG_ERR);
        }

        return $numReferrerDeeplinks;
    }


    //TODO: updateLastSeen() is currently unused
    public function updateLastSeen($referrerDeeplinkId)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        try {
            $collection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionReferrerDeeplinks());

            $query = array(
                Settings::getMongoKeyReferrerDeeplinkAttrId() => $referrerDeeplinkId
            );

            $update = array(
                '$set' => array(
                    Settings::getMongoKeyReferrerDeeplinkAttrTsLastSeen()  => new MongoTimestamp()
                ));

            $collection->update($query, $update);
        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while setting tsLastSeen on referrer deeplink " . $referrerDeeplinkId . ": " . $e->getMessage(), LOG_ERR);
        }

        $this->logger->log(__METHOD__, "updated tsLastSeen for referrer deeplink " . $referrerDeeplinkId, LOG_DEBUG);

        //TODO: check result
        return true;
    }


    public function incrementRequestsStats($referrerDeeplinkId)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $now = time();

        try {
            $statsCollection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionReferrerDeeplinks());

            $statsQuery = array(
                Settings::getMongoKeyReferrerDeeplinkAttrId() => $referrerDeeplinkId
            );

            $statsUpdate = array(
                '$inc' => array(
                    Settings::getMongoKeyReferrerAttrNumRequests() => 1,
                )
            );

            if($statsCollection->update($statsQuery, $statsUpdate)) {
                $this->logger->log(__METHOD__, "incremented referrer deeplink daily request stats for " . $referrerDeeplinkId, LOG_DEBUG);
                return true;
            }

        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while incrementing referrer deeplink daily request stats for " . $referrerDeeplinkId . ": " . $e->getMessage(), LOG_ERR);
        }

        return false;
    }
}
