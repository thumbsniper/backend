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
use Slim\Helper\Set;
use ThumbSniper\account\Account;
use ThumbSniper\account\AccountModel;
use ThumbSniper\account\OauthModel;
use ThumbSniper\common\Helpers;
use ThumbSniper\common\Logger;
use ThumbSniper\common\Settings;
use ThumbSniper\db\Mongo;
use ThumbSniper\db\Redis;
use ThumbSniper\objective\ReferrerDeeplinkModel;
use ThumbSniper\objective\RequestController;
use ThumbSniper\objective\UserAgent;
use ThumbSniper\objective\UserAgentModel;
use ThumbSniper\objective\Visitor;
use ThumbSniper\objective\VisitorModel;
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

    /** @var VisitorModel */
    private $visitorModel;

    /** @var Account */
    protected $account;

    /** @var Target */
    protected $target;

    // Make the plain URL available without an object
    // to be able to use it without a Target->getOrCreate() call
    protected $targetUrl;

    /** @var Visitor */
    protected $visitor;

    protected $httpProtocol;
    protected $httpRequestMethod;

    protected $thumbnailWidth;
    protected $thumbnailEffect;
    protected $action;
    protected $forceDebug;
    protected $waitimg;
    protected $forceUpdate;
    protected $callback;
    protected $cacheId;

    protected $frontendImageUrls;

    /** @var RequestController */
    protected $requestController;


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


    public function publishLogsAsHeaders()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $this->getLogger()->publishMessagesAsHeaders();
    }


    public function loadAndValidateCommonParameters($action)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);
        return $this->getRequestController()->validateApiAction($action);
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

        if(!is_array($urlparts)) {
            $this->getLogger()->log(__METHOD__, "invalid URL (invalid URL parts)", LOG_ERR);
            return false;
        }elseif(!isset($urlparts['scheme'])) {
            $this->getLogger()->log(__METHOD__, "invalid URL (invalid URL parts - invalid URL scheme)", LOG_ERR);
            return false;
        }elseif(!isset($urlparts['host']))
        {
            $this->getLogger()->log(__METHOD__, "invalid URL (invalid URL parts - host missing)", LOG_ERR);
            return false;
        }

        if(Helpers::isPrivateIpAddress($urlparts['host']))
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


    protected function checkApiKey($apiKey)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $result = false;

        if (!$apiKey) {
            $this->getLogger()->log(__METHOD__, "no apiKey used", LOG_DEBUG);
        }else if(!is_string($apiKey) || !strlen($apiKey) == 32) {
            $this->getLogger()->log(__METHOD__, "invalid apiKey: " . strval($apiKey), LOG_ERR);
        } else {
            $account = $this->getAccountModel()->getByApiKey($apiKey);

            if (!$account instanceof Account) {
                $this->getLogger()->log(__METHOD__, "invalid account", LOG_WARNING);
            } else {
                //TODO: check if active and not expired
                $this->account = $account;

                // track all active accounts on daily basis
                $this->getAccountModel()->addToActiveAccountsDailyStats($account->getId());

                $result = true;
            }
        }

        return $result;
    }


    protected function validateWidth($width)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        if ($width && in_array($width, Settings::getApiValidWidths())) {
           return $width;
        }

        return null;
    }


    protected function checkWidth($width)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $result = false;
        $validatedWidth = $this->validateWidth($width);

        if (!$validatedWidth) {
            $this->getLogger()->log(__METHOD__, "invalid width: " . strval($width), LOG_ERR);
        } else {
            $this->thumbnailWidth = $validatedWidth;
            $result = true;
        }

        return $result;
    }


    protected function validateEffect($effect)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        if($effect && is_string($effect) && in_array($effect, array_keys(Settings::getActiveImageEffects()))) {
            return $effect;
        }else {
            return null;
        }
    }


    protected function checkEffect($effect)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $result = false;
        $validatedEffect = $this->validateEffect($effect);

        if (!$validatedEffect) {
            $this->getLogger()->log(__METHOD__, "invalid effect: " . strval($effect), LOG_ERR);
        } else {
            $this->thumbnailEffect = $validatedEffect;

            $result = true;
        }

        return $result;
    }


    protected function checkTargetUrl($url, $result)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

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
                    $this->targetUrl = $target->getUrl();

                    if (!$this->target->getCurrentImage() instanceof Image) {
                        $this->getLogger()->log(__METHOD__, "invalid target", LOG_ERR);
                        $result = false;
                    }
                }
            }
        }

        return $result;
    }


    protected function checkReferrer($referrerUrl)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $referrer = null;
        
        if(!Settings::isEnergySaveActive() && $referrerUrl != null) {

            $referrerUrl = $this->getValidatedUrl($referrerUrl, true, true);

            if($referrerUrl == null) {
                $this->getLogger()->log(__METHOD__, "invalid referrerUrl: " . $referrerUrl, LOG_WARNING);
            }else {
                if ($this->account instanceof Account) {
                    $ref = $this->getReferrerModel()->getOrCreateByUrl($referrerUrl, $this->account->getId());
                } else {
                    $ref = $this->getReferrerModel()->getOrCreateByUrl($referrerUrl);
                }

                if (!$ref instanceof Referrer) {
                    $this->getLogger()->log(__METHOD__, "invalid referrer: " . $referrerUrl, LOG_WARNING);
                } else {
                    $referrer = $ref;

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
                                $this->getReferrerModel()->checkDomainVerificationKeyExpired($referrer, $this->account);
                            } else {
                                $this->getLogger()->log(__METHOD__, "ignoring account " . $account->getId(), LOG_DEBUG);
                            }
                        }
                    }

                    $this->getReferrerModel()->addTargetMapping($referrer, $this->target);
                    //$this->getTargetModel()->addReferrerMapping($this->target, $referrer);

                    $this->getApiStatistics()->updateReferrerLastSeenStats($referrer->getId());
                }
            }
        }else {
            $this->getLogger()->log(__METHOD__, "not using referrer", LOG_DEBUG);
        }

        return $referrer;
    }


    protected function checkUserAgent($userAgentStr)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $userAgent = null;

        if(Settings::isStoreUserAgents()) {
            if (!Settings::isEnergySaveActive() && $userAgentStr != null) {

                $ua = $this->getUserAgentModel()->getOrCreateByDescription($userAgentStr);

                if (!$ua instanceof UserAgent) {
                    $this->getLogger()->log(__METHOD__, "invalid user agent: " . $userAgentStr, LOG_WARNING);
                } else {
                    $userAgent = $ua;

                    //$this->getUserAgentModel()->addTargetMapping($this->userAgent, $this->target);
                    //$this->getTargetModel()->addUserAgentMapping($this->target, $this->userAgent);

                    $this->getApiStatistics()->updateUserAgentLastSeenStats($userAgent->getId());
                    //$this->getApiStatistics()->incrementUserAgentRequestStats($this->userAgent);
                }
            } else {
                $this->getLogger()->log(__METHOD__, "not using user agent", LOG_DEBUG);
            }
        }

        return $userAgent;
    }


    protected function checkVisitor($address, $userAgentStr, $referrerUrl, $result)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        if(Settings::isStoreVisitors()) {
            if (!$result) {
                $this->getLogger()->log(__METHOD__, "not checking visitor because of previous error(s)", LOG_DEBUG);
            } else {
                if (!Settings::isEnergySaveActive() && $address != null) {

                    $addressType = Helpers::getIpProtocol($address);
                    
                    if(!$addressType) {
                        $this->getLogger()->log(__METHOD__, "invalid addressType", LOG_ERR);
                        return $result;
                    }

                    $userAgent = $this->checkUserAgent($userAgentStr);
                    $referrer = $this->checkReferrer($referrerUrl);

                    $visitor = $this->getVisitorModel()->getOrCreateByAddress($address, $addressType, $userAgent, $referrer);

                    if (!$visitor instanceof Visitor) {
                        $this->getLogger()->log(__METHOD__, "invalid visitor: " . $address, LOG_WARNING);
                    } else {
                        $this->visitor = $visitor;

                        //$this->getVisitorModel()->addTargetMapping($this->userAgent, $this->target);
                        //$this->getTargetModel()->addUserAgentMapping($this->target, $this->userAgent);

                        $this->getApiStatistics()->updateVisitorLastSeenStats($this->visitor->getId());
                        //$this->getApiStatistics()->incrementVisitorRequestStats($this->visitor);
                    }
                } else {
                    $this->getLogger()->log(__METHOD__, "not using visitor", LOG_DEBUG);
                }
            }
        }

        return $result;
    }


    protected function checkCallback($callback)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $result = false;

        if ($callback != null) {
            if(!is_string($callback) || !preg_match('/^jQuery[0-9]+_[0-9]+$|^jsonp[0-9]+$/', $callback)) {
                $this->getLogger()->log(__METHOD__, "invalid callback", LOG_WARNING);
            }else {
                $this->getLogger()->log(__METHOD__, "set callback to: " . $callback, LOG_INFO);
                $this->callback = $callback;
                $result = true;
            }
        }else {
            $this->getLogger()->log(__METHOD__, "not using callback", LOG_DEBUG);
        }

        return $result;
    }


    private function isViolation($targetUrl, $referrerUrl)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $isViolation = false;

        if(Settings::isApiKeyOrReferrerWhitelistOnly())
        {
            if(!$this->account && !in_array($targetUrl, $this->frontendImageUrls))
            {
                $referrer = $this->getReferrerModel()->getByUrl($referrerUrl);

                if(!$referrer || !$referrer->isWhitelisted())
                {
                    $this->getLogger()->log(__METHOD__, "violation - invalid referrer: " . $referrerUrl, LOG_INFO);
                    $isViolation = true;
                }
            }
        }elseif($referrerUrl && $this->getValidatedUrl($referrerUrl, false, false) && $this->getReferrerModel()->isBlacklisted($this->getValidatedUrl($referrerUrl, false, false)))
        {
            $this->getLogger()->log(__METHOD__, "violation - referrer is blacklisted: " . $referrerUrl, LOG_INFO);
            $isViolation = true;
        }

        return $isViolation;
    }


    public function loadAndValidateThumbnailParameters($width, $effect, $url, $waitimg, $referrerUrl, $forceUpdate, $callback, $userAgentStr, $visitorAddress)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $result = true;

        if(!$this->checkWidth($width))
        {
            $result = false;
        }

        if(!$this->checkEffect($effect))
        {
            $result = false;
        }

        if(!$this->checkTargetUrl($url, $result))
        {
            $result = false;
        }

        if($result) {
            $this->getRequestController()->validateWaitImageUrl($waitimg);
        }

        $this->checkReferrer($referrerUrl, $result);
        $this->checkVisitor($visitorAddress, $userAgentStr, $referrerUrl, $result);
        $this->checkCallback($callback);

        $this->getLogger()->log(__METHOD__, "VISITOR: " . print_r($this->visitor, true), LOG_DEBUG);
        
        if ($forceUpdate) {
            $this->forceUpdate = true;
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

        //TODO: is it possible to combine the following if clauses?

        $targetIsBlacklisted = $this->getTargetModel()->isBlacklisted($this->target->getUrl());

        if(!$targetIsBlacklisted) {
            $targetPriority = Settings::getTargetDefaultPriority();
            $imageMaxAge = Settings::getImageDefaultMaxAge();

            if ($this->account) {
                $targetPriority = Settings::getTargetPriority($this->account->getApiKeyType());
                $imageMaxAge = Settings::getImageMaxAge($this->account->getApiKeyType());
            }

            $imageIsUpToDate = $this->getImageModel()->checkImageCurrentness($this->target, $this->target->getCurrentImage(), $this->forceUpdate);

            if (!$imageIsUpToDate) {
                $redisTargetMasterImageKey = Settings::getRedisKeyTargetMasterImageData() . $this->target->getId();

                if($this->redis->exists($redisTargetMasterImageKey))
                {
                    // old -> enqueued
                    $this->logger->log(__METHOD__, "checking out image " . $this->target->getCurrentImage()->getId() .  " (masterImage in Redis)", LOG_DEBUG);
                    $this->getImageModel()->checkOut($this->target->getCurrentImage()->getId());
                }else {
                    $this->logger->log(__METHOD__, "image is not checked out (masterImage is missing)", LOG_DEBUG);
                    $this->logger->log(__METHOD__, "enabling forced update for target " . $this->target->getId(), LOG_DEBUG);
                    $this->target->setForcedUpdate(true);
                }
            }

            $this->getTargetModel()->checkTargetCurrentness($this->target, $targetPriority, $imageMaxAge);
        }

        if(!in_array($this->target->getUrl(), $this->frontendImageUrls) && $targetIsBlacklisted)
        {
            $this->getLogger()->log(__METHOD__, "target is blacklisted: " . $this->target->getUrl(), LOG_INFO);
            $output = $this->generateRobotsOutput();
        } else if ($this->target->getCurrentImage()->getTsLastUpdated()) {

            // ALL OK

            $output['status'] = "ok";


            if(Settings::isAmazonS3enabled() && $this->target->getCurrentImage()->getAmazonS3url())
            {
                //TODO: differ between branded and unbranded images

                $this->getLogger()->log(__METHOD__, "using S3 presigned image URL for target " . $this->target->getId(), LOG_DEBUG);
                $url = $this->getImageModel()->getAmazonS3presignedUrl($this->target);

                if($url) {
                    $output['redirectUrl'] = $url;
                }else {
                    //FIXME: error handling
                }
            }elseif(Settings::isLocalThumbnailStorageEnabled()) {
                //TODO: ist diese if-Abrage ok? Mögliche Kombinationen prüfen
                if (Settings::isImageWatermarksEnabled() == false ||
                    ($this->account && (!$this->account->getMaxDailyRequests() || $this->account->getRequestStats() < $this->account->getMaxDailyRequests()))
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
            }else {
                $this->getLogger()->log(__METHOD__, "Neither S3 or local storage are active!", LOG_CRIT);
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


    public function outputThumbnail($apiKey, $width, $effect, $url, $waitimg, $referrerUrl, $forceUpdate, $callback, $userAgentStr, $visitorAddress)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $slimResponse = new SlimResponse();

        $this->checkApiKey($apiKey);
        $violation = $this->isViolation($url, $referrerUrl);

        if(!$violation) {
            if (!$this->loadAndValidateThumbnailParameters($width, $effect, $url, $waitimg, $referrerUrl, $forceUpdate, $callback, $userAgentStr, $visitorAddress)) {
                $this->getLogger()->log(__METHOD__, "invalid thumbnail parameters", LOG_ERR);
                //TODO: create SlimResponse
                return false;
            }

            $output = $this->processThumbnailRequest();
        }else {
            //TODO: What should we do if a request is a violation, but validation of width and/or effect also fails?
            $output = $this->generateViolationOutput($this->validateWidth($width), $this->validateEffect($effect));
        }

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
            $this->getLogger()->log(__METHOD__, "requested URL: " . $this->targetUrl, LOG_DEBUG);
            $this->getLogger()->log(__METHOD__, "delivered URL: " . $output['newTargetUrl'], LOG_DEBUG);

            if($this->targetUrl == $output['newTargetUrl'])
            {
                $this->getLogger()->log(__METHOD__, "Loop detected: " . $this->targetUrl, LOG_DEBUG);

                $output['redirectUrl'] = $this->httpProtocol . "://" . $this->getRandomImageServer() . Settings::getFrontendImagesPathTransparentPixel();
                $output['newTargetUrl'] = null;

	            //$slimResponse->setHttpStatus(500);
                //return $slimResponse;
            }
        }

		if($output['status'] == "dummy" && $this->callback)
		{
			//don't track dummies with callback URL
            //TODO: why not?
		}else
		{
			if(!$violation && !Settings::isEnergySaveActive())
			{
				$this->getApiStatistics()->updateTargetRequestStats($this->target->getId());
				$this->getApiStatistics()->incrementImageRequestStats($this->target->getCurrentImage()->getId());
			}

			$this->getApiStatistics()->incrementDeliveryDailyStats();
            
            if($this->visitor) {
                $this->getApiStatistics()->trackPiwik($this->target, $this->visitor->getReferrer(), $this->account, $this->callback);
            }
		}

        // don't count statistics for dummy images
        // UPDATE: why not?!
        //if ($output['status'] != "dummy" && (strtoupper($this->httpRequestMethod) == "HEAD" || strtoupper($this->httpRequestMethod) == "GET"))
        if (strtoupper($this->httpRequestMethod) == "HEAD" || strtoupper($this->httpRequestMethod) == "GET")
        {
            if($this->account) {
                $this->getApiStatistics()->incrementApiKeyRequestStats($this->account);
            }

            if(!Settings::isEnergySaveActive() && $this->visitor && $this->visitor->getReferrer()) {
                $this->getApiStatistics()->incrementReferrerRequestStats($this->visitor->getReferrer());
                $this->getApiStatistics()->incrementReferrerDeeplinkRequestStats($this->visitor->getReferrer()->getCurrentDeeplink());
            }
        }

        if ($this->callback) {

            $jsonOutputArray = array(
                "status" => $output['status'],
                "url" => $output['redirectUrl'] . (isset($output['newTargetUrl']) ? $output['newTargetUrl'] : "")
            );

            if($this->target->getCurrentImage()->getWidth()) {
                $jsonOutputArray['width'] = (int) $this->target->getCurrentImage()->getWidth();

                //TODO: If an image does not exist yet, no height is available. This needs to be fetched somewhere else.

                // set height on if width is set
                if($this->target->getCurrentImage()->getHeight()) {
                    $jsonOutputArray['height'] = (int) $this->target->getCurrentImage()->getHeight();
                }
            }

            $slimResponse->setContentType('application/json');
            $slimResponse->setOutput($this->callback . "(" .
                json_encode($jsonOutputArray) . ")");
        } else {
            $slimResponse->setRedirect(array($output['redirectUrl'] . (isset($output['newTargetUrl']) ? $output['newTargetUrl'] : ""), 307));
        }

        return $slimResponse;
    }


    // Cached Image

    public function loadAndValidateCachedImageParameters($cacheId)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $result = true;

        // cacheId


        if (!$cacheId || !is_string($cacheId) || !preg_match('/^[a-z0-9]{32}$/', $cacheId)) {
            $this->getLogger()->log(__METHOD__, "invalid cacheId", LOG_ERR);
            $result = false;
        } else {
            $this->cacheId = $cacheId;
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
        
        if($mode == "phantom") {

            //TODO: check all $targetData values

            $targetData = json_decode($data, true);
            //return $this->getLogger()->logEcho(__METHOD__, "HHIIIEER: " . print_r($targetData, true), LOG_ERR);
            $target = $this->getTargetModel()->getById($targetData['id']);

            if (!$target instanceof Target) {
                return $this->getLogger()->logEcho(__METHOD__, "invalid target", LOG_ERR);
            }
            
            if(!$targetData['image']) {
                return $this->getLogger()->logEcho(__METHOD__, "invalid image data", LOG_ERR);
            }
            
            $target->setMasterImage($targetData['image']);
            $target->setJavaScriptEnabled(true);
            $target->setSnipeDuration($targetData['snipeDuration']);
            $target->setWeapon('PhantomJS');
            
            if(array_key_exists('robotsAllowed', $targetData)) {
                $target->setRobotsAllowed(($targetData['robotsAllowed'] == 1));
                $target->setTsRobotsCheck(time());
            }
            
            $target->setMimeType($targetData['contentType']);
        }else {
            $this->getLogger()->log(__METHOD__, "size of base64 encoded target : " . strlen($data), LOG_DEBUG);

            $resultData_serialized = base64_decode($data, TRUE);

            $this->getLogger()->log(__METHOD__, "size of base64 decoded target : " . strlen($resultData_serialized), LOG_DEBUG);

            /** @var Target $target */
            $target = unserialize($resultData_serialized);
        }
        
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

        if(!$this->getRedis()->exists(Settings::getRedisKeyAgentLastSleepDurationPrefix() . $type))
        {
            $this->getRedis()->set(Settings::getRedisKeyAgentLastSleepDurationPrefix() . $type, 1);
        }
    }


    private function incrementAgentSleepDuration($type)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        if(intval($this->getRedis()->get(Settings::getRedisKeyAgentLastSleepDurationPrefix() . $type)) < Settings::getAgentMaxSleepDuration()) {
            $this->getRedis()->incr(Settings::getRedisKeyAgentLastSleepDurationPrefix() . $type);
        }
    }


    private function decrementAgentSleepDuration($type)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        if(intval($this->getRedis()->get(Settings::getRedisKeyAgentLastSleepDurationPrefix() . $type)) > 0) {
            $this->getRedis()->decr(Settings::getRedisKeyAgentLastSleepDurationPrefix() . $type);
        }
    }


    private function getCalculatedAgentSleepDuration($type)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $sleepDuration = mt_rand(3, Settings::getAgentMaxSleepDuration());

        $this->initAgentSleepDuration($type);

        $sleep = $this->getRedis()->get(Settings::getRedisKeyAgentLastSleepDurationPrefix() . $type);

        if($sleep) {
            $sleepDuration = intval($sleep);
        }

        $this->incrementAgentSleepDuration($type);

        $this->getLogger()->log(__METHOD__, "agent (" . $type . ") shall sleep for " . $sleepDuration . " seconds", LOG_DEBUG);

        //make sure that no negative value is returned
        return $sleepDuration >= 0 ? $sleepDuration : 0;
    }



    public function agentGetMasterJob($priority)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        if($priority == "phantom") {
            $target = $this->getTargetModel()->getNextMasterJob($priority);

            if (!$target instanceof Target) {
                $this->getLogger()->log(__METHOD__, "no target found", LOG_INFO);
                return json_encode(array(
                    'sleep' => $this->getCalculatedAgentSleepDuration('master_' . $priority)
                ));
            } else {
                $this->decrementAgentSleepDuration('master_' . $priority);
            }

            return json_encode(array(
                'id' => $target->getId(),
                'url' => $target->getUrl()
            ));
        }else {
            $target = $this->getTargetModel()->getNextMasterJob($priority);

            if (!$target instanceof Target) {
                $this->getLogger()->log(__METHOD__, "no target found", LOG_INFO);
                return $this->getCalculatedAgentSleepDuration('master_' . $priority);
            } else {
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
    }



    public function agentGetThumbnailJob($featuredEffects)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $jobData = $this->getImageModel()->getNextThumbnailJob($featuredEffects);

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

	    $redisTargetMasterImageKey = Settings::getRedisKeyTargetMasterImageData() . $target->getId();

	    if(!$this->getRedis()->exists($redisTargetMasterImageKey))
	    {
		    $this->getImageModel()->dequeue($image);
		    $this->getLogger()->log(__METHOD__, "missing redisTargetMasterImageKey for target " . $target->getId(), LOG_ERR);
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

        if($mode == "phantom") {
            $targetData = json_decode($data, true);
            $target = $this->getTargetModel()->getById($targetData['id']);

            if (!$target instanceof Target) {
                return $this->getLogger()->logEcho(__METHOD__, "invalid target", LOG_ERR);
            }

            $target->setJavaScriptEnabled(true);
            $target->setWeapon('PhantomJS');

            if(array_key_exists('error', $targetData)) {
                $target->setLastErrorMessage($targetData['error']);
            }else {
                $target->setLastErrorMessage(null);
            }

            if(array_key_exists('robotsAllowed', $targetData) && array_key_exists('tsRobotsCheck', $targetData)) {
                $target->setRobotsAllowed($targetData['robotsAllowed']);
                $target->setTsRobotsCheck($targetData['tsRobotsCheck']);
            }else {
                $target->setRobotsAllowed(null);
                $target->setTsRobotsCheck(null);
            }
        }else {
            $resultData_serialized = base64_decode($data, TRUE);
            $target = unserialize($resultData_serialized);
        }

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
			    $output['redirectUrl'] .= $this->account->getApiKey() . "/";
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
			    $output['redirectUrl'] .= $this->account->getApiKey() . "/";
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
                $output['redirectUrl'] .= $this->account->getApiKey() . "/";
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


    private function generateViolationOutput($width, $effect)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $output['status'] = "violation";
        $output['redirectUrl'] = $this->httpProtocol . "://" . Settings::getApiHost() . "/v3/thumbnail/";

        //TODO: may be unsafe to use the API key here
//        if ($this->account) {
//            $output['redirectUrl'] .= $this->account->getApiKey() . "/";
//        }

        $output['redirectUrl'].= $width . "/" . $effect . "/?";

        if ($this->forceDebug) {
            $output['redirectUrl'] .= "otnDebug&";
        }

        $output['redirectUrl'] .= "url=";
        $output['newTargetUrl'] = Settings::getFrontendImagesUrl() . Settings::getFrontendImagesPathViolation();

        return $output;
    }

    
    /**
     * @param mixed $forceDebug
     */
    public function setForceDebug($forceDebug)
    {
        $this->forceDebug = $forceDebug;
        $this->getLogger()->setForceDebug($forceDebug);
    }

    
    public function isForceDebug()
    {
        return $this->forceDebug;
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

    protected function getVisitorModel()
    {
        if(!$this->visitorModel instanceof VisitorModel) {
            $this->getLogger()->log(__METHOD__, "init new VisitorModel instance", LOG_DEBUG);
            $this->visitorModel = new VisitorModel($this->getMongoDB(), $this->getLogger());
        }

        return $this->visitorModel;
    }


    protected function getRequestController()
    {
        if(!$this->requestController instanceof RequestController) {
            $this->getLogger()->log(__METHOD__, "init new RequestController instance", LOG_DEBUG);
            $this->requestController = new RequestController($this->getMongoDB(), $this->getLogger());
        }

        return $this->requestController;
    }
}
