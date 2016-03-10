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

use Predis\Client;
use ThumbSniper\common\Helpers;
use ThumbSniper\common\Logger;
use ThumbSniper\common\Settings;
use ThumbSniper\shared\Target;
use ThumbSniper\shared\Image;

use MongoDB;
use MongoTimestamp;
use MongoCursor;
use MongoCollection;
use MongoId;
use Exception;
use ErrorException;



class TargetModel
{
    /** @var MongoDB */
    protected $mongoDB;

	/** @var Client */
	private $redis;

    /** @var Logger */
    protected $logger;

    /** @var ImageModel */
	private $imageModel;

    /** @var ReferrerModel */
    private $referrerModel;



    function __construct(MongoDB $mongoDB, Client $redis, Logger $logger)
	{
        $this->mongoDB = $mongoDB;
		$this->redis = $redis;
        $this->logger = $logger;
		$this->imageModel = new ImageModel($this->mongoDB, $this->redis, $this->logger);
        $this->referrerModel = new ReferrerModel($this->mongoDB, $this->logger);

		$this->logger->log(__METHOD__, NULL, LOG_DEBUG);
	} // function



    private static function load($data) {
        $target = new Target();

        if(!is_array($data))
        {
            return false;
        }

        $target->setId(isset($data[Settings::getMongoKeyTargetAttrId()]) ? $data[Settings::getMongoKeyTargetAttrId()] : null);
        $target->setUrl(isset($data[Settings::getMongoKeyTargetAttrUrl()]) ? $data[Settings::getMongoKeyTargetAttrUrl()] : null);
		$target->setFileId(isset($data[Settings::getMongoKeyTargetAttrFileId()]) ? $data[Settings::getMongoKeyTargetAttrFileId()] : null);
        $target->setFileNameBase(isset($data[Settings::getMongoKeyTargetAttrFileNameBase()]) ? $data[Settings::getMongoKeyTargetAttrFileNameBase()] : null);
        $target->setFileNameSuffix(isset($data[Settings::getMongoKeyTargetAttrFileNameSuffix()]) ? $data[Settings::getMongoKeyTargetAttrFileNameSuffix()] : null);
        $target->setTsCheckedOut(isset($data[Settings::getMongoKeyTargetAttrTsCheckedOut()]) ? $data[Settings::getMongoKeyTargetAttrTsCheckedOut()] : null);
        $target->setCounterCheckedOut(isset($data[Settings::getMongoKeyTargetAttrCounterCheckedOut()]) ? $data[Settings::getMongoKeyTargetAttrCounterCheckedOut()] : 0);
        $target->setCounterUpdated(isset($data[Settings::getMongoKeyTargetAttrCounterUpdated()]) ? $data[Settings::getMongoKeyTargetAttrCounterUpdated()] : 0);
        $target->setCounterFailed(isset($data[Settings::getMongoKeyTargetAttrCounterFailed()]) ? $data[Settings::getMongoKeyTargetAttrCounterFailed()] : 0);
        $target->setJavaScriptEnabled(isset($data[Settings::getMongoKeyTargetAttrJavaScriptEnabled()]) ? $data[Settings::getMongoKeyTargetAttrJavaScriptEnabled()] : null);
        $target->setTsRobotsCheck(isset($data[Settings::getMongoKeyTargetAttrTsRobotsCheck()]) ? $data[Settings::getMongoKeyTargetAttrTsRobotsCheck()] : null);
        $target->setRobotsAllowed(isset($data[Settings::getMongoKeyTargetAttrRobotsAllowed()]) ? $data[Settings::getMongoKeyTargetAttrRobotsAllowed()] : null);
        $target->setSnipeDuration(isset($data[Settings::getMongoKeyTargetAttrSnipeDuration()]) ? $data[Settings::getMongoKeyTargetAttrSnipeDuration()] : null);
        $target->setWeapon(isset($data[Settings::getMongoKeyTargetAttrWeapon()]) ? $data[Settings::getMongoKeyTargetAttrWeapon()] : null);
        $target->setForcedUpdate(isset($data[Settings::getMongoKeyTargetAttrForcedUpdate()]) ? $data[Settings::getMongoKeyTargetAttrForcedUpdate()] : null);
        $target->setNumRequests(isset($data[Settings::getMongoKeyTargetAttrNumRequests()]) ? $data[Settings::getMongoKeyTargetAttrNumRequests()] : 0);
	    $target->setLastErrorMessage(isset($data[Settings::getMongoKeyTargetAttrLastErrorMessage()]) ? $data[Settings::getMongoKeyTargetAttrLastErrorMessage()] : null);
	    $target->setCensored(isset($data[Settings::getMongoKeyTargetAttrCensored()]) ? $data[Settings::getMongoKeyTargetAttrCensored()] : false);
        $target->setMimeType(isset($data[Settings::getMongoKeyTargetAttrMimeType()]) ? $data[Settings::getMongoKeyTargetAttrMimeType()] : false);

        $tsAdded = null;
        if(isset($data[Settings::getMongoKeyTargetAttrTsAdded()]))
        {
            if($data[Settings::getMongoKeyTargetAttrTsAdded()] instanceof MongoTimestamp) {
                /** @var MongoTimestamp $mongoTs */
                $mongoTs = $data[Settings::getMongoKeyTargetAttrTsAdded()];
                $tsAdded = $mongoTs->sec;
            }
        }
        $target->setTsAdded($tsAdded);

        $tsLastUpdated = null;
        if(isset($data[Settings::getMongoKeyTargetAttrTsLastUpdated()]))
        {
            if($data[Settings::getMongoKeyTargetAttrTsLastUpdated()] instanceof MongoTimestamp) {
                /** @var MongoTimestamp $mongoTs */
                $mongoTs = $data[Settings::getMongoKeyTargetAttrTsLastUpdated()];
                $tsLastUpdated = $mongoTs->sec;
            }
        }
        $target->setTsLastUpdated($tsLastUpdated);

        $tsLastRequested = null;
        if(isset($data[Settings::getMongoKeyTargetAttrTsLastRequested()]))
        {
            if($data[Settings::getMongoKeyTargetAttrTsLastRequested()] instanceof MongoTimestamp) {
                /** @var MongoTimestamp $mongoTs */
                $mongoTs = $data[Settings::getMongoKeyTargetAttrTsLastRequested()];
                $tsLastRequested = $mongoTs->sec;
            }
        }
        $target->setTsLastRequested($tsLastRequested);

        //TODO: add more ts

        return $target;
    }



