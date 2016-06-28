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

namespace ThumbSniper\api;

require_once('vendor/autoload.php');


use Predis\Client;
use ThumbSniper\account\Account;
use ThumbSniper\account\AccountModel;
use ThumbSniper\common\Logger;
use ThumbSniper\common\Settings;
use ThumbSniper\objective\ReferrerDeeplink;
use ThumbSniper\objective\ReferrerDeeplinkModel;
use ThumbSniper\objective\TargetModel;
use ThumbSniper\objective\ImageModel;
use ThumbSniper\objective\Referrer;
use ThumbSniper\objective\ReferrerModel;
use ThumbSniper\objective\UserAgent;
use ThumbSniper\objective\UserAgentModel;
use ThumbSniper\objective\Visitor;
use ThumbSniper\objective\VisitorModel;
use ThumbSniper\shared\Target;
use MongoDB;


class ApiStatistics
{
    /** @var MongoDB */
    protected $mongoDB;

	/** @var Client */
	private $redis;

    /** @var Logger */
    protected $logger;

    /** @var  TargetModel */
    private $targetModel;

    /** @var ImageModel */
    private $imageModel;

    /** @var AccountModel */
    private $accountModel;

    /** @var ReferrerModel */
    private $referrerModel;

    /** @var ReferrerDeeplinkModel */
    private $referrerDeeplinkModel;

    /** @var UserAgentModel */
    private $userAgentModel;

    /** @var VisitorModel */
    private $visitorModel;


    function __construct(MongoDB $mongoDB, Client $redis, Logger $logger)
    {
        $this->mongoDB = $mongoDB;
	    $this->redis = $redis;
        $this->logger = $logger;

        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $this->targetModel = new TargetModel($this->mongoDB, $this->redis, $this->logger);
        $this->imageModel = new ImageModel($this->mongoDB, $this->redis, $this->logger);
        $this->accountModel = new AccountModel($this->mongoDB, $this->logger);
        $this->referrerModel = new ReferrerModel($this->mongoDB, $this->logger);
        $this->referrerDeeplinkModel = new ReferrerDeeplinkModel($this->mongoDB, $this->logger);
        $this->userAgentModel = new UserAgentModel($this->mongoDB, $this->logger);
        $this->visitorModel = new VisitorModel($this->mongoDB, $this->logger);
    } // function



