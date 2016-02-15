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


use ThumbSniper\account\Account;
use ThumbSniper\common\Helpers;
use ThumbSniper\frontend\FrontendException;
use ThumbSniper\common\Logger;
use ThumbSniper\common\Settings;
use ThumbSniper\shared\Target;

use Exception;

use MongoDB;
use MongoCollection;
use MongoTimestamp;
use MongoCursor;


class ReferrerModel
{
    /** @var Logger */
    protected $logger;

    /** @var MongoDB */
    protected $mongoDB;

    /** @var ReferrerDeeplinkModel */
    protected $referrerDeeplinkModel;


    function __construct(MongoDB $mongoDB, Logger $logger)
    {
        $this->mongoDB = $mongoDB;
        $this->logger = $logger;

        $this->referrerDeeplinkModel = new ReferrerDeeplinkModel($this->mongoDB, $this->logger);
    }



    private static function load($data) {
        $referrer = new Referrer();

        if(!is_array($data))
        {
            return false;
        }

        $referrer->setId(isset($data[Settings::getMongoKeyReferrerAttrId()]) ? $data[Settings::getMongoKeyReferrerAttrId()] : null);
        $referrer->setUrlBase(isset($data[Settings::getMongoKeyReferrerAttrUrlBase()]) ? $data[Settings::getMongoKeyReferrerAttrUrlBase()] : null);
        $referrer->setAccountId(isset($data[Settings::getMongoKeyReferrerAttrAccountId()]) ? $data[Settings::getMongoKeyReferrerAttrAccountId()] : null);
        $referrer->setNumRequests(isset($data[Settings::getMongoKeyReferrerAttrNumRequests()]) ? $data[Settings::getMongoKeyReferrerAttrNumRequests()] : 0);

        $tsAdded = null;
        if(isset($data[Settings::getMongoKeyReferrerAttrTsAdded()]))
        {
            if($data[Settings::getMongoKeyReferrerAttrTsAdded()] instanceof MongoTimestamp) {
                /** @var MongoTimestamp $mongoTs */
                $mongoTs = $data[Settings::getMongoKeyReferrerAttrTsAdded()];
                $tsAdded = $mongoTs->sec;
            }
        }
        $referrer->setTsAdded($tsAdded);

        $tsLastSeen = null;
        if(isset($data[Settings::getMongoKeyReferrerAttrTsLastSeen()]))
        {
            if($data[Settings::getMongoKeyReferrerAttrTsLastSeen()] instanceof MongoTimestamp) {
                /** @var MongoTimestamp $mongoTs */
                $mongoTs = $data[Settings::getMongoKeyReferrerAttrTsLastSeen()];
                $tsLastSeen = $mongoTs->sec;
            }
        }
        $referrer->setTsLastSeen($tsLastSeen);

        $tsLastUpdated = null;
        if(isset($data[Settings::getMongoKeyReferrerAttrTsLastUpdated()]))
        {
            if($data[Settings::getMongoKeyReferrerAttrTsLastUpdated()] instanceof MongoTimestamp) {
                /** @var MongoTimestamp $mongoTs */
                $mongoTs = $data[Settings::getMongoKeyReferrerAttrTsLastUpdated()];
                $tsLastUpdated = $mongoTs->sec;
            }
        }
        $referrer->setTsLastUpdated($tsLastUpdated);

        $tsDomainVerified = null;
        if(isset($data[Settings::getMongoKeyReferrerAttrTsDomainVerification()]))
        {
            if($data[Settings::getMongoKeyReferrerAttrTsDomainVerification()] instanceof MongoTimestamp) {
                /** @var MongoTimestamp $mongoTs */
                $mongoTs = $data[Settings::getMongoKeyReferrerAttrTsDomainVerification()];
                $tsDomainVerified = $mongoTs->sec;
            }
        }
        $referrer->setTsDomainVerified($tsDomainVerified);

        return $referrer;
    }