	private function calculateId($url)
	{
		$this->logger->log(__METHOD__, NULL, LOG_DEBUG);

		return md5(Settings::getTargetIdPrefix() . $url);
	}


	public function getById($id)
	{
		$this->logger->log(__METHOD__, "id=" . $id, LOG_DEBUG);

        $target = null;

        try
        {
            $targetCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionTargets());
            $targetData = $targetCollection->findOne(array(Settings::getMongoKeyTargetAttrId() => $id));

            if(is_array($targetData)) {
                $target = TargetModel::load($targetData);
                //$target->setBlacklisted($this->isBlacklisted($target-
                $this->logger->log(__METHOD__, "found target " . $target->getId() . " in MongoDB", LOG_DEBUG);
            }
        } catch(Exception $e)
        {
            $this->logger->log(__METHOD__, "exception: " . $e->getMessage(), LOG_ERR);
            die();
        }

		return $target;
	}



	public function getOrCreateByUrl($url, $width, $effect)
	{
		$this->logger->log(__METHOD__, NULL, LOG_DEBUG);

		$target = NULL;

		$targetId = $this->calculateId($url);
		$target = $this->getById($targetId);

		do {
			$this->logger->log(__METHOD__, "start loop", LOG_DEBUG);

			if (!$target instanceof Target) {
				$this->logger->log(__METHOD__, "creating new target record (" . $url . ")", LOG_DEBUG);

				try {
                    $targetCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionTargets());

                    $targetQuery = array(
                        Settings::getMongoKeyTargetAttrId() => $targetId
                    );

                    $targetData = array(
                        Settings::getMongoKeyTargetAttrUrl() => $url,
                        Settings::getMongoKeyTargetAttrFileNameBase() => $targetId,
                        Settings::getMongoKeyTargetAttrFileNameSuffix() => Settings::getMasterFiletype(),
                        Settings::getMongoKeyTargetAttrTsAdded() => new MongoTimestamp(),
                        Settings::getMongoKeyTargetAttrTsLastRequested()  => new MongoTimestamp(),
                        Settings::getMongoKeyTargetAttrCounterCheckedOut() => 0,
                    );

                    $targetUpdate = array(
                        '$setOnInsert' => $targetData
                    );

                    $targetOptions = array(
                        'upsert' => true
                    );

                    $result = $targetCollection->update($targetQuery, $targetUpdate, $targetOptions);

                    if(is_array($result)) {
                        if($result['n'] == true) {
                            $this->logger->log(__METHOD__, "new target created: " . $targetId, LOG_INFO);
                        }elseif($result['updatedExisting']) {
                            $this->logger->log(__METHOD__, "updated target " . $targetId . " instead of creating a new one. Works fine. :-)", LOG_INFO);
                        }
                    }

					$this->incrementNewTargetsDailyStats();
				} catch (Exception $e) {
					$this->logger->log(__METHOD__, "exception while creating target " . $url . ": " . $e->getMessage() . "(Code: " . $e->getCode() . ")", LOG_ERR);
				}

				$target = $this->getById($targetId);
			}
		}while(!$target instanceof Target);
		$this->logger->log(__METHOD__, "end loop", LOG_DEBUG);

		$currentImage = $this->imageModel->getOrCreate($targetId, $width, $effect);

		if($currentImage instanceof Image)
		{
			$target->setCurrentImage($currentImage);
		} else
		{
			$this->logger->log(__METHOD__, "invalid image", LOG_ERR);
			return false;
		}

		return $target instanceof Target ? $target : false;
	}



	private function getTsCheckedOut($targetId)
	{
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionTargets());

        $query = array(
            Settings::getMongoKeyTargetAttrId() => $targetId,
            Settings::getMongoKeyTargetAttrTsCheckedOut() => array(
                '$exists' => true
            )
        );
        $fields = array(
            Settings::getMongoKeyTargetAttrTsCheckedOut() => true
        );

        $result = $collection->findOne($query, $fields);
        $tsCheckedOut = null;

        if($result != null)
        {
            if(isset($result[Settings::getMongoKeyTargetAttrTsCheckedOut()]))
            {
                if($result[Settings::getMongoKeyTargetAttrTsCheckedOut()] instanceof MongoTimestamp) {
                    /** @var MongoTimestamp $mongoTs */
                    $mongoTs = $result[Settings::getMongoKeyTargetAttrTsCheckedOut()];
                    $tsCheckedOut = $mongoTs->sec;
                }
            }
        }

        return $tsCheckedOut;
	}



    private function isEnqueued($targetId)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionQueueJobsMasters());

        $query = array(
            Settings::getMongoKeyTargetAttrId() => $targetId
        );
        $fields = array(
            Settings::getMongoKeyTargetAttrId() => true
        );

        $result = $collection->findOne($query, $fields);

        return $result != null;
    }



	public function getNumTargets($where = NULL)
	{
		$this->logger->log(__METHOD__, NULL, LOG_DEBUG);

		$numTargets = null;

		try {
            $targetCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionTargets());

            if($where)
            {
                $numTargets = $targetCollection->count(array(
                    Settings::getMongoKeyTargetAttrUrl() => array(
                        '$regex' => $where,
                        '$options' => 'i'
                    )));
            }else {
                $numTargets = $targetCollection->count();
            }
		} catch (Exception $e) {
			$this->logger->log(__METHOD__, "exception: " . $e->getMessage(), LOG_DEBUG);
			die();
		}

		return $numTargets;
	}



	public function getNumTargetsByAccount($accountId, $where = NULL)
	{
		$this->logger->log(__METHOD__, NULL, LOG_DEBUG);

		$numTargets = 0;

        //TODO: Suche mit $where eingrenzen

        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionReferrers());

            $matches = array(
                Settings::getMongoKeyReferrerAttrAccountId() => $accountId,
                'targets.0' => array(
                    '$exists' => true
                )
            );

            $pipeline = array(
                array(
                    '$match' => $matches
                ),
                array(
                    '$unwind' => '$targets'
                ),
                array(
                    '$group' => array(
                        '_id' => null,
                        'count' => array(
                            '$sum' => 1
                        )
                    )
                )
            );

            $resultData = $collection->aggregate($pipeline);

            if(is_array($resultData) && isset($resultData['result']) && is_array($resultData['result']) && isset($resultData['result'][0]) && is_array($resultData['result'][0])) {
                $numTargets = $resultData['result'][0]['count'];
                //$this->logger->log(__METHOD__, "HUHU: " . print_r($numTargets, true), LOG_DEBUG);
                $this->logger->log(__METHOD__, "loaded numTargets (" . $numTargets . ") for account " . $accountId . " from DB", LOG_INFO);
            }
        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "could not find targets of account " . $accountId . ": " . $e->getMessage(), LOG_DEBUG);
        }

		return $numTargets;
	}


	//TODO: Parameter-Defaults löschen?
	public function getTargets($orderby = '_id', $orderDirection = 'asc', $limit = PHP_INT_MAX, $offset = 0, $where = NULL)
	{
		$this->logger->log(__METHOD__, NULL, LOG_DEBUG);

		$targets = array();

        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionTargets());

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

            if ($where) {
                $query['$query']['$or'] = array(
                    array(
                        Settings::getMongoKeyTargetAttrId() => array(
                            '$regex' => $where,
                            '$options' => 'i'
                        )
                    ),
                    array(
                        Settings::getMongoKeyTargetAttrUrl() => array(
                            '$regex' => $where,
                            '$options' => 'i'
                        )
                    )
                );
            }

            $fields = array(
                Settings::getMongoKeyTargetAttrId() => true
            );

            //$this->logger->log(__METHOD__, "HIER: " . print_r($query, true), LOG_DEBUG);

            /** @var MongoCursor $cursor */
            $cursor = $collection->find($query, $fields);
            $cursor->skip($offset);
            $cursor->limit($limit);

            foreach ($cursor as $doc) {
                $t = $this->getById($doc[Settings::getMongoKeyTargetAttrId()]);
                //$this->logger->log(__METHOD__, "HIER: " . print_r($t, true), LOG_DEBUG);

                if ($t instanceof Target) {
                    $targets[] = $t;
                }
            }
        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while searching for targets: " . $e->getMessage(), LOG_ERR);
        }

		return $targets;
	}



	//TODO: Parameter-Defaults löschen?
	public function getTargetsByAccount($accountId, $orderby = 'id', $orderDirection = 'asc', $limit = PHP_INT_MAX, $offset = 0, $where = NULL)
	{
		$this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $targetIds = array();
		$targets = array();

        //TODO: $search and order and limit

        try {
            $collection = $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionReferrers());

            $query = array(
                Settings::getMongoKeyReferrerAttrAccountId() => $accountId,
                'targets' => array(
                    '$exists' => true
                )
            );

            $fields = array(
                'targets' => true
            );

            $cursor = $collection->find($query, $fields);

            foreach($cursor as $referrerData)
            {
                //$this->logger->log(__METHOD__, "REFERRERDATA: " . print_r($referrerData, true), LOG_DEBUG);

                if(is_array($referrerData) && isset($referrerData['targets']) && is_array($referrerData['targets']))
                {
                    foreach($referrerData['targets'] as $targetData) {
                        $targetIds[] = $targetData['id'];
                    }
                }
            }

            $targetIds = array_unique($targetIds);

            if(is_array($targetIds)) {
                //$this->logger->log(__METHOD__, "REFERRERDATA: " . print_r($referrerData, true), LOG_DEBUG);

                foreach($targetIds as $targetId)
                {
                    $target = $this->getById($targetId);

                    if($target instanceof Target)
                    {
                        $targets[] = $target;
                    }
                }
            }
        }catch (Exception $e) {
            $this->logger->log(__METHOD__, "could not find targets of account " . $accountId . ": " . $e->getMessage(), LOG_DEBUG);
        }

		return $targets;
	}



	private function isTargetCheckedOut($targetId)
	{
		$this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $tsCheckedOut = $this->getTsCheckedOut($targetId);

		if ($tsCheckedOut != NULL) {
			if (!$this->isEnqueued($targetId) && $tsCheckedOut < (time() - Settings::getCheckoutExpire())) {
				$this->logger->log(__METHOD__, "target " . $targetId . ": checkout expired (not enqueued)", LOG_INFO);
				$this->removeCheckOut($targetId);
				return false;
			} else {
				$this->logger->log(__METHOD__, "target " . $targetId . " is already checked out and enqueued", LOG_DEBUG);
				return true;
			}
		}else {
            return false;
        }
	}


    private function isBlacklistedDomain($host)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionTargetHostsBlacklist());

            $hostExploded = explode('.', $host);
            $hostExplodedReverse = array_reverse($hostExploded);

            for($i = 0; $i <= count($hostExplodedReverse)-1; $i++) {
                $hostPartToCheck = "";

                for($a = $i; $a >= 0; $a--) {
                    $hostPartToCheck = $hostPartToCheck . $hostExplodedReverse[$a];

                    if($a > 0)
                    {
                        $hostPartToCheck = $hostPartToCheck . ".";
                    }
                }

                $query = array(
                    Settings::getMongoKeyTargetHostsBlacklistAttrHost() => $hostPartToCheck,
                    Settings::getMongoKeyTargetHostsBlacklistAttrType() => 'FQDN'
                );

                $targetBlacklistData = $collection->findOne($query);

                if (is_array($targetBlacklistData)) {
                    $this->logger->log(__METHOD__, "host part is blacklisted: " . $host . " (matches " . $hostPartToCheck . ")", LOG_ERR);
                    return true;
                }
            }
        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while searching for blacklisted host " . $host . ": " . $e->getMessage(), LOG_ERR);
            return true;
        }

        return false;
    }


    //TODO: validate $type
    private function isBlacklistedIpAddress($host, $type)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        switch($type)
        {
            case "IPv4":
                $dnsType = DNS_A;
                $ipKey = "ip";
                break;

            case "IPv6":
                $dnsType = DNS_AAAA;
                $ipKey = "ipv6";
                break;

            default:
                $this->logger->log(__METHOD__, "invalid type: " . $type, LOG_ERR);
                return false;
        }

        $dnsRecords = null;

        set_error_handler(function($errno, $errstr, $errfile, $errline, array $errcontext) {
            // error was suppressed with the @-operator
            if (0 === error_reporting()) {
                return false;
            }

            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        });

        try
        {
            $dnsRecords = dns_get_record($host,  $dnsType);
        }catch (ErrorException $e)
        {
            $this->logger->log(__METHOD__, "Exception during dns_get_record for " . $host . " (" . $type . ")", LOG_ERR);
        }

        restore_error_handler();

        if(!is_array($dnsRecords))
        {
            $this->logger->log(__METHOD__, "could not resolve host to ip address: " . $host . " (" . $type . ")", LOG_ERR);
            //$this->logger->log(__METHOD__, "dns_get_record: " . $host . ' - ' . print_r(dns_get_record($host), true), LOG_ERR);
            return false;
        }elseif(!count($dnsRecords) > 0)
        {
            return false;
        }

        foreach($dnsRecords as $dnsRecord) {
            if (!array_key_exists($ipKey, $dnsRecord)) {
                continue;
            }

            try {
                $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionTargetHostsBlacklist());

                $query = array(
                    Settings::getMongoKeyTargetHostsBlacklistAttrHost() => $dnsRecord[$ipKey],
                    Settings::getMongoKeyTargetHostsBlacklistAttrType() => $type
                );

                $targetBlacklistData = $collection->findOne($query);

                if (is_array($targetBlacklistData)) {
                    $this->logger->log(__METHOD__, "IP address is blacklisted: " . $host . " (IP " . $dnsRecord[$ipKey] . ")", LOG_ERR);
                    return true;
                }else {
                    $this->logger->log(__METHOD__, "IP address is not blacklisted: " . $host . " (IP " . $dnsRecord[$ipKey] . ")", LOG_DEBUG);
                }
            } catch (Exception $e) {
                $this->logger->log(__METHOD__, "exception while searching for blacklisted IP address for " . $host . ": " . $e->getMessage(), LOG_ERR);
                return true;
            }
        }

        return false;
    }


	public function isBlacklisted($url)
	{
		$this->logger->log(__METHOD__, NULL, LOG_DEBUG);

		$urlParts = parse_url($url);
		$host = NULL;

		if (!empty($urlParts['host'])) {
			$host = strtolower($urlParts['host']);
		}

        if(!$host)
        {
            $this->logger->log(__METHOD__, "error while getting host from url: " . $url, LOG_ERR);
            return true;
        }

        if($this->isBlacklistedDomain($host))
        {
            return true;
        }

        if($this->isBlacklistedIpAddress($host, 'IPv4'))
        {
            return true;
        }

        if($this->isBlacklistedIpAddress($host, 'IPv6'))
        {
            return true;
        }

        //not blacklisted
		$this->logger->log(__METHOD__, "host is NOT blacklisted: " . $host, LOG_DEBUG);
		return false;
	}



	public function getQueueSizeJobTargetNormal()
	{
		$this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        return $this->getMasterJobQueueSize('normal');
	}


	public function getQueueSizeJobTargetLongrun()
	{
		$this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        return $this->getMasterJobQueueSize('longrun');
	}



    public function getNextMasterJob($mode)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $target = null;

		if(!Settings::isEnergySaveActive())
		{
			$this->incrementMasterAgentConnectionsDailyStats($mode);
		}

        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionQueueJobsMasters());

            $query = array(
                'mode' => $mode
            );

            $fields = array(
                '_id' => true
            );

            $options = array(
                'remove' => true,
                'new' => false,
                'sort' => array(
                    'priority' => -1,
                    'tsAdded' => 1
                )
            );

            $targetData = $collection->findAndModify($query, null, $fields, $options);
            $target = $this->getById($targetData[Settings::getMongoKeyTargetAttrId()]);

        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "could not find next target job: " . $e->getMessage(), LOG_DEBUG);
            die();
        }

        if($target instanceof Target) {
            //$this->logger->log(__METHOD__, "NEXTTARGET: " . print_r($target, true), LOG_ERR);

	        //reset lastErrorMessage
	        $target->setLastErrorMessage(null);

            return $target;
        }else {
            $this->logger->log(__METHOD__, "no next target job found", LOG_INFO);
        }
    }



    public function getMasterJobQueueSize($mode)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $imageId = null;

        try {
            $targetsCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionQueueJobsMasters());

            //FIXME: add more query options

            $query = array(
                'mode' => $mode
            );

            $queueSize = $targetsCollection->count($query);
        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "could get queue size: " . $e->getMessage(), LOG_DEBUG);
            die();
        }

        return $queueSize;
    }



	public function commitMasterImage(Target $target, $mode)
	{
		$this->logger->log(__METHOD__, NULL, LOG_DEBUG);

		//FIXME: hier muss bestimmt alles repariert werden.
		// Ziel ist es, das masterImage als base64 zu bekommen.

		$imageBase64 = $target->getMasterImage();

		if(!$imageBase64)
		{
			$this->logger->log(__METHOD__, "invalid base64 data for target " . $target->getId(), LOG_ERR);
			return false;
		}

		$this->logger->log(__METHOD__, $target->getId() . " master image size: " . strlen($imageBase64), LOG_DEBUG);

		$redisTargetMastImageKey = Settings::getRedisKeyTargetMasterImageData() . $target->getId();

		try {
			if ($this->redis->set($redisTargetMastImageKey, $imageBase64) &&
				$this->redis->expire($redisTargetMastImageKey, Settings::getRedisMasterImageExpire())
			) {
				$this->logger->log(__METHOD__, "cached masterImage file (target " . $target->getId() . ")", LOG_INFO);
			} else {
				$this->logger->log(__METHOD__, "error while caching masterImage file (target " . $target->getId() . ")", LOG_ERR);
				return false;
			}
		}catch (\Exception $e) {
			$this->logger->log(__METHOD__, "exception while caching masterImage file (target " . $target->getId() . ")", LOG_ERR);
			return false;
		}

		return $this->commit($target, $mode);
	}



	public function commitThumbnails(Target $target)
	{
		$this->logger->log(__METHOD__, NULL, LOG_DEBUG);

		foreach ($target->getImages() as $image) {
			if (!$image instanceof Image) {
				$this->logger->log(__METHOD__, "invalid image", LOG_ERR);
				continue;
			}

			$this->logger->log(__METHOD__, "processing image " . $image->getId(), LOG_DEBUG);
			$imageData_base64 = $image->getImageData();

			$imagePath = $this->imageModel->createImageFile($target, $image, $imageData_base64);

			if ($imagePath) {
				$this->imageModel->commit($image);
			} else {
				$this->logger->log(__METHOD__, "not committing image " . $image->getId(), LOG_ERR);
			}

			//FIXME: return code
		}
	}


	private function commit(Target $target, $mode)
	{
		$this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionTargets());

        $query = array(
            Settings::getMongoKeyTargetAttrId() => $target->getId()
        );

        $setData = array(
            Settings::getMongoKeyTargetAttrJavaScriptEnabled() => $target->isJavaScriptEnabled(),
            Settings::getMongoKeyTargetAttrSnipeDuration() => $target->getSnipeDuration(),
            Settings::getMongoKeyTargetAttrWeapon() => $target->getWeapon(),
            Settings::getMongoKeyTargetAttrRobotsAllowed() => $target->isRobotsAllowed(),
            Settings::getMongoKeyTargetAttrTsRobotsCheck() => $target->getTsRobotsCheck(),
            Settings::getMongoKeyTargetAttrCounterFailed() => 0,
            Settings::getMongoKeyTargetAttrTsLastUpdated() => new MongoTimestamp()
        );

        $unsetData = array(
            Settings::getMongoKeyTargetAttrTsCheckedOut() => "",
            Settings::getMongoKeyTargetAttrForcedUpdate() => "",
            Settings::getMongoKeyTargetAttrLastErrorMessage() => "",
	        // fileId löschen, da Redis das masterImage nur temporär speichert
	        Settings::getMongoKeyTargetAttrFileId() => ""
        );

        if($target->getMimeType()) {
            $setData[Settings::getMongoKeyTargetAttrMimeType()] = $target->getMimeType();
        }else {
            $unsetData[Settings::getMongoKeyTargetAttrMimeType()] = '';
        }

        $update = array(
            '$set' => $setData,
            '$unset' => $unsetData,
            '$inc' => array(
                Settings::getMongoKeyTargetAttrCounterUpdated() => 1
            )

        );

        if($collection->update($query, $update)) {
            //$this->dequeue($target->getId());
			if(!Settings::isEnergySaveActive())
			{
				$this->incrementTargetsUpdatedDailyStats($mode);
			}

            $this->logger->log(__METHOD__, "updated target (" . $target->getId() . ")", LOG_INFO);
            return true;
        }else {
            //TODO
        }
	}



	public function failedMasterImage(Target $target, $mode)
	{
		$this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionTargets());

        $query = array(
            Settings::getMongoKeyTargetAttrId() => $target->getId()
        );

        //TODO: don't remove ForcedUpdate if maxTries is not reached

        $update = array(
            '$set' => array(
                Settings::getMongoKeyTargetAttrRobotsAllowed() => $target->isRobotsAllowed(),
                Settings::getMongoKeyTargetAttrTsRobotsCheck() => $target->getTsRobotsCheck(),
	            Settings::getMongoKeyTargetAttrLastErrorMessage() => $target->getLastErrorMessage()
            ),
            '$unset' => array(
                Settings::getMongoKeyTargetAttrTsCheckedOut() => "",
                Settings::getMongoKeyTargetAttrForcedUpdate() => ""
            )
        );

        if(!$target->getTsRobotsCheck() || $target->isRobotsAllowed()) {
            $this->incrementTargetsFailedDailyStats($mode);
            $update['$inc'] = array(
                Settings::getMongoKeyTargetAttrCounterFailed() => 1
            );
        }else {
            $this->incrementTargetsForbiddenDailyStats($mode);
	        $this->deleteMasterImage($target);
	        //TODO: delete images
        }

        $result = $collection->update($query, $update);

		if(is_array($result) && $result['updatedExisting'] && $result['ok'])
		{
			$this->logger->log(__METHOD__, "updated target (" . $target->getId() . ")", LOG_INFO);
		}else
		{
			$this->logger->log(__METHOD__, "failed to update target (" . $target->getId() . ")", LOG_INFO);
		}

		return true;
	}



	// to what targets does a referrer link?
	public function getReferrerTargets($referrerId, $accountId = NULL, $orderby, $orderDirection, $limit, $offset, $where)
	{
		$this->logger->log(__METHOD__, NULL, LOG_DEBUG);

		$targets = array();

        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionTargets());

            $query = array(
                '$query' => array(
                    'referrers.id' => $referrerId
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

                $query['$query'][Settings::getMongoKeyTargetAttrUrl()] = array(
                    '$regex' => $where,
                    '$options' => 'i'
                );
            }

            $fields = array(
                Settings::getMongoKeyTargetAttrId() => true
            );

            //$this->logger->log(__METHOD__, "query: " . print_r($query, true), LOG_ERR);

            /** @var MongoCursor $cursor */
            $cursor = $collection->find($query, $fields);
            $cursor->skip($offset);
            $cursor->limit($limit);

            foreach($cursor as $data)
            {
                $target = $this->getById($data[Settings::getMongoKeyTargetAttrId()]);

                if($target instanceof Target)
                {
                    $targets[] = $target;
                }
            }
        }catch (Exception $e) {
            $this->logger->log(__METHOD__, "could not find targets of referrer " . $referrerId . ": " . $e->getMessage(), LOG_DEBUG);
        }

        //$this->logger->log(__METHOD__, "targets: " . print_r($targets, true), LOG_ERR);

		return $targets;
	}



	public function getNumReferrerTargets($referrerId, $accountId, $where)
	{
		$this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $numTargets = 0;

        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionTargets());

            $query = array(
                'referrers.id' => $referrerId
            );

            if ($accountId) {
                $query[Settings::getMongoKeyReferrerAttrAccountId()] = $accountId;
            }

            if ($where) {
                $query[Settings::getMongoKeyTargetAttrUrl()] = array(
                    '$regex' => $where,
                    '$options' => 'i'
                );
            }

            $numTargets = $collection->count($query);