    public function incrementDeliveryDailyStats()
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
                    'delivered' . "." . $today => 1
                )
            );

            $statsOptions = array(
                'upsert' => true
            );

            if($statsCollection->update($statsQuery, $statsUpdate, $statsOptions)) {
                $this->logger->log(__METHOD__, "incremented delivered targets daily stats", LOG_DEBUG);
            }

        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception while incrementing delivered targets daily: " . $e->getMessage(), LOG_ERR);
        }
    }



    public function updateTargetRequestStats($targetId)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $success = TRUE;

        //TODO: also add numRequests daily in targetModel function
        if (!$this->targetModel->updateTargetRequestStats($targetId)) {
            $success = false;
        }

        return $success;
    }



    public function incrementImageRequestStats($imageId)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $success = TRUE;

        if (!$this->imageModel->incrementNumRequests($imageId)) {
            $success = false;
        }

        return $success;
    }


    public function incrementApiKeyRequestStats(Account $account)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        if($this->accountModel->incrementDailyRequests($account->getId())) {
            $this->logger->log(__METHOD__, "incremented daily requests for account " . $account->getId(), LOG_DEBUG);
            return true;
        }

        return false;
    }


    public function incrementReferrerRequestStats($referrer)
    {
        /** @var $referrer Referrer */

        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $referrerId = NULL;

        if ($referrer) {
            $referrerId = $referrer->getId();
        }

        if ($referrerId != NULL) {
            if($this->referrerModel->incrementRequestsStats($referrerId)) {
                $this->logger->log(__METHOD__, "incremented daily requests for referrer " . $referrerId, LOG_DEBUG);
                return true;
            }
        }

        return false;
    }


    public function incrementReferrerDeeplinkRequestStats($referrerDeeplink)
    {
        /** @var $referrerDeeplink ReferrerDeeplink */

        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $referrerDeeplinkId = NULL;

        if ($referrerDeeplink) {
            $referrerDeeplinkId = $referrerDeeplink->getId();
        }

        if ($referrerDeeplinkId != NULL) {
            if($this->referrerDeeplinkModel->incrementRequestsStats($referrerDeeplinkId)) {
                $this->logger->log(__METHOD__, "incremented daily requests for referrer deeplink " . $referrerDeeplinkId, LOG_DEBUG);
                return true;
            }
        }

        return false;
    }


    public function trackPiwik($target, $referrer, $account, $callback)
    {
        /** @var $target Target */
        /** @var $referrer Referrer */
        /** @var $account Account */

        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

		if(Settings::isEnergySaveActive())
		{
			$this->logger->log(__METHOD__, "energy saving active", LOG_DEBUG);
			return true;
		}

        if (!Settings::getPiwikTrackingEnabled()) {
			$this->logger->log(__METHOD__, "Piwik tracking disabled", LOG_DEBUG);
            return false;
        }

        \PiwikTracker::$URL = Settings::getPiwikUrl();

        $piwikTracker = new \PiwikTracker($idSite = Settings::getPiwikSiteId());

        $piwikTracker->setTokenAuth(Settings::getPiwikTokenAuth());
        $piwikTracker->disableSendImageResponse();

        //TODO: find a better way to detect a reverse-proxied IP address
        if(array_key_exists('HTTP_X_REAL_IP', $_SERVER))
        {
            $piwikTracker->setIp($_SERVER['HTTP_X_REAL_IP']);
        }elseif(array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER))
        {
            $piwikTracker->setIp($_SERVER['HTTP_X_FORWARDED_FOR']);
        }else
        {
            $piwikTracker->setIp($_SERVER['REMOTE_ADDR']);
        }

        if ($referrer) {
            /** @var ReferrerDeeplink $deeplink */
            $deeplink = $referrer->getCurrentDeeplink();

            if($deeplink instanceof ReferrerDeeplink) {
                //$piwikTracker->setUserId(substr(md5($deeplink->getUrl()), 0, 16));
                $piwikTracker->setUrlReferrer($deeplink->getUrl());
            }
        }

        $piwikTracker->setUrl($target->getUrl());

        $width = $target->getCurrentImage()->getWidth();
        $effect = $target->getCurrentImage()->getEffect();

        $piwikTracker->setCustomVariable(1, "width", $width, "page");
        $piwikTracker->setCustomVariable(2, "effect", $effect, "page");
        $piwikTracker->setCustomVariable(3, "variant", $width . " - " . $effect, "page");

        if($account)
        {
            $piwikTracker->setCustomVariable(4, "account", $account->getId(), "page");
        }else
        {
            $piwikTracker->setCustomVariable(4, "account", "anonymous", "page");
        }

        if($callback)
        {
            $piwikTracker->setCustomVariable(5, "callback", true, "page");
        }

        $oauth = NULL;

        if ($account) {
            $oauth = ', OAuthID: ' . $account->getId();
        }

        $piwikTracker->doTrackPageView(
            'width: ' . $target->getCurrentImage()->getWidth() .
            ', effect: ' . $target->getCurrentImage()->getEffect() . $oauth
        );

        //TODO: check result
        return true;
    }


    public function getDeliveryDailyStats()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionStatistics());

            $query = array(
                '_id' => 'targets',
                'delivered' => array(
                    '$exists' => true
                )
            );

            $fields = array(
                'delivered' => true
            );

            $data = $collection->findOne($query, $fields);

            if(is_array($data) && isset($data['delivered']) && is_array($data['delivered']))
            {
                return $data['delivered'];
            }
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception while getting delivered targets daily: " . $e->getMessage(), LOG_ERR);
        }
    }


    public function getTargetsUpdatedDailyStats($mode)
    {
        //TODO: limit days (also in other functions)

        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $now = time();
        $today = date("Y-m-d", $now);

        $result = null;

        try {
            $statsCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionStatistics());

            $statsQuery = array(
                '_id' => 'targets'
            );

            $statsFields = array(
                'updated_' . $mode => true
            );

            $statsData = $statsCollection->findOne($statsQuery, $statsFields);

            //$this->logger->log(__METHOD__, "STATSDATA: " . print_r($statsData, true), LOG_DEBUG);

            if(is_array($statsData) && isset($statsData['updated_' . $mode]) && is_array($statsData['updated_' . $mode]))
            {
                $result = $statsData['updated_' . $mode];
            }
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception getting updated targets daily (" . $mode . "): " . $e->getMessage(), LOG_ERR);
        }

        return !empty($result) ? $result : array($today => 0);
    }


    public function getImagesUpdatedDailyStats()
    {
        //TODO: limit days (also in other functions)

        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $now = time();
        $today = date("Y-m-d", $now);

        $result = null;

        try {
            $statsCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionStatistics());

            $statsQuery = array(
                '_id' => 'images'
            );

            $statsFields = array(
                'updated' => true
            );

            $statsData = $statsCollection->findOne($statsQuery, $statsFields);

            //$this->logger->log(__METHOD__, "STATSDATA: " . print_r($statsData, true), LOG_DEBUG);

            if(is_array($statsData) && isset($statsData['updated']) && is_array($statsData['updated']))
            {
                $result = $statsData['updated'];
            }
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception getting updated images daily: " . $e->getMessage(), LOG_ERR);
        }

        return !empty($result) ? $result : array($today => 0);
    }


    public function getTargetsFailedDailyStats($mode)
    {
        //TODO: limit days (also in other functions)

        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $now = time();
        $today = date("Y-m-d", $now);

        $result = null;

        try {
            $statsCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionStatistics());

            $statsQuery = array(
                '_id' => 'targets'
            );

            $statsFields = array(
                'failed_' . $mode => true
            );

            $statsData = $statsCollection->findOne($statsQuery, $statsFields);

            //$this->logger->log(__METHOD__, "STATSDATA: " . print_r($statsData, true), LOG_DEBUG);

            if(is_array($statsData) && isset($statsData['failed_' . $mode]) && is_array($statsData['failed_' . $mode]))
            {
                $result = $statsData['failed_' . $mode];
            }
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception getting failed targets daily (" . $mode . "): " . $e->getMessage(), LOG_ERR);
        }

        return !empty($result) ? $result : array($today => 0);
    }


    public function getTargetsForbiddenDailyStats($mode)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        //TODO: limit days (also in other functions)

        $now = time();
        $today = date("Y-m-d", $now);

        $result = null;

        try {
            $statsCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionStatistics());

            $statsQuery = array(
                '_id' => 'targets'
            );

            $statsFields = array(
                'forbidden_' . $mode => true
            );

            $statsData = $statsCollection->findOne($statsQuery, $statsFields);

            //$this->logger->log(__METHOD__, "STATSDATA: " . print_r($statsData, true), LOG_DEBUG);

            if(is_array($statsData) && isset($statsData['forbidden_' . $mode]) && is_array($statsData['forbidden_' . $mode]))
            {
                $result = $statsData['forbidden_' . $mode];
            }
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception getting forbidden targets daily (" . $mode . "): " . $e->getMessage(), LOG_ERR);
        }

        return !empty($result) ? $result : array($today => 0);
    }


    public function getImagesFailedDailyStats()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $now = time();
        $today = date("Y-m-d", $now);

        $result = null;

        try {
            $statsCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionStatistics());

            $statsQuery = array(
                '_id' => 'images'
            );

            $statsFields = array(
                'failed' => true
            );

            $statsData = $statsCollection->findOne($statsQuery, $statsFields);

            //$this->logger->log(__METHOD__, "STATSDATA: " . print_r($statsData, true), LOG_DEBUG);

            if(is_array($statsData) && isset($statsData['failed']) && is_array($statsData['failed']))
            {
                $result = $statsData['failed'];
            }
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception getting failed images daily: " . $e->getMessage(), LOG_ERR);
        }

        return !empty($result) ? $result : array($today => 0);
    }


    public function getNewTargetsDailyStats()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $now = time();
        $today = date("Y-m-d", $now);

        $result = null;

        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionStatistics());

            $query = array(
                '_id' => 'targets',
                'new' => array(
                    '$exists' => true
                )
            );

            $fields = array(
                'new' => true
            );

            $data = $collection->findOne($query, $fields);

            if(is_array($data) && isset($data['new']) && is_array($data['new']))
            {
                $result = $data['new'];
            }
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception while getting new targets daily: " . $e->getMessage(), LOG_ERR);
        }

        return !empty($result) ? $result : array($today => 0);
    }


    public function getNewReferrersDailyStats()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $now = time();
        $today = date("Y-m-d", $now);

        $result = null;

        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionStatistics());

            $query = array(
                '_id' => 'referrers',
                'new' => array(
                    '$exists' => true
                )
            );

            $fields = array(
                'new' => true
            );

            $data = $collection->findOne($query, $fields);

            if(is_array($data) && isset($data['new']) && is_array($data['new']))
            {
                $result = $data['new'];
            }
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception while getting new referrers daily: " . $e->getMessage(), LOG_ERR);
        }

        return !empty($result) ? $result : array($today => 0);
    }



    public function getDailyActiveAccounts($days)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $dailyStats = array();

        $now = time();

        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionStatistics());

            $query = array(
                '_id' => 'accounts',
                'active' => array(
                    '$exists' => true
                )
            );

            $fields = array(
                'active' => true
            );

            $doc = $collection->findOne($query, $fields);

            if(is_array($doc) && isset($doc['active']) && is_array($doc['active'])) {
                $activeData = $doc['active'];

	            $startCounting = false;

                for ($i = $days; $i >= 0; $i--) {
                    $day = date("Y-m-d", $now - (86400 * $i));
                    //$this->logger->log(__METHOD__, "check day: " . $day, LOG_DEBUG);
                    if (isset($activeData[$day])) {
                        //$this->logger->log(__METHOD__, "check day: " . $day . ": " . print_r($activeData[$day], true), LOG_DEBUG);
                        $dailyStats[$day] = count($activeData[$day]);
	                    $startCounting = true;
                    }else {
	                    if($startCounting)
	                    {
		                    $dailyStats[$day] = 0;
	                    }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception while getting daily active accounts stats : " . $e->getMessage(), LOG_ERR);
        }
//
//
//        for($i = 0; $i < $days; $i++) {
//            $interval = 86400;
//            $timestamp = time() - ($i * $interval);
//            $dateStr = date("Y", $timestamp) . date("m", $timestamp) . date("d", $timestamp);
//
//            $dailyStats[$dateStr] = count($this->redis->smembers(Settings::getRedisKeySetActiveAccountsDaily() . $dateStr));
//        }

        return $dailyStats;
    }


    public function updateReferrerLastSeenStats($referrerId)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $success = TRUE;

        if (!$this->referrerModel->updateLastSeen($referrerId)) {
            $success = FALSE;
        }

        return $success;
    }


    public function updateUserAgentLastSeenStats($userAgentId)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $success = TRUE;

        if (!$this->userAgentModel->updateLastSeen($userAgentId)) {
            $success = FALSE;
        }

        return $success;
    }


    public function updateVisitorLastSeenStats($visitorId)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $success = TRUE;

        if (!$this->visitorModel->updateLastSeen($visitorId)) {
            $success = FALSE;
        }

        return $success;
    }


    public function getMasterAgentConnectionsDailyStats($mode)
    {
        //TODO: limit days (also in other functions)

        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $now = time();
        $today = date("Y-m-d", $now);

        $result = null;

        try {
            $statsCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionStatistics());

            $statsQuery = array(
                '_id' => 'master_agents_' . $mode
            );

            $statsFields = array(
                'numRequests' => true
            );

            $statsData = $statsCollection->findOne($statsQuery, $statsFields);

            //$this->logger->log(__METHOD__, "STATSDATA: " . print_r($statsData, true), LOG_DEBUG);

            if(is_array($statsData) && isset($statsData['numRequests']) && is_array($statsData['numRequests']))
            {
                $result = $statsData['numRequests'];
            }
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception getting master agent connections (" . $mode . ") daily: " . $e->getMessage(), LOG_ERR);
        }

        return !empty($result) ? $result : array($today => 0);
    }



    public function getThumbnailAgentConnectionsDailyStats()
    {
        //TODO: limit days (also in other functions)

        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $now = time();
        $today = date("Y-m-d", $now);

        $result = null;

        try {
            $statsCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionStatistics());

            $statsQuery = array(
                '_id' => 'thumbnail_agents'
            );

            $statsFields = array(
                'numRequests' => true
            );

            $statsData = $statsCollection->findOne($statsQuery, $statsFields);

            //$this->logger->log(__METHOD__, "STATSDATA: " . print_r($statsData, true), LOG_DEBUG);

            if(is_array($statsData) && isset($statsData['numRequests']) && is_array($statsData['numRequests']))
            {
                $result = $statsData['numRequests'];
            }
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception getting thumbnail agent connections daily: " . $e->getMessage(), LOG_ERR);
        }

        return !empty($result) ? $result : array($today => 0);
    }


    public function incrementUserAgentRequestStats(UserAgent $userAgent)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

		if(Settings::isEnergySaveActive())
		{
			$this->logger->log(__METHOD__, "energy saving active", LOG_DEBUG);
			return true;
		}

        $userAgentId = NULL;

        if ($userAgent) {
            $userAgentId = $userAgent->getId();
        }

        if ($userAgentId != NULL) {
            if($this->userAgentModel->incrementRequestsStats($userAgentId)) {
                $this->logger->log(__METHOD__, "incremented daily requests for user agent " . $userAgentId, LOG_DEBUG);
                return true;
            }
        }

        return false;
    }


    public function incrementVisitorRequestStats(Visitor $visitor)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        if(Settings::isEnergySaveActive())
        {
            $this->logger->log(__METHOD__, "energy saving active", LOG_DEBUG);
            return true;
        }

        $visitorId = NULL;

        if ($visitor) {
            $visitorId = $visitor->getId();
        }

        if ($visitorId != NULL) {
            if($this->visitorModel->incrementRequestsStats($visitorId)) {
                $this->logger->log(__METHOD__, "incremented daily requests for visitor " . $visitorId, LOG_DEBUG);
                return true;
            }
        }

        return false;
    }
}
