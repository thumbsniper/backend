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


use ThumbSniper\account\Account;
use ThumbSniper\shared\Target;


class Request
{
    /** @var Account */
    protected $account;

    /** @var Target */
    protected $target;

    /** @var Visitor */
    protected $visitor;

    /** @var UserAgent */
    protected $userAgent;
    
    /** @var string */
    protected $httpProtocol;
    
    /** @var string */
    protected $httpMethod;
    
    /** @var string */
    protected $apiAction;

    /** @var string */
    protected $waitImageUrl;
        
    
    public function __construct()
    {
    }

    /**
     * @return Account
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @param Account $account
     */
    public function setAccount($account)
    {
        $this->account = $account;
    }

    /**
     * @return Target
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @param Target $target
     */
    public function setTarget($target)
    {
        $this->target = $target;
    }

    /**
     * @return Visitor
     */
    public function getVisitor()
    {
        return $this->visitor;
    }

    /**
     * @param Visitor $visitor
     */
    public function setVisitor($visitor)
    {
        $this->visitor = $visitor;
    }

    /**
     * @return string
     */
    public function getHttpProtocol()
    {
        return $this->httpProtocol;
    }

    /**
     * @param string $httpProtocol
     */
    public function setHttpProtocol($httpProtocol)
    {
        $this->httpProtocol = $httpProtocol;
    }

    /**
     * @return string
     */
    public function getHttpMethod()
    {
        return $this->httpMethod;
    }

    /**
     * @param string $httpMethod
     */
    public function setHttpMethod($httpMethod)
    {
        $this->httpMethod = $httpMethod;
    }

    /**
     * @return string
     */
    public function getApiAction()
    {
        return $this->apiAction;
    }

    /**
     * @param string $apiAction
     */
    public function setApiAction($apiAction)
    {
        $this->apiAction = $apiAction;
    }

    /**
     * @return string
     */
    public function getWaitImageUrl()
    {
        return $this->waitImageUrl;
    }

    /**
     * @param string $waitImageUrl
     */
    public function setWaitImageUrl($waitImageUrl)
    {
        $this->waitImageUrl = $waitImageUrl;
    }

    /**
     * @return UserAgent
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * @param UserAgent $userAgent
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;
    }
}