//            $this->logger->log(__METHOD__, "query: " . print_r($query, true), LOG_ERR);
//            $this->logger->log(__METHOD__, "numTargets: " . print_r($numTargets, true), LOG_ERR);

            $this->logger->log(__METHOD__, "loaded numTargets (" . $numTargets . ") for referrer " . $referrerId . " from DB", LOG_INFO);

        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "could not find targets of referrer " . $referrerId . ": " . $e->getMessage(), LOG_DEBUG);
        }

		return $numTargets;
	}


	private function incrementNewTargetsDailyStats()
	{
		$this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $now = time();
        $today = date("Y-m-d", $now);

        try {
            $statsCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionStatistics());

            $statsQuery = array(
                '_id' => 'targets'
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
                $this->logger->log(__METHOD__, "incremented new targets daily stats", LOG_DEBUG);
            }

        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while incrementing new targets daily: " . $e->getMessage(), LOG_ERR);
        }
	}


	private function incrementTargetsUpdatedDailyStats($mode)
	{
		$this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $now = time();
        $today = date("Y-m-d", $now);

        try {
            $statsCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionStatistics());

            $statsQuery = array(
                '_id' => 'targets'
            );

            $statsUpdate = array(
                '$inc' => array(
                    'updated_' . $mode . "." . $today => 1
                )
            );

            $statsOptions = array(
                'upsert' => true
            );

            if($statsCollection->update($statsQuery, $statsUpdate, $statsOptions)) {
                $this->logger->log(__METHOD__, "incremented updated targets daily (" . $mode . ") stats", LOG_DEBUG);
            }

        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while incrementing updated targets daily (" . $mode . "): " . $e->getMessage(), LOG_ERR);
        }
	}


	private function incrementTargetsFailedDailyStats($mode)
	{
		$this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $now = time();
        $today = date("Y-m-d", $now);

        try {
            $statsCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionStatistics());

            $statsQuery = array(
                '_id' => 'targets'
            );

            $statsUpdate = array(
                '$inc' => array(
                    'failed_' . $mode . "." . $today => 1
                )
            );

            $statsOptions = array(
                'upsert' => true
            );

            if($statsCollection->update($statsQuery, $statsUpdate, $statsOptions)) {
                $this->logger->log(__METHOD__, "incremented failed targets daily (" . $mode . ") stats", LOG_DEBUG);
            }

        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while incrementing failed targets daily (" . $mode . "): " . $e->getMessage(), LOG_ERR);
        }
	}


	private function incrementTargetsForbiddenDailyStats($mode)
	{
		$this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $now = time();
        $today = date("Y-m-d", $now);

        try {
            $statsCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionStatistics());

            $statsQuery = array(
                '_id' => 'targets'
            );

            $statsUpdate = array(
                '$inc' => array(
                    'forbidden_' . $mode . "." . $today => 1
                )
            );

            $statsOptions = array(
                'upsert' => true
            );

            if($statsCollection->update($statsQuery, $statsUpdate, $statsOptions)) {
                $this->logger->log(__METHOD__, "incremented forbidden targets daily (" . $mode . ") stats", LOG_DEBUG);
            }

        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while incrementing forbidden targets daily (" . $mode . "): " . $e->getMessage(), LOG_ERR);
        }
	}



	public function getFailedTargets()
	{
		$this->logger->log(__METHOD__, NULL, LOG_DEBUG);

		$failedTargets = array();

        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionTargets());

            $query = array(
                Settings::getMongoKeyTargetAttrCounterFailed() => array(
                    '$gte' => Settings::getTargetMaxTries()
                )
            );

            $fields = array(
                Settings::getMongoKeyTargetAttrId() => true
            );

            //$this->logger->log(__METHOD__, "HIER: " . print_r($query, true), LOG_DEBUG);

            /** @var MongoCursor $cursor */
            $cursor = $collection->find($query, $fields);

            foreach ($cursor as $doc) {
                $t = $this->getById($doc[Settings::getMongoKeyTargetAttrId()]);
                //$this->logger->log(__METHOD__, "HIER: " . print_r($r, true), LOG_DEBUG);

                if ($t instanceof Target) {
                    $failedTargets[] = $t;
                }
            }
        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while searching for targets: " . $e->getMessage(), LOG_ERR);
        }

		return $failedTargets;
	}


	public function resetTargetFailures($targetId)
	{
		$this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionTargets());

        $query = array(
            Settings::getMongoKeyTargetAttrId() => $targetId
        );

        $update = array(
            '$set' => array(
                Settings::getMongoKeyTargetAttrCounterFailed() => 0
            ),
	        '$unset' => array(
		        Settings::getMongoKeyTargetAttrLastErrorMessage() => ''
	        )
        );

        return $collection->update($query, $update);
	}



    private function incrementMasterAgentConnectionsDailyStats($priority)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $now = time();
        $today = date("Y-m-d", $now);

        try {
            $statsCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionStatistics());

            $statsQuery = array(
                '_id' => 'master_agents_' . $priority
            );

            $statsUpdate = array(
                '$inc' => array(
                    'numRequests' . "." . $today => 1
                )
            );

            $statsOptions = array(
                'upsert' => true
            );

            if($statsCollection->update($statsQuery, $statsUpdate, $statsOptions)) {
                $this->logger->log(__METHOD__, "incremented master agent connections (" . $priority . ") daily stats", LOG_DEBUG);
            }

        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while incrementing master agent connections (" . $priority . ") daily: " . $e->getMessage(), LOG_ERR);
        }
    }



    public function checkTargetCurrentness(Target $target, $priority, $maxAge)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        //TODO: ebenfalls implementieren: $forceUpdate und erneuter RobotsCheck

        // dequeues if expired
        if(!$this->isTargetCheckedOut($target->getId()))
        {
            //$target = $this->getById($target->getId());

            if($target->isForcedUpdate() || !$target->getTsLastUpdated() ||
                $target->getTsLastUpdated() < (time() - Helpers::getVariancedValue($maxAge, Settings::getImageMaxAgeVariance()))) {

                if($target->isRobotsAllowed() || !$target->getTsRobotsCheck())
                {
                    if ($target->getCounterFailed() < Settings::getTargetMaxTries() / 2) {
                        $this->checkOut($target->getId(), 'normal', $priority);
                        // old -> enqeued
                        return false;
                    } else {
                        if ($target->getCounterFailed() < Settings::getTargetMaxTries()) {
                            $this->checkOut($target->getId(), 'longrun', $priority);
                            // old -> enqeued
                            return false;
                        }
                    }
                }elseif(!$target->isRobotsAllowed() && $target->getTsRobotsCheck() && $target->getTsRobotsCheck() < (time() - Helpers::getVariancedValue(Settings::getRobotsCheckMaxAge(), Settings::getRobotsMaxAgeVariance()))) {
                    // give robots.txt blocked URL's another chance
                    $this->checkOut($target->getId(), 'normal', $priority);
                    // old -> enqeued
                    return false;
                }
            }
        }

        // fresh or maxFailures reached or robotsForbitten -> not enqueued
        return true;
    }



    private function checkOut($targetId, $mode, $priority)
    {
        $this->logger->log(__METHOD__, '$targetId = ' . $targetId . ', $mode = ' . $mode, LOG_DEBUG);

        $target = null;

        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionTargets());

            $query = array(
                Settings::getMongoKeyTargetAttrId() => $targetId
            );

            $update = array(
                '$set' => array(
                    Settings::getMongoKeyTargetAttrTsCheckedOut() => new MongoTimestamp()
                ),
                '$inc' => array(
                    Settings::getMongoKeyTargetAttrCounterCheckedOut() => 1
                )
            );

            $options = array(
                'new' => true
            );

            $targetData = $collection->findAndModify($query, $update, null, $options);
            $target = TargetModel::load($targetData);

        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "could not find next target job: " . $e->getMessage(), LOG_DEBUG);
            die();
        }

        if($target instanceof Target) {
            $this->enqueue($target->getId(), $mode, $priority);
            //$this->logger->log(__METHOD__, "NEXTTARGET: " . print_r($target, true), LOG_ERR);
            return $target;
        }else {
            $this->logger->log(__METHOD__, "no next target job found", LOG_INFO);
        }
    }



    private function removeCheckOut($targetId)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $target = null;

        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionTargets());

            $query = array(
                Settings::getMongoKeyTargetAttrId() => $targetId
            );

            $update = array(
                '$unset' => array(
                    Settings::getMongoKeyTargetAttrTsCheckedOut() => ''
                )
            );

            $targetData = $collection->findAndModify($query, $update, null, null);
            $target = TargetModel::load($targetData);

        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "could not find queued item: " . $e->getMessage(), LOG_DEBUG);
            return false;
        }

        if($target instanceof Target) {
            return true;
        }else {
            return false;
        }
    }



    private function enqueue($targetId, $mode, $priority)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionQueueJobsMasters());

            $query = array(
                Settings::getMongoKeyTargetAttrId() => $targetId
            );

            $data = array(
                'tsAdded' => new MongoTimestamp(),
                'mode' => $mode,
                'priority' => $priority
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
                    $this->logger->log(__METHOD__, "new queue item created: " . $targetId, LOG_INFO);
                } elseif ($result['updatedExisting']) {
                    $this->logger->log(__METHOD__, "updated queue item " . $targetId . " instead of creating a new one. Works fine. :-)", LOG_ERR);
                }
            }
        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while creating queue item: " . $targetId . ": " . $e->getMessage() . "(Code: " . $e->getCode() . ")", LOG_ERR);
        }
    }



    public function updateTargetRequestStats($targetId)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionTargets());

            $query = array(
                Settings::getMongoKeyTargetAttrId() => $targetId
            );

            $update = array(
                '$set' => array(
                    Settings::getMongoKeyTargetAttrTsLastRequested()  => new MongoTimestamp()
                ),
                '$inc' => array(
                    Settings::getMongoKeyTargetAttrNumRequests() => 1
                )
            );

            $collection->update($query, $update);
        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while updating tsLastRequested stats for " . $targetId . ": " . $e->getMessage(), LOG_ERR);
        }

        $this->logger->log(__METHOD__, "updated tsLastRequested stats for " . $targetId, LOG_DEBUG);

        //TODO: check result
        return true;
    }



    public function addReferrerMapping(Target $target, Referrer $referrer)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        try {
            $collection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionTargets());

            $referrerData = array(
                'id' => $referrer->getId()
            );

            $query = array(
                Settings::getMongoKeyTargetAttrId() => $target->getId(),
                'referrers.id' => array(
                    '$ne' => $referrer->getId()
                ));

            $update = array(
                '$push' => array(
                    'referrers' => $referrerData
                ));

            if($collection->update($query, $update)) {
                $this->logger->log(__METHOD__, "added referrer " . $referrer->getId() . " to target " . $target->getId(), LOG_DEBUG);
            }

        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while adding referrer " . $referrer->getId() . " to target " . $target->getId() . ": " . $e->getMessage(), LOG_ERR);
        }

        //TODO: result auswerten
        return true;
    }



    public function addUserAgentMapping(Target $target, UserAgent $userAgent)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        try {
            $collection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionTargets());

            $data = array(
                'id' => $userAgent->getId()
            );

            $query = array(
                Settings::getMongoKeyTargetAttrId() => $target->getId(),
                'useragents.id' => array(
                    '$ne' => $userAgent->getId()
                ));

            $update = array(
                '$push' => array(
                    'useragents' => $data
                ));

            if($collection->update($query, $update)) {
                $this->logger->log(__METHOD__, "added user agent " . $userAgent->getId() . " to target " . $target->getId(), LOG_DEBUG);
            }

        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while adding user agent " . $userAgent->getId() . " to target " . $target->getId() . ": " . $e->getMessage(), LOG_ERR);
        }

        //TODO: result auswerten
        return true;
    }


    public function forceUpdate($targetId)
    {
        $this->logger->log(__METHOD__, null, LOG_DEBUG);

        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionTargets());

            $query = array(
                Settings::getMongoKeyTargetAttrId() => $targetId
            );

            $update = array(
                '$set' => array(
                    Settings::getMongoKeyTargetAttrForcedUpdate() => true
                ),
                '$unset' => array(
                    Settings::getMongoKeyTargetAttrCounterFailed() => ''
                )
            );

            if($collection->update($query, $update)) {
                $this->logger->log(__METHOD__, "forcing update of target " . $targetId, LOG_INFO);
                return true;
            }
        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "could not force update of target " . $targetId . ": " . $e->getMessage(), LOG_DEBUG);
        }

        return false;
    }


	//TODO: delete from Redis
	public function deleteMasterImage(Target $target)
	{
		$this->logger->log(__METHOD__, NULL, LOG_DEBUG);

		//TODO: löschen aus Redis ohne FileId: umbauen

		if($target->getFileId())
		{
			if(strpos($target->getFileId(), Settings::getRedisKeyTargetMasterImageData()) !== false)
			{
				//use Redis

				if(!$this->redis->del($target->getFileId()))
				{
					$this->logger->log(__METHOD__, "error while deleting fileId: " . $target->getFileId() . " from Redis (not updated)", LOG_ERR);
					return false;
				}
			}else
			{
				//use MongoDB

				$gridfs = $this->mongoDB->getGridFS('masters');

				$deleteResult = $gridfs->delete(new MongoId($target->getFileId()));

				if(!isset($deleteResult['ok']) || !$deleteResult['ok'] || !isset($deleteResult['n']) || !$deleteResult['n'] > 0)
				{
					$this->logger->log(__METHOD__, "error while deleting fileId: " . $target->getFileId() . " (not updated)", LOG_ERR);
					return false;
				}
			}

			try
			{
				$collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionTargets());

				$query = array(
					Settings::getMongoKeyTargetAttrId() => $target->getId()
				);

				$update = array(
					'$unset' => array(
						Settings::getMongoKeyTargetAttrFileId() => ''
					)
				);

				if($collection->update($query, $update))
				{
					$this->logger->log(__METHOD__, "deleted master image of target " . $target->getId(), LOG_INFO);

					return true;
				}
			}catch(Exception $e)
			{
				$this->logger->log(__METHOD__, "could not delete master image of target " . $target->getId() . ": " . $e->getMessage(), LOG_DEBUG);

				return false;
			}
		}else {
			$this->logger->log(__METHOD__, "no master image of target " . $target->getId() . " exists", LOG_INFO);
			return true;
		}

		return false;
	}



    // what targets did a user agent request?
    public function getUserAgentTargets($userAgentId, $accountId = NULL, $orderby, $orderDirection, $limit, $offset, $where)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $targets = array();

        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionTargets());

            $query = array(
                '$query' => array(
                    'useragents.id' => $userAgentId
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

                $query['$query'][Settings::getMongoKeyTargetAttrUrl()] = array(
                    '$regex' => $where,
                    '$options' => 'i'
                );
            }

            $fields = array(
                Settings::getMongoKeyTargetAttrId() => true
            );

            //$this->logger->log(__METHOD__, "query: " . print_r($query, true), LOG_ERR);

            /** @var MongoCursor $cursor */
            $cursor = $collection->find($query, $fields);
            $cursor->skip($offset);
            $cursor->limit($limit);

            foreach($cursor as $data)
            {
                $target = $this->getById($data[Settings::getMongoKeyTargetAttrId()]);

                if($target instanceof Target)
                {
                    $targets[] = $target;
                }
            }
        }catch (Exception $e) {
            $this->logger->log(__METHOD__, "could not find targets of user agent " . $userAgentId . ": " . $e->getMessage(), LOG_DEBUG);
        }

        //$this->logger->log(__METHOD__, "targets: " . print_r($targets, true), LOG_ERR);

        return $targets;
    }



    public function getNumUserAgentTargets($userAgentId, $accountId, $where)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $numTargets = 0;

        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionTargets());

            $query = array(
                'useragents.id' => $userAgentId
            );

            if ($where) {
                $query[Settings::getMongoKeyTargetAttrUrl()] = array(
                    '$regex' => $where,
                    '$options' => 'i'
                );
            }

            $numTargets = $collection->count($query);
