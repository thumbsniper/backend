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

namespace ThumbSniper\common;


class Helpers
{
    public static function genRandomString($length = 1)
    {
        $characters = "0123456789abcdefghijklmnopqrstuvwxyz";

        $string = "";

        for ($p = 0; $p < $length; $p++) {
            $string .= $characters[mt_rand(0, strlen($characters) - 1)];
        }

        return $string;
    }


    public static function getVariancedValue($staticValue, $variance)
    {
        $range = intval($staticValue / 100 * $variance);
        $random = rand(-$range, $range);

        return $staticValue + $random;
    }


    public static function isValidMd5($md5 ='')
    {
        return preg_match('/^[a-f0-9]{32}$/', $md5);
    }


    public static function isDomainExists($domain)
    {
        $ascii = idn_to_ascii($domain);

        if($ascii && (checkdnsrr($ascii, 'A') || checkdnsrr($ascii, 'CNAME') || checkdnsrr($ascii, 'AAAA')))
        {
            return true;
        }
        else
        {
            return false;
        }
    }


    // use with care: very slow!
    public static function isIpAddressBlacklistedAsSpam($ipv4)
    {
        $blacklistDomains = array(
            "bl.spamcop.net",
            "list.dsbl.org",
            "sbl.spamhaus.org",
            'xbl.spamhaus.org'
        );

        if(!$ipv4) {
            throw new \Exception("no IP address given");
        }

        $ipReversed = implode('.', array_reverse(explode(".",$ipv4)));

        foreach($blacklistDomains as $blacklistDomain)
        {
            if(checkdnsrr($ipReversed . '.' . $blacklistDomain . '.', 'A'))
            {
                return true;
            }
        }

        return false;
    }


    public static function isPrivateIpAddress($ipv4)
    {
        if(!$ipv4)
        {
            throw new \Exception("no host given");
        }

        $privateAddresses = array (
            '10.0.0.0|10.255.255.255', // single class A network
            '172.16.0.0|172.31.255.255', // 16 contiguous class B network
            '192.168.0.0|192.168.255.255', // 256 contiguous class C network
            '169.254.0.0|169.254.255.255', // Link-local address also refered to as Automatic Private IP Addressing
            '127.0.0.0|127.255.255.255' // localhost
        );

        $longIp = ip2long($ipv4);

        if(!$longIp)
        {
            return false;
        }

        foreach($privateAddresses AS $privateAddress) {
            list ($start, $end) = explode('|', $privateAddress);

            // IF IS PRIVATE
            if ($longIp >= ip2long($start) && $longIp <= ip2long($end))
            {
                return true;
            }
        }

        return false;
    }


    public static function isIpAddress($host)
    {
        if(filter_var($host, FILTER_VALIDATE_IP))
        {
            return true;
        }else
        {
            return false;
        }
    }


	public static function getSaltedPasswordHash($password, $salt)
	{
		return hash('sha256', $salt . $password);
	}
}
