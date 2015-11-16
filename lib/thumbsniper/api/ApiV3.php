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
use ThumbSniper\account\OauthModel;
use ThumbSniper\common\Helpers;
use ThumbSniper\common\Logger;
use ThumbSniper\common\Settings;
use ThumbSniper\db\Mongo;
use ThumbSniper\db\Redis;
use ThumbSniper\objective\ReferrerDeeplinkModel;
use ThumbSniper\objective\UserAgent;
use ThumbSniper\objective\UserAgentModel;
use ThumbSniper\shared\Image;
use ThumbSniper\objective\ImageModel;
use ThumbSniper\objective\Referrer;
use ThumbSniper\objective\ReferrerModel;
use ThumbSniper\shared\Target;
use ThumbSniper\objective\TargetModel;
use MongoDB;


class ApiV3
{
    /** @var MongoDB */
    private $mongodb;

	/** @var Client */
	private $redis;

    /** @var Logger */
    private $logger;

    /** @var AccountModel */
    private $accountModel;

    /** @var ReferrerModel */
    private $referrerModel;

    /** @var ReferrerDeeplinkModel */
    private $referrerDeeplinkModel;

    /** @var TargetModel */
    private $targetModel;

    /** @var ImageModel */
    private $imageModel;

    /** @var  OauthModel */
    private $oauthModel;

    /** @var ApiStatistics */
    private $apiStatistics;

    /** @var UserAgentModel */
    private $userAgentModel;

    /** @var Account */
    protected $account;

    /** @var Target */
    protected $target;

    /** @var Referrer */
    protected $referrer;

    /** @var UserAgent */
    protected $userAgent;

    protected $httpProtocol;
    protected $httpRequestMethod;

    // sent with the API request
    protected $apiKey;

    protected $thumbnailWidth;
    protected $thumbnailEffect;
    protected $action;
    protected $forceDebug;
    protected $waitimg;
    protected $forceUpdate;
    protected $callback;
    protected $cacheId;

    protected $frontendImageUrls;



    function __construct($forceDebug)
    {
        $this->forceDebug = $forceDebug;

        $this->frontendImageUrls = array(
            Settings::getFrontendImagesUrl() . Settings::getFrontendImagesPathDummy(),
            Settings::getFrontendImagesUrl() . Settings::getFrontendImagesPathBroken(),
            Settings::getFrontendImagesUrl() . Settings::getFrontendImagesPathRobots(),
            Settings::getFrontendImagesUrl() . Settings::getFrontendImagesPathViolation()
        );
    }


    function __destruct()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);
    }


    // common

    public function log($source, $msg, $severity)
    {
        return $this->getLogger()->log($source, $msg, $severity);
    }


    protected function isSSL()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        if(isset($_SERVER['HTTPS']))
        {
            if(strtolower($_SERVER['HTTPS']) == 'on' || $_SERVER['HTTPS'] == '1')
            {
                return true;
            }
        }elseif(isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443')
        {
            return true;
        }

        return false;
    }


    public function publishLogsAsHeaders()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $this->getLogger()->publishMessagesAsHeaders();
    }


    // validation

    public function loadAndValidateCommonParameters($otnAction)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $result = true;

        // otnAction

        if (!$otnAction || !is_string($otnAction) || !in_array($otnAction, Settings::getApiValidActions())) {
            $this->getLogger()->log(__METHOD__, "invalid otnAction", LOG_ERR);
            $result = false;
        } else {
            $this->action = $otnAction;
        }

        if($this->isSSL())
        {
            $this->httpProtocol = "https";
        }else
        {
            $this->httpProtocol = "http";
        }

        $this->httpRequestMethod = $_SERVER['REQUEST_METHOD'];

        return $result;
    }



    private function getValidatedUrl($url, $addQuery = false, $addFragment = false)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        if (!$url || !is_string($url))
        {
            $this->getLogger()->log(__METHOD__, "invalid URL (not set or not string)", LOG_ERR);
            return false;
        }

        //disable Retina images
        $url = str_replace('@2x', '', $url);

        $urlparts = parse_url($url);

        if(!is_array($urlparts))
        {
            $this->getLogger()->log(__METHOD__, "invalid URL (invalid URL parts)", LOG_ERR);
            return false;
        }

        if(isset($urlparts['host']) && Helpers::isPrivateIpAddress($urlparts['host']))
        {
            $this->getLogger()->log(__METHOD__, "invalid URL (host " . $urlparts['host'] . " is a private IP address)", LOG_ERR);
            return false;
        }

        //TODO: enable this funktion to check for unresolvable domains
