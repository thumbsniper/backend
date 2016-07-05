<?php

/**
 *  Copyright (C) 2016  Thomas Schulte <thomas@cupracer.de>
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace ThumbSniper\objective;


use Aws\CloudFront\Exception\Exception;
use MongoDB;
use Predis\Client;
use ThumbSniper\account\Account;
use ThumbSniper\account\AccountModel;
use ThumbSniper\api\ApiStatistics;
use ThumbSniper\common\Helpers;
use ThumbSniper\common\Logger;
use ThumbSniper\common\Settings;


class RequestController
{
    /** @var Logger */
    protected $logger;

    /** @var MongoDB */
    protected $mongoDB;

    /** @var Client */
    private $redis;

    /** @var Request */
    protected $request;

    /** @var UserAgentModel */
    protected $userAgentModel;

    /** @var AccountModel */
    protected $accountModel;
    
    /** @var ReferrerModel */
    protected $referrerModel;

    /** @var ApiStatistics */
    protected $apiStatistics;

    /** @var VisitorModel */
    protected $visitorModel;



    function __construct(MongoDB $mongoDB, Client $redis, Logger $logger)
    {
        $logger->log(__METHOD__, NULL, LOG_DEBUG);
        
        $this->mongoDB = $mongoDB;
        $this->redis = $redis;
        $this->logger = $logger;
        $this->request = new Request();
    }
    
    
    protected function init($apiAction, $callback, $waitimg, $userAgentStr, $apiKey, $referrerUrl)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $this->request->setHttpProtocol($this->getHttpProtocol());
        $this->request->setHttpMethod($this->getHttpMethod());

        $this->request->setApiAction($this->getValidatedApiAction($apiAction));
        $this->request->setCallback($this->getValidatedCallback($callback));
        $this->request->setWaitImageUrl($this->getValidatedWaitImageUrl($waitimg));

        $this->request->setUserAgent($this->getValidatedUserAgent($userAgentStr));
        $this->request->setAccount($this->getValidatedAccountByApiKey($apiKey));
        $this->request->setReferrer($this->getValidatedReferrer($referrerUrl));
    }


    /**
     * @return Request
     */
    public function getRequest()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);
        return $this->request;
    }


    /**
     * @return string
     */
    protected function getHttpProtocol()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);
        return Helpers::isSSL() ? "https" : "http";
    }


    /**
     * @return string
     */
    protected function getHttpMethod()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);
        return $_SERVER['REQUEST_METHOD'];
    }


    /**
     * @param $action
     * @return bool
     */
    protected function getValidatedApiAction($action)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        if(!$action || !is_string($action) || !in_array($action, Settings::getApiValidActions())) {
            $this->logger->log(__METHOD__, "invalid action: " . $action, LOG_ERR);
            return null;
        } else {
            return $action;
        }
    }


    /**
     * @param $waitimg
     * @return bool
     */
    protected function getValidatedWaitImageUrl($waitimg)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        if(!$waitimg) {
            $this->logger->log(__METHOD__, "not using waitimg", LOG_DEBUG);
            return null;
        }
        
        if(!is_string($waitimg) || !filter_var($waitimg, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED)) {
            $this->logger->log(__METHOD__, "invalid waitimg: " . $waitimg, LOG_WARNING);
            return null;
        } else {
            $this->logger->log(__METHOD__, "found waitimg: " . $waitimg, LOG_INFO);
            return $waitimg;
        }
    }


    public function getValidatedVisitor($address, $userAgentStr, $referrerUrl, $apiKey)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        if(!Settings::isStoreVisitors() || Settings::isEnergySaveActive()) {
            $this->logger->log(__METHOD__, "not storing visitor", LOG_DEBUG);
            return true;
        }

        if(!$address) {
            $this->logger->log(__METHOD__, "invalid address", LOG_ERR);
            return false;
        }

        $addressType = Helpers::getIpProtocol($address);

        if(!$addressType) {
            $this->logger->log(__METHOD__, "invalid addressType", LOG_ERR);
            return false;
        }


        $visitor = $this->getVisitorModel()->getOrCreateByAddress($address, $addressType, $this->request->getUserAgent(), $this->request->getReferrer());

        if (!$visitor instanceof Visitor) {
            $this->logger->log(__METHOD__, "invalid visitor: " . $address, LOG_WARNING);
            return null;
        }

        //$this->getVisitorModel()->addTargetMapping($this->userAgent, $this->target);
        //$this->getTargetModel()->addUserAgentMapping($this->target, $this->userAgent);

        $this->getApiStatistics()->updateVisitorLastSeenStats($visitor->getId());
        //$this->getApiStatistics()->incrementVisitorRequestStats($this->visitor);

        return $visitor;
    }


    /**
     * @param $userAgentStr
     * @return null|UserAgent
     */
    protected function getValidatedUserAgent($userAgentStr)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        if(!Settings::isStoreUserAgents() || Settings::isEnergySaveActive()) {
            $this->logger->log(__METHOD__, "not storing userAgent", LOG_DEBUG);
            return null;
        }

        if(!$userAgentStr) {
            $this->logger->log(__METHOD__, "invalid userAgentStr", LOG_ERR);
            return null;
        }

        $ua = $this->getUserAgentModel()->getOrCreateByDescription($userAgentStr);

        if (!$ua instanceof UserAgent) {
            $this->logger->log(__METHOD__, "invalid user agent: " . $userAgentStr, LOG_WARNING);
            return null;
        }else {
            //FIXME: re-enable apiStatistics for userAgentLastSeenStats
            //$this->getApiStatistics()->updateUserAgentLastSeenStats($ua->getId());
            //$this->getApiStatistics()->incrementUserAgentRequestStats($this->userAgent);
            return $ua;
        }
    }


    protected function getValidatedReferrer($referrerUrl)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        if(Settings::isEnergySaveActive()) {
            $this->logger->log(__METHOD__, "not storing referrer", LOG_DEBUG);
            return null;
        }

        if(!$referrerUrl) {
            $this->logger->log(__METHOD__, "invalid referrerUrl", LOG_ERR);
            return null;
        }

        $referrerUrl = $this->getValidatedUrl($referrerUrl, true, true);

        if($referrerUrl == null) {
            $this->logger->log(__METHOD__, "invalid referrerUrl: " . $referrerUrl, LOG_WARNING);
            return null;
        }

        if ($this->request->getAccount() instanceof Account) {
            $ref = $this->getReferrerModel()->getOrCreateByUrl($referrerUrl, $this->request->getAccount()->getId());
        } else {
            $ref = $this->getReferrerModel()->getOrCreateByUrl($referrerUrl);
        }

        if (!$ref instanceof Referrer) {
            $this->logger->log(__METHOD__, "invalid referrer: " . $referrerUrl, LOG_WARNING);
            return null;
        }
        
        $referrer = $ref;

        if($referrer->getAccountId() && !$this->request->getAccount()) {
            $account = $this->getAccountModel()->getById($referrer->getAccountId());

            // only load account by referrer if whitelist is enabled in account settings
            if ($account instanceof Account) {
                $this->logger->log(__METHOD__, "found account " . $account->getId() . " by its referrer", LOG_DEBUG);

                if ($account->isWhitelistActive()) {
                    $this->logger->log(__METHOD__, "whitelist active for account " . $account->getId(), LOG_DEBUG);
                    $this->request->setAccount($account);
                    $this->getReferrerModel()->checkDomainVerificationKeyExpired($referrer, $this->request->getAccount());
                } else {
                    $this->logger->log(__METHOD__, "ignoring account " . $account->getId(), LOG_DEBUG);
                }
            }
        }

        //FIXME: addTargetMapping somewhere else
        //$this->getReferrerModel()->addTargetMapping($referrer, $this->target);

        //$this->getTargetModel()->addReferrerMapping($this->target, $referrer);

        $this->getApiStatistics()->updateReferrerLastSeenStats($referrer->getId());

        return $referrer;
    }


    /**
     * @param $url
     * @param bool $addQuery
     * @param bool $addFragment
     * @return bool|mixed|string
     * @throws \Exception
     */
    protected function getValidatedUrl($url, $addQuery = false, $addFragment = false)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        if (!$url || !is_string($url))
        {
            $this->logger->log(__METHOD__, "invalid URL (not set or not string)", LOG_ERR);
            return false;
        }

        //disable Retina images
        $url = str_replace('@2x', '', $url);

        $urlparts = parse_url($url);

        if(!is_array($urlparts)) {
            $this->logger->log(__METHOD__, "invalid URL (invalid URL parts)", LOG_ERR);
            return false;
        }elseif(!isset($urlparts['scheme'])) {
            $this->logger->log(__METHOD__, "invalid URL (invalid URL parts - invalid URL scheme)", LOG_ERR);
            return false;
        }elseif(!isset($urlparts['host']))
        {
            $this->logger->log(__METHOD__, "invalid URL (invalid URL parts - host missing)", LOG_ERR);
            return false;
        }

        try {
            if (Helpers::isPrivateIpAddress($urlparts['host'])) {
                $this->logger->log(__METHOD__, "invalid URL (host " . $urlparts['host'] . " is a private IP address)", LOG_ERR);
                return false;
            }
        }catch (Exception $e)
        {
            $this->logger->log(__METHOD__, "Exception while checking for private IP address: " . $e, LOG_ERR);
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
            $this->logger->log(__METHOD__, "invalid URL (filter): " . $url, LOG_ERR);
            return false;
        }

        return $url;
    }


    protected function getValidatedAccountByApiKey($apiKey)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        if (!$apiKey) {
            $this->logger->log(__METHOD__, "no apiKey used", LOG_DEBUG);
            return null;
        }

        if(!is_string($apiKey) || !strlen($apiKey) == 32) {
            $this->logger->log(__METHOD__, "invalid apiKey: " . strval($apiKey), LOG_ERR);
            return null;
        }

        $account = $this->getAccountModel()->getByApiKey($apiKey);

        if (!$account instanceof Account) {
            $this->logger->log(__METHOD__, "invalid account", LOG_WARNING);
            return null;
        }else {
            //TODO: check if active and not expired
            
            //TODO: move to somewhere else:
            $this->getAccountModel()->addToActiveAccountsDailyStats($account->getId());

            return $account;
        }
    }


    protected function getValidatedCallback($callback)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        if (!$callback) {
            $this->logger->log(__METHOD__, "no callback used", LOG_DEBUG);
            return null;
        }

        if(!is_string($callback) || !preg_match('/^jQuery[0-9]+_[0-9]+$|^jsonp[0-9]+$/', $callback)) {
            $this->logger->log(__METHOD__, "invalid callback", LOG_WARNING);
            return null;
        }else {
            $this->logger->log(__METHOD__, "set callback to: " . $callback, LOG_INFO);
            return $callback;
        }
    }


    protected function getUserAgentModel()
    {
        if(!$this->userAgentModel instanceof UserAgentModel) {
            $this->logger->log(__METHOD__, "init new UserAgentModel instance", LOG_DEBUG);
            $this->userAgentModel = new UserAgentModel($this->mongoDB, $this->logger);
        }

        return $this->userAgentModel;
    }


    protected function getAccountModel()
    {
        if(!$this->accountModel instanceof AccountModel) {
            $this->logger->log(__METHOD__, "init new AccountModel instance", LOG_DEBUG);
            $this->accountModel = new AccountModel($this->mongoDB, $this->logger);
        }

        return $this->accountModel;
    }


    protected function getReferrerModel()
    {
        if(!$this->referrerModel instanceof ReferrerModel) {
            $this->logger->log(__METHOD__, "init new ReferrerModel instance", LOG_DEBUG);
            $this->referrerModel = new ReferrerModel($this->mongoDB, $this->logger);
        }

        return $this->referrerModel;
    }

    protected function getApiStatistics()
    {
        if(!$this->apiStatistics instanceof ApiStatistics) {
            $this->logger->log(__METHOD__, "init new ApiStatistics instance", LOG_DEBUG);
            $this->apiStatistics = new ApiStatistics($this->mongoDB, $this->redis, $this->logger);
        }

        return $this->apiStatistics;
    }


    protected function getVisitorModel()
    {
        if(!$this->visitorModel instanceof VisitorModel) {
            $this->logger->log(__METHOD__, "init new VisitorModel instance", LOG_DEBUG);
            $this->visitorModel = new VisitorModel($this->mongoDB, $this->logger);
        }

        return $this->visitorModel;
    }
}