//            $this->logger->log(__METHOD__, "query: " . print_r($query, true), LOG_ERR);
//            $this->logger->log(__METHOD__, "numTargets: " . print_r($numTargets, true), LOG_ERR);

            $this->logger->log(__METHOD__, "loaded numTargets (" . $numTargets . ") for user agent " . $userAgentId . " from DB", LOG_INFO);

        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "could not find targets of user agent " . $userAgentId . ": " . $e->getMessage(), LOG_DEBUG);
        }

        return $numTargets;
    }


    public function getNumTargetHostsBlacklisted($where = NULL)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $numTargetsBlacklisted = null;

        try {
            $targetsBlacklistCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionTargetHostsBlacklist());

            if ($where) {
                $query = array(

                );

                $query['$or'] = array(
                    array(
                        Settings::getMongoKeyTargetHostsBlacklistAttrHost() => array(
                            '$regex' => $where,
                            '$options' => 'i'
                        )
                    ),
                    array(
                        Settings::getMongoKeyTargetHostsBlacklistAttrType() => array(
                            '$regex' => $where,
                            '$options' => 'i'
                        )
                    ),
                    array(
                        Settings::getMongoKeyTargetHostsBlacklistAttrComment() => array(
                            '$regex' => $where,
                            '$options' => 'i'
                        )
                    )
                );

                $numTargetsBlacklisted = $targetsBlacklistCollection->count($query);
            }else {
                $numTargetsBlacklisted = $targetsBlacklistCollection->count();
            }
        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception: " . $e->getMessage(), LOG_ERR);
            die();
        }

        return $numTargetsBlacklisted;
    }


    //TODO: Parameter-Defaults löschen?
    public function getTargetHostsBlacklisted($orderby = '_id', $orderDirection = 'asc', $limit = PHP_INT_MAX, $offset = 0, $where = NULL)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $targetHosts = array();

        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionTargetHostsBlacklist());

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

            if ($where) {
                $query['$query']['$or'] = array(
                    array(
                        Settings::getMongoKeyTargetHostsBlacklistAttrHost() => array(
                            '$regex' => $where,
                            '$options' => 'i'
                        )
                    ),
                    array(
                        Settings::getMongoKeyTargetHostsBlacklistAttrType() => array(
                            '$regex' => $where,
                            '$options' => 'i'
                        )
                    ),
                    array(
                        Settings::getMongoKeyTargetHostsBlacklistAttrComment() => array(
                            '$regex' => $where,
                            '$options' => 'i'
                        )
                    )
                );
            }

            /** @var MongoCursor $cursor */
            $cursor = $collection->find($query);
            $cursor->skip($offset);
            $cursor->limit($limit);

            foreach ($cursor as $doc) {
                $t = new TargetHostBlacklisted();
                $t->setId($doc[Settings::getMongoKeyTargetHostsBlacklistAttrId()]);
                $t->setHost($doc[Settings::getMongoKeyTargetHostsBlacklistAttrHost()]);
                $t->setType($doc[Settings::getMongoKeyTargetHostsBlacklistAttrType()]);
                $t->setComment($doc[Settings::getMongoKeyTargetHostsBlacklistAttrComment()]);

                if ($t instanceof TargetHostBlacklisted) {
                    $targetHosts[] = $t;
                }
            }
        } catch (Exception $e) {
            $this->logger->log(__METHOD__, "exception while searching for blacklisted target hosts: " . $e->getMessage(), LOG_ERR);
        }

        return $targetHosts;
    }


    public function delete($targetId)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $target = $this->getById($targetId);

        // delete target's images
        $images = $this->imageModel->getImages($target->getId());
        if(!empty($images)) {
            /** @var Image $image */
            foreach($images as $image)
            {
                $this->imageModel->delete($target, $image);
            }
        }

        //double-check that no images are left
        $images = $this->imageModel->getImages($target->getId());
        if(!empty($images)) {
            $this->logger->log(__METHOD__, "could not remove all associated images for target: " . $target->getId(), LOG_ERR);
            return false;
        }

        // remove referrer mappings
        $this->referrerModel->removeTargetMappings($target);

        //TODO: remove target from other collections
        //TODO: dequeue master image

        // remove target
        try {
            $targetCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionTargets());

            $query = array(
                Settings::getMongoKeyTargetAttrId() => $target->getId()
            );

            $options = array(
                'justOne' => true
            );

            $result = $targetCollection->remove($query, $options);

            if(is_array($result)) {
                if($result['ok'] == true) {
                    if($result['n'] > 0) {
                        $this->logger->log(__METHOD__, "target removed: " . $target->getId(), LOG_INFO);
                    }else {
                        $this->logger->log(__METHOD__, "no target was removed (ok): " . $target->getId(), LOG_INFO);
                    }
                    return true;
                }else {
                    $this->logger->log(__METHOD__, "could not remove target " . $target->getId() . ": " . $result['err'] . " - " . $result['errmsg'], LOG_ERR);
                    return false;
                }
            }
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception while removing target " . $target->getId() . ": " . $e->getMessage(), LOG_ERR);
            return false;
        }

        return false;
    }
}
