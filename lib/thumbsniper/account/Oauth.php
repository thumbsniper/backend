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


class Oauth
{
    private $id;
    private $provider;

    private $accessToken;
    private $accessTokenSecret;
    private $accessTokenType;
    private $accessTokenExpiry;
    private $refreshToken;
    private $screenName;

	private $password;

    private $tsAdded;
    private $tsLastUpdated;



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
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @param mixed $provider
     */
    public function setProvider($provider)
    {
        $this->provider = $provider;
    }

    /**
     * @return mixed
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @param mixed $accessToken
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @return mixed
     */
    public function getAccessTokenSecret()
    {
        return $this->accessTokenSecret;
    }

    /**
     * @param mixed $accessTokenSecret
     */
    public function setAccessTokenSecret($accessTokenSecret)
    {
        $this->accessTokenSecret = $accessTokenSecret;
    }

    /**
     * @return mixed
     */
    public function getAccessTokenType()
    {
        return $this->accessTokenType;
    }

    /**
     * @param mixed $accessTokenType
     */
    public function setAccessTokenType($accessTokenType)
    {
        $this->accessTokenType = $accessTokenType;
    }

    /**
     * @return mixed
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * @param mixed $refreshToken
     */
    public function setRefreshToken($refreshToken)
    {
        $this->refreshToken = $refreshToken;
    }

    /**
     * @return mixed
     */
    public function getAccessTokenExpiry()
    {
        return $this->accessTokenExpiry;
    }

    /**
     * @param mixed $accessTokenExpiry
     */
    public function setAccessTokenExpiry($accessTokenExpiry)
    {
        $this->accessTokenExpiry = $accessTokenExpiry;
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
    public function getTsLastUpdated()
    {
        return $this->tsLastUpdated;
    }

    /**
     * @param mixed $tsLastUpdated
     */
    public function setTsLastUpdated($tsLastUpdated)
    {
        $this->tsLastUpdated = $tsLastUpdated;
    }

    /**
     * @return mixed
     */
    public function getScreenName()
    {
        return $this->screenName;
    }

    /**
     * @param mixed $screenName
     */
    public function setScreenName($screenName)
    {
        $this->screenName = $screenName;
    }

	/**
	 * @return mixed
	 */
	public function getPassword()
	{
		return $this->password;
	}

	/**
	 * @param mixed $password
	 */
	public function setPassword($password)
	{
		$this->password = $password;
	}

}
