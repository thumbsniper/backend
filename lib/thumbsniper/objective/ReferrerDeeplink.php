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


class ReferrerDeeplink
{
    private $id;
    private $url;
    private $tsAdded;
    private $tsLastSeen;
    private $numRequests;



    function __construct()
    {
    } // function



    static function cmp_id($a, $b)
    {
        /**
         * @var ReferrerDeeplink $a
         * @var ReferrerDeeplink $b
         */

        if ($a->id == $b->id)
        {
            return 0;
        }
        return ($a->id < $b->id) ? -1 : 1;
    }



    static function cmp_url($a, $b)
    {
        /**
         * @var ReferrerDeeplink $a
         * @var ReferrerDeeplink $b
         */

        $al = strtolower($a->url);
        $bl = strtolower($b->url);

        if ($al == $bl) {
            return 0;
        }
        return ($al > $bl) ? +1 : -1;
    }


    public function getId()
    {
        return $this->id;
    }


    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
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
    public function getNumRequests()
    {
        return $this->numRequests;
    }

    /**
     * @param mixed $numRequests
     */
    public function setNumRequests($numRequests)
    {
        $this->numRequests = $numRequests;
    }

    /**
     * @return mixed
     */
    public function getTsLastSeen()
    {
        return $this->tsLastSeen;
    }

    /**
     * @param mixed $tsLastSeen
     */
    public function setTsLastSeen($tsLastSeen)
    {
        $this->tsLastSeen = $tsLastSeen;
    }
}