    private function calculateId($baseUrl)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        return md5(Settings::getReferrerIdPrefix() . $baseUrl);
    }


    public function getById($id)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $referrer = NULL;

        try
        {
            $referrerCollection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionReferrers());

            $referrerQuery = array(
                Settings::getMongoKeyReferrerAttrId() => $id
            );

            $referrerFields = array(
                'targets' => false
            );

            $referrerData = $referrerCollection->findOne($referrerQuery, $referrerFields);

            if(is_array($referrerData)) {
                $referrer = ReferrerModel::load($referrerData);
                $referrer->setBlacklisted($this->isBlacklisted($referrer->getUrlBase()));
                $this->logger->log(__METHOD__, "found referrer " . $referrer->getId() . " in MongoDB", LOG_DEBUG);
            }
        } catch(Exception $e)
        {
            $this->logger->log(__METHOD__, "exception: " . $e->getMessage(), LOG_ERR);
            die();
        }

        if (!$referrer instanceof Referrer) {
            $this->logger->log(__METHOD__, "referrer does not exist", LOG_INFO);
            return null;
        }

        return $referrer;
    }



    public function getByUrl($url)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $urlBase = Helpers::getUrlBase($url);

        if ($urlBase == NULL) {
            //TODO: log message
            return null;
        }

        $referrerId = $this->calculateId($urlBase);
        $referrer = $this->getById($referrerId);

        if($referrer instanceof Referrer)
        {
            return $referrer;
        }

        return null;
    }



    public function getOrCreateByUrl($url, $accountId = NULL)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        //FIXME:
        $accountId = null;

        $urlBase = Helpers::getUrlBase($url);

        if ($urlBase == NULL) {
            //TODO: log message
            return null;
        }

        $referrerId = $this->calculateId($urlBase);
        $referrer = $this->getById($referrerId);

        do {
            $this->logger->log(__METHOD__, "start loop", LOG_DEBUG);

            if (!$referrer instanceof Referrer) {
                $this->logger->log(__METHOD__, "creating new referrer record (" . $url . ")", LOG_INFO);

                $now = time();

                // save new referrer to DB

                try {
                    $referrerCollection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionReferrers());

                    $referrerQuery = array(
                        Settings::getMongoKeyReferrerAttrId() => $referrerId
                    );

                    $mongoNow = new MongoTimestamp();

                    $referrerData = array(
                        Settings::getMongoKeyReferrerAttrUrlBase() => $urlBase,
                        Settings::getMongoKeyReferrerAttrTsAdded() => $mongoNow,
                        Settings::getMongoKeyReferrerAttrTsLastSeen() => $mongoNow
                    );

                    if ($accountId != NULL) {
                        $referrerData[Settings::getMongoKeyReferrerAttrAccountId()] = $accountId;
                    }

                    $referrerUpdate = array(
                        '$setOnInsert' => $referrerData
                    );

                    $referrerOptions = array(
                        'upsert' => true
                    );


                    $result = $referrerCollection->update($referrerQuery, $referrerUpdate, $referrerOptions);

                    if(is_array($result)) {
                        if($result['n'] == true) {
                            $this->logger->log(__METHOD__, "new referrer created: " . $referrerId, LOG_INFO);

                            // update statistics counter
                            $this->incrementNewReferrersDailyStats();
                        }elseif($result['updatedExisting']) {
                            $this->logger->log(__METHOD__, "updated referrer " . $referrerId . " instead of creating a new one. Works fine. :-)", LOG_INFO);
                        }
                    }

                    //$this->logger->log(__METHOD__, "referrer = " . $referrerId . " - data = " . print_r($result, true), LOG_ERR);
                } catch (Exception $e) {
                    $this->logger->log(__METHOD__, "exception while creating referrer " . $urlBase . ": " . $e->getMessage(), LOG_ERR);
                    $this->logger->log(__METHOD__, "going to die now", LOG_ERR);
                    die();
                }

                $referrer = $this->getById($referrerId);
            }
        }while(!$referrer instanceof Referrer);
        $this->logger->log(__METHOD__, "end loop", LOG_DEBUG);

		if(!Settings::isEnergySaveActive())
		{
			$currentDeeplink = $this->referrerDeeplinkModel->getOrCreateByUrl($referrerId, $url);
        	$referrer->setCurrentDeeplink($currentDeeplink);
		}

        return $referrer;
    }



    private function getVerifyDomainHttpCode($url, $key)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $verificationUrl = $url . $key . ".html";
        $ch = curl_init($verificationUrl);

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, Settings::getUserAgent());
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); //timeout in seconds
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        if(Settings::getHttpProxyUrl())
        {
            curl_setopt($ch, CURLOPT_PROXY, Settings::getHttpProxyUrl());
        }

        curl_exec($ch);

        $result = array();

        if (!curl_errno($ch)) {
            $result['httpCode'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $this->logger->log(__METHOD__, "return code for URL " . $verificationUrl . ": " . $result['httpCode'], LOG_DEBUG);
        }else{
            $result['error'] = curl_error($ch);
            $this->logger->log(__METHOD__, "curl failed while trying to access " . $verificationUrl . ": " . $result['error'], LOG_DEBUG);
        }

        curl_close($ch);

        return $result;
    }


    public function checkDomainVerificationKeyExpired(Referrer $referrer, Account $account)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        if($referrer->getTsDomainVerified() > (time() - Settings::getDomainVerificationExpire()))
        {
            $this->logger->log(__METHOD__, "domain verification for " . $referrer->getUrlBase() . " not expired.", LOG_DEBUG);
            return true;
        }else {
            $this->logger->log(__METHOD__, "domain verification for " . $referrer->getUrlBase() . " expired.", LOG_DEBUG);
        }

        //$account = $this->accountModel->getById($referrer->getAccountId());

        if(!$account instanceof Account)
        {
            $this->logger->log(__METHOD__, "invalid account - oauthId: " . $referrer->getAccountId(), LOG_ERR);
            return false;
        }

        $result = $this->getVerifyDomainHttpCode($referrer->getUrlBase(), $account->getDomainVerificationKey());
        $resetVerification = false;

        if(!array_key_exists('httpCode', $result))
        {
            $this->logger->log(__METHOD__, "domain " . $referrer->getUrlBase() . " unavailable", LOG_INFO);
            $resetVerification = true;
        }else if($result['httpCode'] != 200)
        {
            $this->logger->log(__METHOD__, "no verification key for domain " . $referrer->getUrlBase() . " found", LOG_INFO);
            $resetVerification = true;
        }

        if($resetVerification)
        {
            $this->deleteAccountIdFromReferrer($referrer->getId(), $referrer->getAccountId());
        }else
        {
            $this->updateTsDomainVerifiedForReferrer($referrer);
        }
    }


    public function addAccountIdToReferrer($url, Account $account)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $result = $this->getVerifyDomainHttpCode(Helpers::getUrlBase($url), $account->getDomainVerificationKey());
        if(!array_key_exists('httpCode', $result)) {
            if(array_key_exists('error', $result))
            {
                $this->logger->log(__METHOD__, "throwing new FrontendException: " . $result['error'], LOG_DEBUG);
                throw new FrontendException("Domain ownership could not be verified.<br/>\"" . $result['error'] . "\"");
            }else
            {
                $this->logger->log(__METHOD__, "throwing new FrontendException", LOG_DEBUG);
                throw new FrontendException("Domain ownership could not be verified.");
            }
        }else if($result['httpCode'] != 200) {
            $this->logger->log(__METHOD__, "throwing new FrontendException: HTTP status: " . $result['httpCode'], LOG_DEBUG);
            throw new FrontendException("Domain ownership could not be verified.<br/>\"HTTP status: " . $result['httpCode'] . "\"");
        }


        //FIXME: oauthId nicht mehr übergeben
        $referrer = $this->getOrCreateByUrl($url, $account->getId());

        if ($referrer instanceof Referrer) {
            if ($referrer->getAccountId() == NULL) {

                $this->logger->log(__METHOD__, "updating referrer record (" . $url . ")", LOG_INFO);

                //TODO: check if this works as expected

                try {
                    $referrerCollection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionReferrers());

                    $referrerQuery = array(
                        Settings::getMongoKeyReferrerAttrId() => $referrer->getId()
                    );

                    $referrerData = array(
                        Settings::getMongoKeyReferrerAttrAccountId() => $account->getId(),
                        Settings::getMongoKeyReferrerAttrTsDomainVerification() => new MongoTimestamp()
                    );

                    $referrerUpdate = array(
                        '$set' => $referrerData
                    );

                    $result = $referrerCollection->update($referrerQuery, $referrerUpdate);

                    if(is_array($result)) {
                        if($result['n'] == true) {
                            $this->logger->log(__METHOD__, "added account (" . $account->getId() . ") to referrer (" . $referrer->getId() . ")", LOG_ERR);
                        }elseif($result['updatedExisting']) {
                            $this->logger->log(__METHOD__, "updated referrer (" . $referrer->getId() . ") and set account " . $account->getId() . " instead of creating a new one. Works fine. :-)", LOG_ERR);
                        }
                    }

                } catch (Exception $e) {
                    $this->logger->log(__METHOD__, "exception while adding account to referrer: " . $e->getMessage(), LOG_ERR);
                    $this->logger->log(__METHOD__, "going to die now", LOG_ERR);
                    die();
                }
            }
        }else {
            $this->logger->log(__METHOD__, "invalid referrer", LOG_WARNING);
        }

        return false;
    }


    public function deleteAccountIdFromReferrer($id, $accountId)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $referrer = $this->getById($id);

        if (!$referrer) {
            return false;
        }

        try {
            $collection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionReferrers());

            $query = array(
                Settings::getMongoKeyReferrerAttrId() => $referrer->getId(),
                Settings::getMongoKeyReferrerAttrAccountId() => $accountId
            );

            $update = array(
                '$unset' => array(
                    Settings::getMongoKeyReferrerAttrAccountId() => ""
                )
            );

            if($collection->update($query, $update))
            {
                $this->logger->log(__METHOD__, "removed account " . $accountId . " from referrer " . $referrer->getId(), LOG_INFO);
            }
        }catch (Exception $e)
        {
            $this->logger->log(__METHOD__, "exception while removing account " . $accountId . " from referrer " . $referrer->getId(), LOG_INFO);
            return false;
        }

        return true;
    }


    private function updateTsDomainVerifiedForReferrer(Referrer $referrer)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        try {
            $collection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionReferrers());

            $query = array(
                Settings::getMongoKeyReferrerAttrId() => $referrer->getId()
            );

            $data = array(
                Settings::getMongoKeyReferrerAttrTsDomainVerification() => new MongoTimestamp()
            );

            $update = array(
                '$set' => $data
            );

            if($collection->update($query, $update)) {
                $this->logger->log(__METHOD__, "updated tsDomainVerification for referrer (" . $referrer->getId() . ")", LOG_ERR);
            }

        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while updating tsDomainVerification for referrer: " . $e->getMessage(), LOG_ERR);
            return false;
        }

        return true;
    }



    public function addTargetMapping(Referrer $referrer, Target $target)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        try {
            $referrerCollection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionReferrers());

            $targetData = array(
                'id' => $target->getId()
            );

            $referrerQuery = array(
                Settings::getMongoKeyReferrerAttrId() => $referrer->getId(),
                'targets.id' => array(
                    '$ne' => $target->getId()
                ));

            $referrerUpdate = array(
                '$push' => array(
                    'targets' => $targetData
                ));

            if($referrerCollection->update($referrerQuery, $referrerUpdate)) {
                $this->logger->log(__METHOD__, "added target " . $target->getId() . " to referrer " . $referrer->getId(), LOG_DEBUG);
            }

        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while adding target " . $target->getId() . " to referrer " . $referrer->getId() . ": " . $e->getMessage(), LOG_ERR);
        }

        //TODO: result auswerten
        return true;
    }


    public function removeTargetMappings(Target $target)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        try {
            $referrerCollection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionReferrers());

            $targetData = array(
                'id' => $target->getId()
            );

            $referrerQuery = array(
                'targets.id' => array(
                    '$eq' => $target->getId()
                ));

            $referrerUpdate = array(
                '$pull' => array(
                    'targets' => $targetData
                ));

            if($referrerCollection->update($referrerQuery, $referrerUpdate)) {
                $this->logger->log(__METHOD__, "removed target " . $target->getId() . " from referrers", LOG_DEBUG);
                return true;
            }

        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while removing target " . $target->getId() . " from referrers: " . $e->getMessage(), LOG_ERR);
        }

        return false;
    }


    // what referrers link to a target?
    public function getTargetReferrers($targetId, $accountId = NULL, $orderby, $orderDirection, $limit, $offset, $where)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $referrers = array();

        //TODO: $accountId, $search and order

        try {
            $collection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionReferrers());

            $query = array(
                '$query' => array(
                    'targets.id' => $targetId
                )
            );

            if ($accountId) {
                $query['$query'] = array(
                    Settings::getMongoKeyReferrerAttrAccountId() => $accountId
                );
            }

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

                $query['$query'][Settings::getMongoKeyReferrerAttrUrlBase()] = array(
                    '$regex' => $where,
                    '$options' => 'i'
                );
            }

            $fields = array(
                Settings::getMongoKeyReferrerAttrId() => true
            );

            /** @var MongoCursor $cursor */
            $cursor = $collection->find($query, $fields);
            $cursor->skip($offset);
            $cursor->limit($limit);

            foreach($cursor as $data)
            {
                //$this->logger->log(__METHOD__, "HIER: " . print_r($data, true), LOG_DEBUG);
                $referrer = $this->getById($data[Settings::getMongoKeyReferrerAttrId()]);

                if($referrer instanceof Referrer)
                {
                    $referrers[] = $referrer;
                }
            }
        }catch (Exception $e) {
            $this->logger->log(__METHOD__, "could not find referrers of target " . $targetId . ": " . $e->getMessage(), LOG_DEBUG);
        }

        return $referrers;
    }


    /**
     * @param null $targetId
     * @param null $where
     * @param null $accountId
     * @return int
     */
    public function getNumReferrers($targetId = null, $where = null, $accountId = null)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $numReferrers = 0;

        //TODO: filter by targetId

        try {
            $collection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionReferrers());

            $query = array();

            if($targetId)
            {
                $query['targets.id'] = $targetId;
            }

            if($accountId)
            {
                $query[Settings::getMongoKeyReferrerAttrAccountId()] = $accountId;
            }

            if ($where)
            {
                if($targetId || $accountId) {
                    //TODO: ist es okay, das $query Array hier so neu aufzubauen?
                    $oldQuery = $query;
                    $query = array('$and' => array());
                    $query['$and'][] = $oldQuery;
                    $query['$and'][][Settings::getMongoKeyReferrerAttrUrlBase()] = array(
                        '$regex' => $where,
                        '$options' => 'i'
                    );
                }else {
                    $query[Settings::getMongoKeyReferrerAttrUrlBase()] = array(
                        '$regex' => $where,
                        '$options' => 'i'
                    );
                }
            }

            $numReferrers = $collection->count($query);
            $this->logger->log(__METHOD__, "successfully counted referrers", LOG_DEBUG);
        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while counting referrers: " . $e->getMessage(), LOG_ERR);
        }

        return $numReferrers;
    }


    public function getNumReferrersByAccountId($accountId, $where = null)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $numReferrers = 0;

        try {
            $collection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionReferrers());

            $query = array(
                Settings::getMongoKeyReferrerAttrAccountId() => $accountId
            );

            if ($where)
            {
                //TODO: ist es okay, das $query Array hier so neu aufzubauen?
                $query['$and'] = $query;
                $query['$and'][Settings::getMongoKeyReferrerAttrUrlBase()] = array(
                    '$regex' => $where,
                    '$options' => 'i'
                );
            }

            //$this->logger->log(__METHOD__, "HIER: " . print_r($query, true), LOG_DEBUG);

            $numReferrers = $collection->count($query);
            $this->logger->log(__METHOD__, "successfully counted referrers", LOG_INFO);
        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while counting referrers: " . $e->getMessage(), LOG_ERR);
        }

        return $numReferrers;
    }


    public function getReferrers($orderby = 'tsAdded', $orderDirection = 'asc', $limit = PHP_INT_MAX, $offset = 0, $where = NULL, $accountId = null)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $referrers = array();

        try {
            $collection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionReferrers());

            $query = array(
                '$query' => array()
            );

            if ($accountId) {
                $query['$query'] = array(
                    Settings::getMongoKeyReferrerAttrAccountId() => $accountId
                );
            }

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
                    Settings::getMongoKeyReferrerAttrUrlBase() => array(
                        '$regex' => $where,
                        '$options' => 'i'
                    )
                );
            }

            $fields = array(
                Settings::getMongoKeyReferrerAttrId() => true
            );

            //$this->logger->log(__METHOD__, "HIER: " . print_r($query, true), LOG_DEBUG);

            /** @var MongoCursor $referrerCursor */
            $referrerCursor = $collection->find($query, $fields); //$query, $fields);
            $referrerCursor->skip($offset);
            $referrerCursor->limit($limit);

            foreach ($referrerCursor as $referrerDoc) {
                $r = $this->getById($referrerDoc[Settings::getMongoKeyReferrerAttrId()]);
                //$this->logger->log(__METHOD__, "HIER: " . print_r($r, true), LOG_DEBUG);

                if ($r instanceof Referrer) {
                    $referrers[] = $r;
                }
            }
        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while searching for referrers: " . $e->getMessage(), LOG_ERR);
        }

        return $referrers;
    }



    private function incrementNewReferrersDailyStats()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $now = time();
        $today = date("Y-m-d", $now);

        try {
            $statsCollection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionStatistics());

            $statsQuery = array(
                '_id' => 'referrers'
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
                $this->logger->log(__METHOD__, "incremented new referrers daily stats", LOG_INFO);
            }

        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while incrementing new referrers daily: " . $e->getMessage(), LOG_ERR);
        }
    }



    public function incrementRequestsStats($referrerId)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $now = time();
        $today = date("Y-m-d", $now);

        try {
            $statsCollection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionReferrers());

            $statsQuery = array(
                Settings::getMongoKeyReferrerAttrId() => $referrerId
            );

            $statsUpdate = array(
                '$inc' => array(
                    Settings::getMongoKeyReferrerAttrNumRequests() => 1,
                    Settings::getMongoKeyReferrerAttrNumRequestsDaily() . "." . $today => 1
                )
            );

            if($statsCollection->update($statsQuery, $statsUpdate)) {
                $this->logger->log(__METHOD__, "incremented referrer daily request stats for " . $referrerId, LOG_DEBUG);
                return true;
            }

        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while incrementing referrer daily request stats for " . $referrerId . ": " . $e->getMessage(), LOG_ERR);
        }

        return false;
    }



    public function getNumRequestsDaily($referrerId, $days)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $stats = array();

        $now = time();

        try {
            $collection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionReferrers());

            $query = array(
                Settings::getMongoKeyReferrerAttrId() => $referrerId,
                Settings::getMongoKeyReferrerAttrNumRequestsDaily() => array(
                    '$exists' => true
                )
            );

            $fields = array(
                Settings::getMongoKeyReferrerAttrId() => false,
                Settings::getMongoKeyReferrerAttrNumRequestsDaily() => true
            );

            $doc = $collection->findOne($query, $fields);

            for ($i = 0; $i < $days; $i++) {
                $day = date("Y-m-d", $now - (86400 * $i));
                if (isset($doc[Settings::getMongoKeyReferrerAttrNumRequestsDaily()][$day])) {
                    $stats[$day] = $doc[Settings::getMongoKeyReferrerAttrNumRequestsDaily()][$day];
                }
            }
        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while getting referrer daily request stats for " . $referrerId . ": " . $e->getMessage(), LOG_ERR);
        }

        return $stats;
    }



    public function updateLastSeen($referrerId)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        try {
            $referrerCollection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionReferrers());

            $referrerQuery = array(
                Settings::getMongoKeyReferrerAttrId() => $referrerId
            );

            $referrerUpdate = array(
                '$set' => array(
                    Settings::getMongoKeyReferrerAttrTsLastSeen()  => new MongoTimestamp()
                ));

            $referrerCollection->update($referrerQuery, $referrerUpdate);
        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while setting tsLastSeen on referrer " . $referrerId . ": " . $e->getMessage(), LOG_ERR);
        }

        $this->logger->log(__METHOD__, "updated tsLastSeen for referrer " . $referrerId, LOG_DEBUG);

        //TODO: check result
        return true;
    }



    public function isBlacklisted($urlBase)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $urlParts = parse_url($urlBase);
        $host = NULL;

        if (!empty($urlParts['host'])) {
            $host = strtolower($urlParts['host']);
        }

        if(!$host)
        {
            $this->logger->log(__METHOD__, "error while getting host from urlBase: " . $urlBase, LOG_ERR);
            return true;
        }

        $hostMd5 = md5($host);

        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionReferrersBlacklist());

            $query = array(
                '_id' => $hostMd5
            );

            $referrerBlacklistData = $collection->findOne($query);

            if(is_array($referrerBlacklistData))
            {
                $this->logger->log(__METHOD__, "host is blacklisted: " . $host, LOG_INFO);
                return true;
            }
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception while searching for blacklisted host " . $host . ": " . $e->getMessage(), LOG_ERR);
            return true;
        }

        //not blacklisted
        return false;
    }
}
