<?php

/*
 * Copyright (C) 2016  Thomas Schulte <thomas@cupracer.de>
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

use MongoTimestamp;

class Visitor
{
    /** @var int */
    private $id;
    /** @var string */
    private $address;
    /** @var string */
    private $addressType;
    /** @var MongoTimestamp */
    private $tsAdded;
    /** @var MongoTimestamp */
    private $tsLastSeen;
    /** @var MongoTimestamp */
    private $tsLastUpdated;
    /** @var boolean */
    private $blacklisted;

    /** @var string */
    private $geoContinentCode;
    /** @var string */
    private $geoCountryCode;
    /** @var string */
    private $geoCountryCode3;
    /** @var string */
    private $geoCountryName;
    /** @var string */
    private $geoRegion;
    /** @var string */
    private $geoCity;
    /** @var string */
    private $geoPostalCode;
    /** @var string */
    private $geoLatitude;
    /** @var string */
    private $geoLongitude;
    /** @var int */
    private $geoDmaCode;
    /** @var int */
    private $geoAreaCode;


    function __construct()
    {
    } // function



    static function cmp_id($a, $b)
    {
        /**
         * @var Visitor $a
         * @var Visitor $b
         */

        if ($a->id == $b->id)
        {
            return 0;
        }
        return ($a->id < $b->id) ? -1 : 1;
    }



    static function cmp_address($a, $b)
    {
        /**
         * @var Visitor $a
         * @var Visitor $b
         */

        $al = strtolower($a->address);
        $bl = strtolower($b->address);

        if ($al == $bl) {
            return 0;
        }
        return ($al > $bl) ? +1 : -1;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param string $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * @return string
     */
    public function getAddressType()
    {
        return $this->addressType;
    }

    /**
     * @param string $addressType
     */
    public function setAddressType($addressType)
    {
        $this->addressType = $addressType;
    }

    /**
     * @return MongoTimestamp
     */
    public function getTsAdded()
    {
        return $this->tsAdded;
    }

    /**
     * @param MongoTimestamp $tsAdded
     */
    public function setTsAdded($tsAdded)
    {
        $this->tsAdded = $tsAdded;
    }

    /**
     * @return MongoTimestamp
     */
    public function getTsLastSeen()
    {
        return $this->tsLastSeen;
    }

    /**
     * @param MongoTimestamp $tsLastSeen
     */
    public function setTsLastSeen($tsLastSeen)
    {
        $this->tsLastSeen = $tsLastSeen;
    }

    /**
     * @return MongoTimestamp
     */
    public function getTsLastUpdated()
    {
        return $this->tsLastUpdated;
    }

    /**
     * @param MongoTimestamp $tsLastUpdated
     */
    public function setTsLastUpdated($tsLastUpdated)
    {
        $this->tsLastUpdated = $tsLastUpdated;
    }

    /**
     * @return boolean
     */
    public function isBlacklisted()
    {
        return $this->blacklisted;
    }

    /**
     * @param boolean $blacklisted
     */
    public function setBlacklisted($blacklisted)
    {
        $this->blacklisted = $blacklisted;
    }

    /**
     * @return string
     */
    public function getGeoContinentCode()
    {
        return $this->geoContinentCode;
    }

    /**
     * @param string $geoContinentCode
     */
    public function setGeoContinentCode($geoContinentCode)
    {
        $this->geoContinentCode = $geoContinentCode;
    }

    /**
     * @return string
     */
    public function getGeoCountryCode()
    {
        return $this->geoCountryCode;
    }

    /**
     * @param string $geoCountryCode
     */
    public function setGeoCountryCode($geoCountryCode)
    {
        $this->geoCountryCode = $geoCountryCode;
    }

    /**
     * @return string
     */
    public function getGeoCountryCode3()
    {
        return $this->geoCountryCode3;
    }

    /**
     * @param string $geoCountryCode3
     */
    public function setGeoCountryCode3($geoCountryCode3)
    {
        $this->geoCountryCode3 = $geoCountryCode3;
    }

    /**
     * @return string
     */
    public function getGeoCountryName()
    {
        return $this->geoCountryName;
    }

    /**
     * @param string $geoCountryName
     */
    public function setGeoCountryName($geoCountryName)
    {
        $this->geoCountryName = $geoCountryName;
    }

    /**
     * @return string
     */
    public function getGeoRegion()
    {
        return $this->geoRegion;
    }

    /**
     * @param string $geoRegion
     */
    public function setGeoRegion($geoRegion)
    {
        $this->geoRegion = $geoRegion;
    }

    /**
     * @return string
     */
    public function getGeoCity()
    {
        return $this->geoCity;
    }

    /**
     * @param string $geoCity
     */
    public function setGeoCity($geoCity)
    {
        $this->geoCity = $geoCity;
    }

    /**
     * @return string
     */
    public function getGeoPostalCode()
    {
        return $this->geoPostalCode;
    }

    /**
     * @param string $geoPostalCode
     */
    public function setGeoPostalCode($geoPostalCode)
    {
        $this->geoPostalCode = $geoPostalCode;
    }

    /**
     * @return string
     */
    public function getGeoLatitude()
    {
        return $this->geoLatitude;
    }

    /**
     * @param string $geoLatitude
     */
    public function setGeoLatitude($geoLatitude)
    {
        $this->geoLatitude = $geoLatitude;
    }

    /**
     * @return string
     */
    public function getGeoLongitude()
    {
        return $this->geoLongitude;
    }

    /**
     * @param string $geoLongitude
     */
    public function setGeoLongitude($geoLongitude)
    {
        $this->geoLongitude = $geoLongitude;
    }

    /**
     * @return int
     */
    public function getGeoDmaCode()
    {
        return $this->geoDmaCode;
    }

    /**
     * @param int $geoDmaCode
     */
    public function setGeoDmaCode($geoDmaCode)
    {
        $this->geoDmaCode = $geoDmaCode;
    }

    /**
     * @return int
     */
    public function getGeoAreaCode()
    {
        return $this->geoAreaCode;
    }

    /**
     * @param int $geoAreaCode
     */
    public function setGeoAreaCode($geoAreaCode)
    {
        $this->geoAreaCode = $geoAreaCode;
    }
}
