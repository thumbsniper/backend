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

namespace ThumbSniper\account;


class Account
{
    private $id;
    private $firstName;
    private $lastName;
    private $email;
    private $emailVerified;

    private $tsAdded;
    private $active;
    private $admin;
    private $actAsAdmin;

    private $apiKey;
    private $apiKeyType;
    private $apiKeyTsAdded;
    private $apiKeyTsExpire;
    private $apiKeyActive;
    private $maxDailyRequests;
    private $domainVerificationKey;
    private $whitelistActive;

    private $requestStats;


    public function __construct()
    {
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param mixed $firstName
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * @return mixed
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param mixed $lastName
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function isEmailVerified()
    {
        return $this->emailVerified;
    }

    /**
     * @param mixed $emailVerified
     */
    public function setEmailVerified($emailVerified)
    {
        $this->emailVerified = $emailVerified;
    }

    /**
     * @return mixed
     */
    public function getTsAdded()
    {
        return $this->tsAdded;
    }

    /**
     * @param mixed $tsAdded
     */
    public function setTsAdded($tsAdded)
    {
        $this->tsAdded = $tsAdded;
    }

    /**
     * @return mixed
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param mixed $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * @return mixed
     */
    public function isAdmin()
    {
        return $this->admin;
    }

    /**
     * @param mixed $admin
     */
    public function setAdmin($admin)
    {
        $this->admin = $admin;
    }

    /**
     * @return mixed
     */
    public function isActAsAdmin()
    {
        return $this->actAsAdmin;
    }

    /**
     * @param mixed $actAsAdmin
     */
    public function setActAsAdmin($actAsAdmin)
    {
        $this->actAsAdmin = $actAsAdmin;
    }

    /**
     * @return mixed
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @param mixed $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @return mixed
     */
    public function getApiKeyType()
    {
        return $this->apiKeyType;
    }

    /**
     * @param mixed $apiKeyType
     */
    public function setApiKeyType($apiKeyType)
    {
        $this->apiKeyType = $apiKeyType;
    }

    /**
     * @return mixed
     */
    public function getApiKeyTsAdded()
    {
        return $this->apiKeyTsAdded;
    }

    /**
     * @param $apiKeyTsAdded
     */
    public function setApiKeyTsAdded($apiKeyTsAdded)
    {
        $this->apiKeyTsAdded = $apiKeyTsAdded;
    }

    /**
     * @return mixed
     */
    public function getApiKeyTsExpire()
    {
        return $this->apiKeyTsExpire;
    }

    /**
     * @param $apiKeyTsExpire
     */
    public function setApiKeyTsExpire($apiKeyTsExpire)
    {
        $this->apiKeyTsExpire = $apiKeyTsExpire;
    }

    /**
     * @return mixed
     */
    public function isApiKeyActive()
    {
        return $this->apiKeyActive;
    }

    /**
     * @param mixed $apiKeyActive
     */
    public function setApiKeyActive($apiKeyActive)
    {
        $this->apiKeyActive = $apiKeyActive;
    }

    /**
     * @return mixed
     */
    public function getMaxDailyRequests()
    {
        return $this->maxDailyRequests;
    }

    /**
     * @param mixed $maxDailyRequests
     */
    public function setMaxDailyRequests($maxDailyRequests)
    {
        $this->maxDailyRequests = $maxDailyRequests;
    }

    /**
     * @return mixed
     */
    public function getDomainVerificationKey()
    {
        return $this->domainVerificationKey;
    }

    /**
     * @param mixed $domainVerificationKey
     */
    public function setDomainVerificationKey($domainVerificationKey)
    {
        $this->domainVerificationKey = $domainVerificationKey;
    }

    /**
     * @return mixed
     */
    public function isWhitelistActive()
    {
        return $this->whitelistActive;
    }

    /**
     * @param mixed $whitelistActive
     */
    public function setWhitelistActive($whitelistActive)
    {
        $this->whitelistActive = $whitelistActive;
    }

    /**
     * @return mixed
     */
    public function getRequestStats()
    {
        return $this->requestStats;
    }

    /**
     * @param mixed $requestStats
     */
    public function setRequestStats($requestStats)
    {
        $this->requestStats = $requestStats;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->firstName . " " . $this->lastName;
    }

}