//        if(isset($urlparts['host']) && (!Helpers::isIpAddress($urlparts['host']) && !Helpers::isDomainExists($urlparts['host'])))
//        {
//            $this->getLogger()->log(__METHOD__, "Host " . $urlparts['host'] . " has no DNS RR", LOG_ERR);
//            return false;
//        }

        if(!isset($urlparts['path']) || empty($urlparts['path']))
        {
            $urlparts['path'] = "/";
        }

        $encodedPath = $urlparts['path'];

	    // double-slash-eliminator
	    if(strlen($encodedPath) >= 2 && substr($encodedPath, 0, 2) == '//')
	    {
		    $encodedPath = substr($encodedPath, 1);
	    }

        $stringsToEncode = array('ü', 'ä', 'ö', 'ß');

        foreach($stringsToEncode as $strToEncode)
        {
            $encodedPath = str_replace(strtolower($strToEncode), urlencode(strtolower($strToEncode)), $encodedPath);
            $encodedPath = str_replace(strtoupper($strToEncode), urlencode(strtoupper($strToEncode)), $encodedPath);
        }

        $urlBase = strtolower($urlparts['scheme']) . "://" . strtolower(idn_to_ascii($urlparts['host']));
        $url = $urlBase . $encodedPath;

        if($addQuery && isset($urlparts['query']) && !empty($urlparts['query']))
        {
            $url.= '?' . $urlparts['query'];
        }

        if($addFragment && isset($urlparts['fragment']) && !empty($urlparts['fragment']))
        {
            $url.= '#' . $urlparts['fragment'];
        }

        //FIXME: besseren URL-Validator verwenden
        if(!filter_var($urlBase, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED)) {
            $this->getLogger()->log(__METHOD__, "invalid URL (filter): " . $url, LOG_ERR);
            return false;
        }

        return $url;
        //return $urlparts['scheme'] . "://" . idn_to_ascii($urlparts['host']) . $urlparts['path'];
    }


    public function loadAndValidateThumbnailParameters($otnApiKey, $otnWidth, $otnEffect, $url, $waitimg, $referrerUrl, $forceUpdate, $callback, $userAgentStr)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $result = true;

        // otnApiKey

        if (!$otnApiKey) {
            $this->getLogger()->log(__METHOD__, "no otnApiKey used", LOG_DEBUG);
        }else if(!is_string($otnApiKey) || !strlen($otnApiKey) == 32) {
            $this->getLogger()->log(__METHOD__, "invalid otnApiKey: " . strval($otnApiKey), LOG_ERR);
        } else {
            $account = $this->getAccountModel()->getByApiKey($otnApiKey);

            if (!$account instanceof Account) {
                $this->getLogger()->log(__METHOD__, "invalid account", LOG_WARNING);
            } else {
                //TODO: check if active and not expired
                $this->apiKey = $otnApiKey;
                $this->account = $account;

                // track all active accounts on daily basis
                $this->getAccountModel()->addToActiveAccountsDailyStats($account->getId());
            }
        }

        // otnWidth

        if (!$otnWidth || !in_array($otnWidth, Settings::getApiValidWidths())) {
            $this->getLogger()->log(__METHOD__, "invalid otnWidth: " . strval($otnWidth), LOG_ERR);
            $result = false;
        } else {
            $this->thumbnailWidth = $otnWidth;
        }

        // otnEffect

        if (!$otnEffect || !is_string($otnEffect) || !in_array($otnEffect, array_keys(Settings::getImageEffects()))) {
            $this->getLogger()->log(__METHOD__, "invalid otnEffect: " . strval($otnEffect), LOG_ERR);
            $result = false;
        } else {
            $this->thumbnailEffect = $otnEffect;
        }

        // url

        //TODO: disable addQuery parameter again
        $url = $this->getValidatedUrl($url, true, false);

        if(!$url) {
            $this->getLogger()->log(__METHOD__, "invalid url", LOG_DEBUG);
            $result = false;
        }else {
	        $this->getLogger()->log(__METHOD__, "requested url: " . $url, LOG_DEBUG);
            if (!$result) {
                $this->getLogger()->log(__METHOD__, "not building target because of previous error(s)", LOG_ERR);
            } else {
                // check if target already exists
                //$targetIsNew = $this->getTargetModel()->isTargetExistsByUrl($url) ? false : true;

                $target = $this->getTargetModel()->getOrCreateByUrl($url, $this->thumbnailWidth, $this->thumbnailEffect);

                if (!$target instanceof Target) {
                    $this->getLogger()->log(__METHOD__, "invalid target", LOG_ERR);
                    $result = false;
                } else {
                    $this->target = $target;

                    if (!$this->target->getCurrentImage() instanceof Image) {
                        $this->getLogger()->log(__METHOD__, "invalid target", LOG_ERR);
                        $result = false;
                    }
                }
            }
        }

        // waitimg

        if (!$result) {
            $this->getLogger()->log(__METHOD__, "not checking waitimg because of previous error(s)", LOG_DEBUG);
        } else {
            if ($waitimg != null) {
                if (!is_string($waitimg) || !filter_var($waitimg, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED)) {
                    $this->getLogger()->log(__METHOD__, "invalid waitimg", LOG_WARNING);
                } else {
                    $this->getLogger()->log(__METHOD__, "set waitimg to: " . $waitimg, LOG_INFO);
                    $this->waitimg = $waitimg;
                }
            } else {
                $this->getLogger()->log(__METHOD__, "not using waitimg", LOG_DEBUG);
            }
        }

        // referrer

        if (!$result) {
            $this->getLogger()->log(__METHOD__, "not checking referrer because of previous error(s)", LOG_DEBUG);
        } else {
            if(!Settings::isEnergySaveActive() && $referrerUrl != null) {

                $referrerUrl = $this->getValidatedUrl($referrerUrl, true, true);

                if($referrerUrl == null) {
                    $this->getLogger()->log(__METHOD__, "invalid referrerUrl: " . $referrerUrl, LOG_WARNING);
                }else {
                    if ($this->account instanceof Account) {
                        $referrer = $this->getReferrerModel()->getOrCreateByUrl($referrerUrl, $this->account->getId());
                    } else {
                        $referrer = $this->getReferrerModel()->getOrCreateByUrl($referrerUrl);
                    }

                    if (!$referrer instanceof Referrer) {
                        $this->getLogger()->log(__METHOD__, "invalid referrer: " . $referrerUrl, LOG_WARNING);
                    } else {
                        $this->referrer = $referrer;

                        if ($referrer->getAccountId()) {
                            /** @var Account $account */
                            $account = null;

                            if (!$this->account instanceof Account) {
                                $account = $this->getAccountModel()->getById($referrer->getAccountId());
                            }

                            // only load account by referrer if whitelist is enabled in account settings
                            if ($account instanceof Account) {
                                $this->getLogger()->log(__METHOD__, "found account " . $account->getId() . " by its referrer", LOG_DEBUG);

                                if ($account->isWhitelistActive()) {
                                    $this->getLogger()->log(__METHOD__, "whitelist active for account " . $account->getId(), LOG_DEBUG);
                                    $this->account = $account;
                                    $this->getReferrerModel()->checkDomainVerificationKeyExpired($this->referrer, $this->account);
                                } else {
                                    $this->getLogger()->log(__METHOD__, "ignoring account " . $account->getId(), LOG_DEBUG);
                                }
                            }
                        }

                        $this->getReferrerModel()->addTargetMapping($this->referrer, $this->target);
                        $this->getTargetModel()->addReferrerMapping($this->target, $this->referrer);

                        $this->getApiStatistics()->updateReferrerLastSeenStats($this->referrer->getId());
                    }
                }
            }else {
                $this->getLogger()->log(__METHOD__, "not using referrer", LOG_DEBUG);
            }
        }


        // user agent

        if(!$this->referrer && Settings::isStoreUserAgents()) {
            if (!$result) {
                $this->getLogger()->log(__METHOD__, "not checking user agent because of previous error(s)", LOG_DEBUG);
            } else {
                if (!Settings::isEnergySaveActive() && $userAgentStr != null) {

                    $userAgent = $this->getUserAgentModel()->getOrCreateByDescription($userAgentStr);

                    if (!$userAgent instanceof UserAgent) {
                        $this->getLogger()->log(__METHOD__, "invalid user agent: " . $userAgentStr, LOG_WARNING);
                    } else {
                        $this->userAgent = $userAgent;

                        $this->getUserAgentModel()->addTargetMapping($this->userAgent, $this->target);
                        $this->getTargetModel()->addUserAgentMapping($this->target, $this->userAgent);

                        $this->getApiStatistics()->updateUserAgentLastSeenStats($this->userAgent->getId());
                        $this->getApiStatistics()->incrementUserAgentRequestStats($this->userAgent);
                    }
                } else {
                    $this->getLogger()->log(__METHOD__, "not using user agent", LOG_DEBUG);
                }
            }
        }


        // forceUpdate

        if ($forceUpdate) {
            $this->forceUpdate = true;
        }

        // callback

        if ($callback != null) {
            if(!is_string($callback) || !preg_match('/^jQuery[0-9]+_[0-9]+$|^jsonp[0-9]+$/', $callback)) {
                $this->getLogger()->log(__METHOD__, "invalid callback", LOG_WARNING);
            }else {
                $this->getLogger()->log(__METHOD__, "set callback to: " . $callback, LOG_INFO);
                $this->callback = $callback;
            }
        }else {
            $this->getLogger()->log(__METHOD__, "not using callback", LOG_DEBUG);
        }

        return $result;
    }


    // Thumbnail

    private function getRandomImageServer()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $rand = array_rand(Settings::getFrontendImageHosts());
        return Settings::getFrontendImageHosts()[$rand];
    }


    protected function processThumbnailRequest()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);
        $output = array();

        $targetPriority = Settings::getTargetDefaultPriority();
        $imageMaxAge = Settings::getImageDefaultMaxAge();

        if($this->account) {
            $targetPriority = Settings::getTargetPriority($this->account->getApiKeyType());
            $imageMaxAge = Settings::getImageMaxAge($this->account->getApiKeyType());
        }

	    $imageIsUpToDate = $this->getImageModel()->checkImageCurrentness($this->target, $this->target->getCurrentImage(), $this->forceUpdate);

	    if(!$imageIsUpToDate)
	    {
		    $this->target->setForcedUpdate(true);
	    }

	    $this->getTargetModel()->checkTargetCurrentness($this->target, $targetPriority, $imageMaxAge);


        if ((Settings::isApiKeyOrReferrerWhitelistOnly()
                && (!$this->account && !in_array($this->target->getUrl(), $this->frontendImageUrls)
                    && (!$this->referrer || !$this->referrer->isWhitelisted())))
            || ($this->referrer && $this->referrer->isBlacklisted())) {
            $this->getLogger()->log(__METHOD__, "violation: " . $this->target->getUrl(), LOG_INFO);
            $output = $this->generateViolationOutput();
        //}else if($this->target->isBlacklisted())
        }else if($this->getTargetModel()->isBlacklisted($this->target->getUrl()))
        {
            $this->getLogger()->log(__METHOD__, "target is blacklisted: " . $this->target->getUrl(), LOG_INFO);
            $output = $this->generateRobotsOutput();
        } else if ($this->target->getCurrentImage()->getTsLastUpdated()) {

            // ALL OK

            $output['status'] = "ok";

            //TODO: ist diese if-Abrage ok? Mögliche Kombinationen prüfen
            if (Settings::isImageWatermarksEnabled() == false ||
                ($this->account && (!$this->account->getMaxDailyRequests() || $this->account->getRequestStats() < $this->account->getMaxDailyRequests()))
//                    || (!isset($this->account) && ($this->referrer && $this->referrer->isWhitelisted()))) {
            ) {

                $this->getLogger()->log(__METHOD__, "using unbranded image for target " . $this->target->getId(), LOG_DEBUG);
                $imageCacheKey = $this->getImageModel()->prepareCachedImage($this->target, false);
            } else {
                $this->getLogger()->log(__METHOD__, "using branded image for target " . $this->target->getId(), LOG_DEBUG);
                $imageCacheKey = $this->getImageModel()->prepareCachedImage($this->target, true);
            }

            $this->getLogger()->log(__METHOD__, "imageCacheKey: " . $imageCacheKey, LOG_DEBUG);

            if ($imageCacheKey) {
                $output['redirectUrl'] = $this->httpProtocol . "://" . $this->getRandomImageServer() . "/image/" . $imageCacheKey . "." . Settings::getImageFiletype($this->target->getCurrentImage()->getEffect());

                if ($this->forceDebug) {
                    $output['redirectUrl'] .= "?otnDebug";
                }
                $this->getLogger()->log(__METHOD__, "cached image found: " . $output['redirectUrl'], LOG_DEBUG);
            } else {
                $this->getLogger()->log(__METHOD__, "no cached image found.", LOG_INFO);
                $output = $this->generateDummyOutput();
            }

        } else {
	        //TODO: für Dummy-Bilder nicht die numRequests für den Original-API-Key hochzählen!
	        if (!is_null($this->target->isRobotsAllowed()) && !$this->target->isRobotsAllowed()) {
		        $output = $this->generateRobotsOutput();
	        }elseif ($this->target->getCounterFailed() >= Settings::getTargetMaxTries()) {
                    $output = $this->generateBrokenOutput();
            } else {
                $output = $this->generateDummyOutput();
            }
        }

        return $output;
    }


    public function outputThumbnail()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

	    $slimResponse = new SlimResponse();

        $output = $this->processThumbnailRequest();

	    $slimResponse->setExpires('Sat, 26 Jul 1997 05:00:00 GMT');
	    $slimResponse->setCacheControl('no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
	    $slimResponse->setPragma('no-cache');

        //TODO: $this->publishLogsAsHeaders();

        $validOutputCodes = array("dummy", "violation", "robots", "broken", "ok");

        if (!in_array($output['status'], $validOutputCodes)) {
            $this->getLogger()->log(__METHOD__, "invalid output code: " . $output['status'], LOG_WARNING);
            return $slimResponse;
        }

        // CHECK FOR INFINITE LOOP
        if($output['status'] != "ok" && isset($output['newTargetUrl']))
        {
            $this->getLogger()->log(__METHOD__, "requested URL: " . $this->target->getUrl(), LOG_DEBUG);
            $this->getLogger()->log(__METHOD__, "delivered URL: " . $output['newTargetUrl'], LOG_DEBUG);

            if($this->target->getUrl() == $output['newTargetUrl'])
            {
                $this->getLogger()->log(__METHOD__, "Loop detected: " . $this->target->getUrl(), LOG_DEBUG);
	            $slimResponse->setHttpStatus(500);

                return $slimResponse;
            }
        }

		if($output['status'] == "dummy" && $this->callback)
		{
			//don't track dummies with callback URL
		}else
		{
			if(!Settings::isEnergySaveActive())
			{
				$this->getApiStatistics()->updateTargetRequestStats($this->target->getId());
				$this->getApiStatistics()->incrementImageRequestStats($this->target->getCurrentImage()->getId());
			}

			$this->getApiStatistics()->incrementDeliveryDailyStats();
			$this->getApiStatistics()->trackPiwik($this->target, $this->referrer, $this->account, $this->callback);
		}

        // don't count statistics for dummy images
        if ($output['status'] != "dummy" && (strtoupper($this->httpRequestMethod) == "HEAD" || strtoupper($this->httpRequestMethod) == "GET"))
        {
            if($this->account) {
                $this->getApiStatistics()->incrementApiKeyRequestStats($this->account);
            }

            if(!Settings::isEnergySaveActive() && $this->referrer) {
                $this->getApiStatistics()->incrementReferrerRequestStats($this->referrer);
                $this->getApiStatistics()->incrementReferrerDeeplinkRequestStats($this->referrer->getCurrentDeeplink());
            }
        }

        if ($this->callback) {
            $slimResponse->setContentType('application/json');

            if ($output['status'] == "dummy") {
                $slimResponse->setOutput($this->callback . "(" . json_encode(array("url" => "wait")) . ")");
            } else {
	            $slimResponse->setOutput($this->callback . "(" . json_encode(array("url" => $output['redirectUrl'])) . ")");
            }
        } else {
	        $slimResponse->setRedirect(array($output['redirectUrl'] . (isset($output['newTargetUrl']) ? $output['newTargetUrl'] : ""), 307));
        }

        return $slimResponse;
    }


    // Cached Image

    public function loadAndValidateCachedImageParameters($otnCacheId)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $result = true;

        // otnCacheId


        if (!$otnCacheId || !is_string($otnCacheId) || !preg_match('/^[a-z0-9]{32}$/', $otnCacheId)) {
            $this->getLogger()->log(__METHOD__, "invalid otnCacheId", LOG_ERR);
            $result = false;
        } else {
            $this->cacheId = $otnCacheId;
        }

        return $result;
    }


    public function outputCachedImage()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        if (!$this->cacheId) {
            $this->getLogger()->log(__METHOD__, "invalid cacheId", LOG_DEBUG);
            return false;
        }

        $output = NULL;
	    $slimResponse = new SlimResponse();

        $this->getLogger()->log(__METHOD__, "cacheId: " . $this->cacheId, LOG_DEBUG);

        $cachedImage = $this->getImageModel()->getCachedImage($this->cacheId);


        if ($cachedImage) {

	        $lastModified = time() - (Settings::getRedisImageCacheExpire() - $cachedImage->getTtl());
            $expires = time() + $cachedImage->getTtl();

	        $slimResponse->setExpires(gmdate("r", $expires));
	        $slimResponse->setLastModified($lastModified);
	        $slimResponse->setPragma('cache');
	        $slimResponse->setCacheControl('store, cache');
	        $slimResponse->setContentType('image/' . $cachedImage->getFileType());

            //header("X-OpenThumbnails-Captured: " . gmdate('D, d M Y H:i:s \G\M\T', $cachedImage->getTsCaptured()));
            //header("X-OpenThumbnails-SnipeDuration: " . $cachedImage->getSnipeDuration());

	        $slimResponse->setOutput(base64_decode($cachedImage->getImageData()));

	        return $slimResponse;
        } else {
            $this->getLogger()->log(__METHOD__, "invalid cachedImage", LOG_ERR);
            return false;
        }
    }


    // Generator


    public function agentProcessMasterCommit($data, $mode)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        if ($data == NULL) {
            return $this->getLogger()->logEcho(__METHOD__, "invalid result", LOG_ERR);
        }

        $this->getLogger()->log(__METHOD__, "size of base64 encoded target : " . strlen($data), LOG_DEBUG);

        $resultData_serialized = base64_decode($data, TRUE);

        $this->getLogger()->log(__METHOD__, "size of base64 decoded target : " . strlen($resultData_serialized), LOG_DEBUG);

        /** @var Target $target */
        $target = unserialize($resultData_serialized);

        if (!$target instanceof Target) {
            return $this->getLogger()->logEcho(__METHOD__, "invalid target", LOG_ERR);
        }

        $this->getLogger()->log(__METHOD__, "size of target image : " . strlen($target->getMasterImage()), LOG_DEBUG);

        //TODO: Einzeldaten validieren (Security!)

        if ($this->getTargetModel()->commitMasterImage($target, $mode)) {
            // enqueue target's images

            //FIXME: do something with related image to have them queued after updating the master image
            //disabled during mongo migration - has to be enabled again

            $target = $this->getTargetModel()->getById($target->getId());
            $images = $this->getImageModel()->getImages($target->getId());

            //$this->getLogger()->log(__METHOD__, "target images: " . print_r($images, true), LOG_INFO);

            /** @var Image $image */
            foreach ($images as $image) {
                //enqueue
                //$this->getImageModel()->checkImageCurrentness($target, $image, $this->forceUpdate);
                $this->getImageModel()->checkOut($image->getId());
            }

            //FIXME: return code auswerten
        }

        return "OK";
    }


    public function agentProcessThumbnailsCommit($data)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        if ($data == NULL) {
            return $this->getLogger()->logEcho(__METHOD__, "invalid result", LOG_ERR);
        }

        $resultData_serialized = base64_decode($data, TRUE);
        $target = unserialize($resultData_serialized);

        if (!$target instanceof Target) {
            return $this->getLogger()->logEcho(__METHOD__, "invalid target", LOG_ERR);
        }

        //TODO: Einzeldaten validieren (Security!)

        $this->getTargetModel()->commitThumbnails($target);

        return "OK";
    }



    private function initAgentSleepDuration($type)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $collection = new \MongoCollection($this->getMongoDB(), 'configuration');

        $query = array(
            '_id' => 'live',
        );

        $update = array(
            '$setOnInsert' => array(
                'agentLastSleepDuration_' . $type => 1
            )
        );

        $options = array(
            'upsert' => true
        );

        $collection->update($query, $update, $options);
    }


    private function incrementAgentSleepDuration($type)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $collection = new \MongoCollection($this->getMongoDB(), 'configuration');

        $query = array(
            '_id' => 'live',
            '$or' => array(
                array(
                    'agentLastSleepDuration_' . $type => array(
                        '$exists' => false
                    )
                ),
                array(
                    'agentLastSleepDuration_' . $type => array(
                        '$lt' => Settings::getAgentMaxSleepDuration()
                    )
                )
            )
        );

        $update = array(
            '$inc' => array(
                'agentLastSleepDuration_' . $type => 1
            )
        );

        $collection->update($query, $update);
    }


    private function decrementAgentSleepDuration($type)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $collection = new \MongoCollection($this->getMongoDB(), 'configuration');

        $query = array(
            '_id' => 'live',
            'agentLastSleepDuration_' . $type => array(
                '$gt' => 0
            )
        );

        $update = array(
            '$inc' => array(
                'agentLastSleepDuration_' . $type => -1
            )
        );
        $collection->update($query, $update);
    }


    private function getCalculatedAgentSleepDuration($type)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $sleepDuration = mt_rand(3, Settings::getAgentMaxSleepDuration());

        $this->initAgentSleepDuration($type);

        $collection = new \MongoCollection($this->getMongoDB(), 'configuration');

        $configData = $collection->findOne(array('_id' => 'live'), array('agentLastSleepDuration_' . $type => true));

        if(is_array($configData) && isset($configData['agentLastSleepDuration_' . $type]) && is_numeric($configData['agentLastSleepDuration_' . $type])) {
            $sleepDuration = intval($configData['agentLastSleepDuration_' . $type]);
        }

        $this->incrementAgentSleepDuration($type);

        $this->getLogger()->log(__METHOD__, "agent (" . $type . ") shall sleep for " . $sleepDuration . " seconds", LOG_DEBUG);

        return $sleepDuration;
    }



    public function agentGetMasterJob($priority)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $target = $this->getTargetModel()->getNextMasterJob($priority);

        if(!$target instanceof Target)
        {
            $this->getLogger()->log(__METHOD__, "no target found", LOG_INFO);
            return $this->getCalculatedAgentSleepDuration('master_' . $priority);
        }else {
            $this->decrementAgentSleepDuration('master_' . $priority);
        }

        if ($target->getCounterFailed() <= Settings::getTargetMaxTries() / 2) {
            $target->setJavaScriptEnabled(true);
        } else {
            $target->setJavaScriptEnabled(false);
        }

        if ($target->getCounterFailed() % 2 == 0) {
            $target->setWeapon(Settings::getWeaponCutycapt());
        } else {
            $target->setWeapon(Settings::getWeaponWkhtml());
        }

        $this->getLogger()->log(__METHOD__, "prepared job for targetId " . $target->getId(), LOG_DEBUG);

        return base64_encode(serialize($target));
    }



    public function agentGetThumbnailJob()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $jobData = $this->getImageModel()->getNextThumbnailJob();

        if (!$jobData) {
            $this->getLogger()->log(__METHOD__, "no target job", LOG_DEBUG);
            return $this->getCalculatedAgentSleepDuration('thumbnail');
        }else {
            $this->decrementAgentSleepDuration('thumbnail');
        }

        $image = $this->getImageModel()->getById($jobData);

        if (!$image instanceof Image) {
            $this->getLogger()->log(__METHOD__, "invalid image " . $jobData, LOG_ERR);
            return false;
        }

        //TODO: ist nur ein Versuch. Ist das hier zu umständlich?
        $target = $this->getTargetModel()->getById($image->getTargetId());

	    $redisTargetMastImageKey = Settings::getRedisKeyTargetMasterImageData() . $target->getId();

	    if(!$target->getFileId() && !$this->getRedis()->exists($redisTargetMastImageKey))
	    {
		    $this->getImageModel()->dequeue($image);
		    $this->getLogger()->log(__METHOD__, "missing fileId in target " . $target->getId(), LOG_ERR);
		    return $this->getCalculatedAgentSleepDuration('thumbnail');
	    }

        $target->setImages(array($image));

		//TODO: wird MasterImage besser in TargetModel gesetzt?
		$mastImage = $this->getImageModel()->getMasterImage($target);

		if($mastImage)
		{
			$target->setMasterImage($mastImage);
			$base64 = base64_encode(serialize($target));
		}else
		{
			$this->getImageModel()->dequeue($image);
			$this->getLogger()->log(__METHOD__, "no master image for target " . $target->getId() . " found", LOG_ERR);
			return $this->getCalculatedAgentSleepDuration('thumbnail');
		}

        return $base64;
    }



    public function agentProcessMasterFailure($data, $mode)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        if ($data == NULL) {
            return $this->getLogger()->logEcho(__METHOD__, "invalid result", LOG_ERR);
        }

        $resultData_serialized = base64_decode($data, TRUE);
        $target = unserialize($resultData_serialized);

        if (!$target instanceof Target) {
            return $this->getLogger()->logEcho(__METHOD__, "invalid target", LOG_ERR);
        }

        //TODO: Einzeldaten validieren (Security!)

        $this->getTargetModel()->failedMasterImage($target, $mode);

        return "OK";
    }


    // Getter/Setter

    public function getAction()
    {
        return $this->action;
    }



    private function generateRobotsOutput()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $output['status'] = "robots";

	    if($this->waitimg)
	    {
		    $output['redirectUrl'] = $this->waitimg;
	    }else
	    {
		    $output['redirectUrl'] = $this->httpProtocol . "://" . Settings::getApiHost() . "/v3/thumbnail/";

		    if($this->account)
		    {
			    $output['redirectUrl'] .= $this->apiKey . "/";
		    }

		    $output['redirectUrl'] .= $this->target->getCurrentImage()->getWidth() .
			    "/" . $this->target->getCurrentImage()->getEffect() . "/?";

		    if($this->forceDebug)
		    {
			    $output['redirectUrl'] .= "otnDebug&";
		    }

		    $output['redirectUrl'] .= "url=";
		    $output['newTargetUrl'] = Settings::getFrontendImagesUrl() . Settings::getFrontendImagesPathRobots();
	    }

        return $output;
    }


    private function generateBrokenOutput()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $output['status'] = "broken";

	    if($this->waitimg)
	    {
		    $output['redirectUrl'] = $this->waitimg;
	    }else
	    {
		    $output['redirectUrl'] = $this->httpProtocol . "://" . Settings::getApiHost() . "/v3/thumbnail/";

		    if($this->account)
		    {
			    $output['redirectUrl'] .= $this->apiKey . "/";
		    }

		    $output['redirectUrl'] .= $this->target->getCurrentImage()->getWidth() .
			    "/" . $this->target->getCurrentImage()->getEffect() . "/?";

		    if($this->forceDebug)
		    {
			    $output['redirectUrl'] .= "otnDebug&";
		    }

		    $output['redirectUrl'] .= "url=";
		    $output['newTargetUrl'] = Settings::getFrontendImagesUrl() . Settings::getFrontendImagesPathBroken();
	    }

        return $output;
    }



    private function generateDummyOutput()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $output['status'] = "dummy";

        if($this->waitimg)
        {
            $output['redirectUrl'] = $this->waitimg;
        }else
        {
            $output['redirectUrl'] = $this->httpProtocol . "://" . Settings::getApiHost() . "/v3/thumbnail/";

            if ($this->account) {
                $output['redirectUrl'] .= $this->apiKey . "/";
            }

            $output['redirectUrl'] .= $this->target->getCurrentImage()->getWidth() .
                "/" . $this->target->getCurrentImage()->getEffect() . "/?";

            if ($this->forceDebug) {
                $output['redirectUrl'] .= "otnDebug&";
            }

            $output['redirectUrl'] .= "url=";
            $output['newTargetUrl'] = Settings::getFrontendImagesUrl() . Settings::getFrontendImagesPathDummy();
        }

        return $output;
    }


    private function generateViolationOutput()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $output['status'] = "violation";
        $output['redirectUrl'] = $this->httpProtocol . "://" . Settings::getApiHost() . "/v3/thumbnail/";

        if ($this->account) {
            $output['redirectUrl'] .= $this->apiKey . "/";
        }

        $output['redirectUrl'].= $this->target->getCurrentImage()->getWidth() .
            "/" . $this->target->getCurrentImage()->getEffect() . "/?";

        if ($this->forceDebug) {
            $output['redirectUrl'] .= "otnDebug&";
        }

        $output['redirectUrl'] .= "url=";
        $output['newTargetUrl'] = Settings::getFrontendImagesUrl() . Settings::getFrontendImagesPathViolation();

        return $output;
    }


    protected function getMongoDB()
    {
        if(!$this->mongodb instanceof MongoDB) {
            $this->getLogger()->log(__METHOD__, "init new MongoDB instance", LOG_DEBUG);

            $mongodb = new Mongo($this->getLogger(), Settings::getMongoHost(), Settings::getMongoPort(), Settings::getMongoUser(), Settings::getMongoPass(), Settings::getMongoDb());
            $connection = $mongodb->getConnection();

            if($connection) {
                $this->mongodb = $connection;
            } else {
                $this->getLogger()->log(__METHOD__, "Could not connect to MongoDB", LOG_ERR);
                die();
            }
        }

        return $this->mongodb;
    }


	protected function getRedis()
	{
		if(!$this->redis instanceof Client) {
			$this->getLogger()->log(__METHOD__, "init new Redis instance", LOG_DEBUG);

			$redis = new Redis($this->getLogger(), Settings::getRedisScheme(), Settings::getRedisHost(), Settings::getRedisPort(), Settings::getRedisDb());
			$connection = $redis->getConnection();

			if($connection) {
				$this->redis = $connection;
			} else {
				$this->getLogger()->log(__METHOD__, "Could not connect to Redis", LOG_ERR);
				die();
			}
		}

		return $this->redis;
	}


    protected function getLogger()
    {
        if(!$this->logger instanceof Logger)
        {
            $this->logger = new Logger($this->forceDebug);
            $this->logger->log(__METHOD__, "init new Logger instance", LOG_DEBUG);
        }

        return $this->logger;
    }


    protected function getAccountModel()
    {
        if(!$this->accountModel instanceof AccountModel) {
            $this->getLogger()->log(__METHOD__, "init new AccountModel instance", LOG_DEBUG);
            $this->accountModel = new AccountModel($this->getMongoDB(), $this->getLogger());
        }

        return $this->accountModel;
    }


    protected function getReferrerModel()
    {
        if(!$this->referrerModel instanceof ReferrerModel) {
            $this->getLogger()->log(__METHOD__, "init new ReferrerModel instance", LOG_DEBUG);
            $this->referrerModel = new ReferrerModel($this->getMongoDB(), $this->getLogger());
        }

        return $this->referrerModel;
    }


    protected function getReferrerDeeplinkModel()
    {
        if(!$this->referrerDeeplinkModel instanceof ReferrerDeeplinkModel) {
            $this->getLogger()->log(__METHOD__, "init new ReferrerDeeplinkModel instance", LOG_DEBUG);
            $this->referrerDeeplinkModel = new ReferrerDeeplinkModel($this->getMongoDB(), $this->getLogger());
        }

        return $this->referrerDeeplinkModel;
    }


    protected function getTargetModel()
    {
        if(!$this->targetModel instanceof TargetModel) {
            $this->getLogger()->log(__METHOD__, "init new TargetModel instance", LOG_DEBUG);
            $this->targetModel = new TargetModel($this->getMongoDB(), $this->getRedis(), $this->getLogger());
        }

        return $this->targetModel;
    }


    protected function getImageModel()
    {
        if(!$this->imageModel instanceof ImageModel) {
            $this->getLogger()->log(__METHOD__, "init new ImageModel instance", LOG_DEBUG);
            $this->imageModel = new ImageModel($this->getMongoDB(), $this->getRedis(), $this->getLogger());
        }

        return $this->imageModel;
    }


    protected function getOauthModel()
    {
        if(!$this->oauthModel instanceof OauthModel) {
            $this->getLogger()->log(__METHOD__, "init new OauthModel instance", LOG_DEBUG);
            $this->oauthModel = new OauthModel($this->getMongoDB(), $this->getLogger());
        }

        return $this->oauthModel;
    }


    protected function getApiStatistics()
    {
        if(!$this->apiStatistics instanceof ApiStatistics) {
            $this->getLogger()->log(__METHOD__, "init new ApiStatistics instance", LOG_DEBUG);
            $this->apiStatistics = new ApiStatistics($this->getMongoDB(), $this->getRedis(), $this->getLogger());
        }

        return $this->apiStatistics;
    }


    protected function getUserAgentModel()
    {
        if(!$this->userAgentModel instanceof UserAgentModel) {
            $this->getLogger()->log(__METHOD__, "init new UserAgentModel instance", LOG_DEBUG);
            $this->userAgentModel = new UserAgentModel($this->getMongoDB(), $this->getLogger());
        }

        return $this->userAgentModel;
    }
}
