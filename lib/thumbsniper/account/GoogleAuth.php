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

require_once('vendor/autoload.php');



use ThumbSniper\common\Logger;
use ThumbSniper\common\Settings;

class GoogleAuth
{
    private $logger;

    private $client_id;
    private $client_secret;
    private $home_url;
    private $auth_url;

    private $authCode;


    function __construct(Logger $logger, $client_id, $client_secret, $home_url, $auth_url)
    {
        $this->logger = $logger;
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->home_url = $home_url;
        $this->auth_url = $auth_url;
    }


    private function formatOAuthReq($force)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $arr['response_type'] = 'code';
        $arr['client_id'] = $this->client_id;
        $arr['redirect_uri'] = $this->auth_url;
        $arr['access_type'] = 'offline';

        if($force) {
            $arr['approval_prompt'] = 'force';
        }

        $arr['scope'] =
            "https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile";

        $uri = 'https://accounts.google.com/o/oauth2/auth?';

        foreach ($arr as $key => $value) {
            $uri .= $key . '=' . $value . '&';
        }

        return $uri;
        //return rtrim($uri, "&");
    }


    private function do_post($url, $fields)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, Settings::getUserAgent());

        if(Settings::getHttpProxyUrl())
        {
            curl_setopt($ch, CURLOPT_PROXY, Settings::getHttpProxyUrl());
        }

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }


    private function get_url_param($url, $name)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        parse_str(parse_url($url, PHP_URL_QUERY), $params);
        return isset($params[$name]) ? $params[$name] : null;
    }


    private function get_auth_token()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        if (!$this->authCode) {
            return false;
        }

        $url = 'https://accounts.google.com/o/oauth2/token';

        $fields = array(
            'code' => $this->authCode,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'redirect_uri' => $this->auth_url,
            'grant_type' => 'authorization_code'
        );

        $response = $this->do_post($url, $fields);
        return $response;
    }


    public function isError()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        return $this->get_url_param($_SERVER['REQUEST_URI'], 'error') != NULL;
    }


    public function getError()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        return $this->get_url_param($_SERVER['REQUEST_URI'], 'error');
    }


    public function revokeToken()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        //TODO: implement revokeToken for GoogleAuth
    }


    public function checkAuthCode($force, $skipRequestUri = false)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        // does the code parameter exist?
        $authcode = $this->get_url_param($_SERVER['REQUEST_URI'], 'code');

        if (!$skipRequestUri && $authcode != NULL) {
            $this->authCode = $authcode;
        } else {
            header('Location: ' . $this->formatOAuthReq($force));
            exit; // the redirect will come back to this page and $code will have a value
        }
    }


    private function getTokenData()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $oauth = NULL;

        // now exchange Authorization code for access token and refresh token
        $token_response = $this->get_auth_token();
        $json_obj = json_decode($token_response);

        //$this->logger->log(__METHOD__, "google token data: " . print_r($json_obj, true), LOG_ERR);

        if ($json_obj) {
            $oauth = new Oauth();
            $oauth->setAccessToken($json_obj->access_token);
            $oauth->setAccessTokenType($json_obj->token_type);

            $oauth->setAccessTokenExpiry(time() + $json_obj->expires_in);

            if(isset($json_obj->refresh_token)) {
                $oauth->setRefreshToken($json_obj->refresh_token);
            }
        }

        return $oauth;
    }


    public function getOauth()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $oauth = $this->getTokenData();

        if (!$oauth instanceof Oauth) {
            return false;
        }

        $url = 'https://www.googleapis.com/oauth2/v1/userinfo?alt=json&access_token=' . $oauth->getAccessToken();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, Settings::getUserAgent());

        if(Settings::getHttpProxyUrl())
        {
            curl_setopt($ch, CURLOPT_PROXY, Settings::getHttpProxyUrl());
        }

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        if (isset($data['error'])) {

            $this->logger->log(__METHOD__, print_r($data, true), LOG_ERR);

            return false;
        }

        $oauth->setId($data['id']);
        $oauth->setProvider('google');

        return $oauth;
    }
}
