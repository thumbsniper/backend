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

namespace ThumbSniper\frontend;

use Abraham\TwitterOAuth\TwitterOAuth;
use ThumbSniper\account\Oauth;
use ThumbSniper\client\ThumbSniperClient;
use ThumbSniper\common\Helpers;
use ThumbSniper\common\Settings;
use ThumbSniper\api\ApiV3;
use ThumbSniper\account\Account;
use ThumbSniper\account\GoogleAuth;
use ThumbSniper\objective\TargetHostBlacklisted;
use ThumbSniper\objective\UserAgent;
use ThumbSniper\shared\Image;
use ThumbSniper\objective\Referrer;
use ThumbSniper\shared\Target;
use ThumbSniper\objective\ReferrerDeeplink;
use smarty;



class Panel extends ApiV3
{
    private $smarty;

    /** @var  Oauth */
    private $oauth;


    public function __construct($forceDebug, $smartyTemplateDir, $smartyCompileDir, $smartyConfigDir, $smartyCacheDir)
    {
        parent::__construct($forceDebug);

        $this->smarty = new \Smarty();
        $this->smarty->setTemplateDir($smartyTemplateDir);
        $this->smarty->setCompileDir($smartyCompileDir);
        $this->smarty->setConfigDir($smartyConfigDir);
        $this->smarty->setCacheDir($smartyCacheDir);

        //$this->smarty->debugging = true;

        $this->setGlobals();
    }



    private function setGlobals()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $site['title'] = Settings::getPanelTitle();
        $this->smarty->assign('site', $site);
        $this->smarty->assign('webUrl', Settings::getWebUrl());

        if(isset($_GET['logout']))
        {
            $this->logout();
        }

        if(isset($_SESSION['oauth']))
        {
            $oauth = $this->getOauthModel()->getById($_SESSION['oauth']);

            if($oauth instanceof Oauth)
            {
                $this->oauth = $oauth;
                $this->smarty->assignByRef('oauth', $this->oauth);
            }
        }

        if (isset($_SESSION['account'])) {
            $this->account = $this->getAccountModel()->getById($_SESSION['account']);
        }

        if($this->account instanceof Account)
        {
            $this->getAccountModel()->addToActiveAccountsDailyStats($this->account->getId());

            // Toggle AdminMode
            if (isset($_GET['admin']) && isset($this->account)) {
                if ($this->account->isAdmin()) {
                    if ($_GET['admin']) {
                        $_SESSION['actAsAdmin'] = true;
                    } else {
                        $_SESSION['actAsAdmin'] = false;
                    }
                }

                //TODO: wozu war das gut?
                //header("Location: " . Settings::getPanelUrl());
                //exit;
            }

            // get current AdminMode status
            if (isset($_SESSION['actAsAdmin'])) {
                $this->account->setActAsAdmin($_SESSION['actAsAdmin']);
            }

            $this->smarty->assignByRef('account', $this->account);
        }
    }



    private function login($oauthData)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $this->getLogger()->log(__METHOD__, "oauth data: " . print_r($oauthData, true), LOG_DEBUG);

        $oauth = $this->getOauthModel()->getById($oauthData['id']);

        if(!$oauth instanceof Oauth)
        {
            //TODO: show frontend error message?
            $this->getLogger()->log(__METHOD__, "invalid oauthId " . $oauthData['id'], LOG_ERR);
            return false;
        }

        $_SESSION['oauth'] = $oauthData['id'];
        $accountId = $this->getOauthModel()->getAccountIdByOauthIdAndProvider($oauth->getId(), $oauth->getProvider());

        if (!isset($_SESSION['account']) && $accountId) {
            $account = $this->getAccountModel()->getById($accountId);

            if($account->getEmail()) {
                $_SESSION['account'] = $account->getId();
            }
        }

        //save updated tokendata
        $this->getOauthModel()->saveOauth($accountId, $oauthData);

        // check Oauth connections

        $profiles = $this->getOauthModel()->getAllProfiles($accountId);

        /** @var Oauth $profile */
        foreach($profiles as $profile)
        {
            if($profile->getId() == $oauthData['id'])
            {
                // skip currently used Oauth
                continue;
            }

            switch($profile->getProvider())
            {
                case "google":
                    $this->verifyGoogleOauth($profile);
                    break;

                case "twitter":
                    $this->verifyTwitterOauth($profile);
                    break;
            }
        }

        //TODO: return true hier ok?
        return true;
    }



    private function logout()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        session_destroy();

        header('Location: /'); //redirect user back to page
        exit;
    }


    public function tryGoogleAuth()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        if(!Settings::isGoogleAuthEnabled())
        {
            //TODO: show error?
            header("Location: " . Settings::getPanelUrl());
            exit;
        }

        $googleAuth = new GoogleAuth(
            $this->getLogger(),
            Settings::getGoogleClientId(),
            Settings::getGoogleClientSecret(),
            Settings::getPanelUrl(),
            Settings::getGoogleAuthUrl());

        if (isset($_REQUEST['logout'])) {
            $this->logout();
        }

        if ($googleAuth->isError()) {
            header("Location: " . Settings::getPanelUrl());
            exit;
        }

        if($this->isAccount()) {
            //force on existing accounts to get new refreshToken
            $googleAuth->checkAuthCode(true);
        }else {
            $googleAuth->checkAuthCode(false);
        }

        $oauth = $googleAuth->getOauth();

        if(($oauth instanceof Oauth && !$oauth->getRefreshToken()))
        {
            //$existingOauthId = $oauth->getId(); //$this->getOauthModel()->getAccountIdByOauthIdAndProvider($oauth->getId(), "google");
            $existingOauth = $this->getOauthModel()->getById($oauth->getId());

            if(!$existingOauth instanceof Oauth || !$existingOauth->getRefreshToken()) {
                // retry to get refreshToken
                $googleAuth->checkAuthCode(true, true);
                $oauth = $googleAuth->getOauth();
            }
        }

        if (!$oauth instanceof Oauth) {
            //TODO: show error on user frontend
            header("Location: " . Settings::getPanelUrl());
            exit;
        }

        //Experiment: Oauth erst mal nur cachen
        //$oauthId = $this->getOauthModel()->saveOauthGoogle($oauth);
        $_SESSION['oauth_temp'] = array(
            'id' => $oauth->getId(),
            'provider' => $oauth->getProvider(),
            'accessToken' => $oauth->getAccessToken(),
            'accessTokenType' => $oauth->getAccessTokenType(),
            'accessTokenExpiry' => $oauth->getAccessTokenExpiry(),
            'refreshToken' => $oauth->getRefreshToken()
        );
        //Experiment Ende

        if(!isset($_SESSION['oauth']) && $_SESSION['oauth_temp']['id']) {
            $this->login($_SESSION['oauth_temp']);
        }else {
            //TODO: show error on user frontend
        }

        // Map directly to account if it exists
        if($this->isAccount()) {

	        if($this->getOauthModel()->getById($_SESSION['oauth_temp']['id']))
	        {
		        //TODO: show error on user frontend
		        $this->getLogger()->log(__METHOD__, "oauthId is already in use", LOG_ERR);
	        }else
	        {
		        $this->getOauthModel()->saveOauth($this->account->getId(), $_SESSION['oauth_temp']);
	        }
        }

        header("Location: " . Settings::getPanelUrl());
        exit;
    }



    public function verifyGoogleOauth(Oauth $oauth)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        //FIXME: needs implementation
    }


    public function twitterAuthRedirect()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        if(!Settings::isTwitterAuthEnabled())
        {
            //TODO: show error?
            header("Location: " . Settings::getPanelUrl());
            exit;
        }

        /* Build TwitterOAuth object with client credentials. */
        $connection = new TwitterOAuth(Settings::getTwitterConsumerKey(), Settings::getTwitterConsumerSecret());

	    if(Settings::getHttpProxyUrl())
	    {
		    $connection->setProxy(array(
			    'CURLOPT_PROXY' => Settings::getHttpProxyUrl(),
			    'CURLOPT_PROXYUSERPWD' => null,
			    'CURLOPT_PROXYPORT' => null
		    ));
	    }

        /* Get temporary credentials. */
        $request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => Settings::getTwitterAuthCallbackUrl()));

        // in case something goes wrong, redirect to panel
        $url = Settings::getPanelUrl();

        /* If last connection failed don't display authorization link. */
        switch ($connection->getLastHttpCode()) {
            case 200:
                /* Save temporary credentials to session. */
                $_SESSION['oauth_token'] = $request_token['oauth_token'];
                $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

                /* Build authorize URL and redirect user to Twitter. */
                if(isset($_SESSION['account'])) {
                    $url = $connection->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));
                }else {
                    $url = $connection->url('oauth/authenticate', array('oauth_token' => $request_token['oauth_token']));
                }
                break;
            default:
                /* Show notification if something went wrong. */
                echo 'Could not connect to Twitter. Refresh the page or try again later.';
        }
        //echo $twig->render("redirect.html", array("request_token" => $request_token, "url" => $url));
        header("Location: " . $url);
    }



    public function twitterAuthCallback()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        if(!Settings::isTwitterAuthEnabled())
        {
            //TODO: show error?
            header("Location: " . Settings::getPanelUrl());
            exit;
        }

        /* Get temporary credentials from session. */
        $request_token = [];
        $request_token['oauth_token'] = $_SESSION['oauth_token'];
        $request_token['oauth_token_secret'] = $_SESSION['oauth_token_secret'];

        /* If denied, bail. */
        if (isset($_REQUEST['denied'])) {
            exit('Permission was denied. Please start over.');
        }

        /* If the oauth_token is not what we expect, bail. */
        if (isset($_REQUEST['oauth_token']) && $request_token['oauth_token'] !== $_REQUEST['oauth_token']) {
            header('Location: /');
            exit;
        }

        /* Create TwitteroAuth object with app key/secret and token key/secret from default phase */
        $connection = new TwitterOAuth(Settings::getTwitterConsumerKey(), Settings::getTwitterConsumerSecret(), $request_token['oauth_token'], $request_token['oauth_token_secret']);

	    if(Settings::getHttpProxyUrl())
	    {
		    $connection->setProxy(array(
			    'CURLOPT_PROXY' => Settings::getHttpProxyUrl(),
			    'CURLOPT_PROXYUSERPWD' => null,
			    'CURLOPT_PROXYPORT' => 3128
		    ));
	    }

        /* Request access tokens from twitter */
        $access_token = $connection->oauth("oauth/access_token", array("oauth_verifier" => $_REQUEST['oauth_verifier']));

        /* If HTTP response is 200 continue otherwise send to connect page to retry */
        if (200 == $connection->getLastHttpCode()) {
            /* Remove no longer needed request tokens */
            unset($_SESSION['oauth_token']);
            unset($_SESSION['oauth_token_secret']);
            /* The user has been verified and the access tokens can be saved for future use */
            //$_SESSION['status'] = 'verified';

            $connection = new TwitterOAuth(Settings::getTwitterConsumerKey(), Settings::getTwitterConsumerSecret(), $access_token['oauth_token'], $access_token['oauth_token_secret']);

	        if(Settings::getHttpProxyUrl())
	        {
		        $connection->setProxy(array(
			        'CURLOPT_PROXY' => Settings::getHttpProxyUrl(),
			        'CURLOPT_PROXYUSERPWD' => null,
			        'CURLOPT_PROXYPORT' => null
		        ));
	        }

            $user = $connection->get("account/verify_credentials");

            //Experiment: Oauth erst mal nur cachen
            //$oauthId = $this->getOauthModel()->saveOautTwitter($oauth);
            $_SESSION['oauth_temp'] = array(
                'id' => $user->id,
                'provider' => 'twitter',
                'accessToken' => $access_token['oauth_token'],
                'accessTokenSecret' => $access_token['oauth_token_secret'],
                'screenName' => $access_token['screen_name']
            );
            //Experiment Ende

            if(!isset($_SESSION['oauth']) && $_SESSION['oauth_temp']['id']) {
                $this->login($_SESSION['oauth_temp']);
            }else {
                //TODO: show error on user frontend
            }

            // Map directly to account if it exists
            if($this->isAccount()) {
                $this->getLogger()->log(__METHOD__, "session account exist", LOG_ERR);
                $this->getOauthModel()->saveOauth($this->account->getId(), $_SESSION['oauth_temp']);
            }else {
                $this->getLogger()->log(__METHOD__, "NO session account exist", LOG_ERR);
            }

            header("Location: " . Settings::getPanelUrl());
            exit;
        } else {
            //TODO: show error
            header("Location: " . Settings::getPanelUrl());
            exit;
        }
    }


    public function verifyTwitterOauth(Oauth $oauth)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $connection = new TwitterOAuth(Settings::getTwitterConsumerKey(), Settings::getTwitterConsumerSecret(), $oauth->getAccessToken(), $oauth->getAccessTokenSecret());

	    if(Settings::getHttpProxyUrl())
	    {
		    $connection->setProxy(array(
			    'CURLOPT_PROXY' => Settings::getHttpProxyUrl(),
			    'CURLOPT_PROXYUSERPWD' => null,
			    'CURLOPT_PROXYPORT' => 3128
		    ));
	    }

        $connection->get("account/verify_credentials");

        if (200 == $connection->getLastHttpCode()) {
            return true;
        }else {
            $this->getLogger()->log(__METHOD__, "twitter credentials could not be verified for oauth " . $oauth->getId(), LOG_INFO);
            $this->getOauthModel()->deleteOauthTwitter($oauth);
            return false;
        }
    }


    private function isAdminMode()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        if (isset($this->account)) {
            return ($this->account->isAdmin() && $this->account->isActAsAdmin());
        } else {
            return false;
        }
    }


    private function isAccount()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        return isset($this->account);
    }


    private function redirectNoAccount()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        if(!$this->isAccount())
        {
            header("Location: /pages/login.php");
            exit;
        }
    }



    private function isAdmin()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        return (isset($this->account) && $this->isAdminMode());
    }


    private function redirectNoAdmin()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        if(!$this->isAdmin())
        {
            header("Location: /pages/login.php");
            exit;
        }
    }


    public function showIndexPage()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $this->redirectNoAccount();

        $this->showAccountInfoPage();
    }


    public function showLoginPage()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        if($this->isAccount())
        {
            header("Location: /");
            exit;
        }else
        {
            if(isset($_SESSION['oauth_temp'])) {
                header("Location: /pages/register.php");
            }else {
	            $this->showUserMessages();
                $this->smarty->display('login.tpl');
            }
        }
    }


	public function showLoginTabLoginPage()
	{
		$this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $this->smarty->assign('googleAuthEnabled', Settings::isGoogleAuthEnabled());
        $this->smarty->assign('twitterAuthEnabled', Settings::isTwitterAuthEnabled());

		$this->smarty->display('loginTabLogin.tpl');
	}


	public function showLoginTabNewAccountPage()
	{
		$this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $this->smarty->assign('googleAuthEnabled', Settings::isGoogleAuthEnabled());
        $this->smarty->assign('twitterAuthEnabled', Settings::isTwitterAuthEnabled());

		$this->smarty->display('loginTabNewAccount.tpl');
	}


    public function showRegisterPage($action, $data)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        if($this->isAccount())
        {
            header("Location: /");
            exit;
        }else
        {
            if(isset($_SESSION['oauth_temp'])) {
                switch ($action) {
                    case "view":
						if($_SESSION['oauth_temp']['provider'] == 'local') {
							$this->smarty->display('registerLocal.tpl');
						}else
						{
							$this->smarty->display('register.tpl');
						}
                        break;

                    case "cancel":
                        session_destroy();
                        header("Location: /");
                        exit;

                    case "register":
                        $account = new Account();
                        //TODO: HIER
	                    if($_SESSION['oauth_temp']['provider'] == 'local') {
		                    $account->setId($_SESSION['oauth_temp']['id']);
	                    }else
	                    {
		                    $account->setId($data['userName']);
	                    }
                        $account->setFirstName($data['firstName']);
                        $account->setLastName($data['lastName']);
                        $account->setEmail($data['email']);

						$accountId = null;

						//don't save if account already exists
						if(!$this->getAccountModel()->isUserNameExists($account->getId()) && ! $this->getAccountModel()->isEmailExists($account->getEmail()))
						{
							$accountId = $this->getAccountModel()->saveAccount($account);
						}else
						{
							//TODO: error, do something
						}

                        if($accountId)
                        {
                            $this->getOauthModel()->saveOauth($accountId, $_SESSION['oauth_temp']);
                            $this->login($_SESSION['oauth_temp']);
                            unset($_SESSION['oauth_temp']);
                        }

                        header("Location: /");
                        exit;
                        break;
                }
            }else {
                //TODO: do something if neither account or oauth exit
                header("Location: /");
                exit;
            }
        }
    }


    public function showUserInfoPage()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $this->redirectNoAccount();

        $this->smarty->display('userinfo.tpl');
    }


    public function showMyReferrersPage($action = NULL, $url = NULL)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $this->redirectNoAccount();

        switch ($action) {
            case "add":
                //FIXME: validate URL first
                if ($url) {
                    try {
                        $this->getReferrerModel()->addAccountIdToReferrer($url, $this->account);
                    } catch (FrontendException $e) {
                        $this->getLogger()->log(__METHOD__, "FrontendException: " . $e->getMessage(), LOG_ERR);
                        $this->smarty->assign('error', $e->getMessage());
                    } catch (\Exception $e) {
                        $this->getLogger()->log(__METHOD__, "Exception: " . $e->getMessage(), LOG_ERR);
                    }
                }

                break;

            case "delete":
                //FIXME: delete only user's URL's.
                $this->getReferrerModel()->deleteAccountIdFromReferrer($url, $this->account->getId());
                break;

            case "enable":
                $this->getAccountModel()->updateWhitelistUsage($this->account->getId(), true);
                // reset and reload account
                $this->setGlobals();
                break;

            case "disable":
                $this->getAccountModel()->updateWhitelistUsage($this->account->getId(), false);
                // reset and reload account
                $this->setGlobals();
                break;
        }

        $this->smarty->display('myreferrers.tpl');
    }


    public function showStatisticsPage()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $this->redirectNoAdmin();

        $this->smarty->display('statistics.tpl');
    }


    public function showAllTargetsPage()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $this->redirectNoAdmin();

        $this->smarty->display('alltargets.tpl');
    }


    public function showMyTargetsPage()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $this->redirectNoAccount();

        $this->smarty->display('mytargets.tpl');
    }


    public function showAllTargetsJson($draw, $start, $length, $search, $orderColumn, $orderDirection)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $result = array();
        $result['draw'] = $draw;
        $result['recordsTotal'] = 0;
        $result['recordsFiltered'] = 0;
        $result['data'] = array();


        if($this->isAdmin()) {
            $numTargets = $this->getTargetModel()->getNumTargets();
            $numTargetsFiltered = $this->getTargetModel()->getNumTargets($search);

            $targets = $this->getTargetModel()->getTargets($orderColumn, $orderDirection, $length, $start, $search);

            $result['recordsTotal'] = intval($numTargets);
            $result['recordsFiltered'] = intval($numTargetsFiltered);

            foreach ($targets as $target) {
                /** @var Target $target */

                $t = array();
                $t[] = $target->getId();
                $t[] = $target->getUrl();
                $t[] = date("d.m.Y H:i:s", $target->getTsAdded());
                $t[] = date("d.m.Y H:i:s", $target->getTsLastRequested());
                $t[] = intval($target->getCounterUpdated()) > 0 ? $target->getCounterUpdated() : 0;
                $t[] = intval($target->getCounterFailed()) > 0 ? $target->getCounterFailed() : 0;
                $t[] = intval($target->getNumRequests()) > 0 ? $target->getNumRequests() : 0;
                $result['data'][] = $t;
            }
        }

        header('Content-type: application/json');

        echo json_encode($result);
    }



    public function showMyTargetsJson($draw, $start, $length, $search, $orderColumn, $orderDirection)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $result = array();
        $result['draw'] = $draw;
        $result['recordsTotal'] = 0;
        $result['recordsFiltered'] = 0;
        $result['data'] = array();


        if($this->isAccount()) {
            $numTargets = $this->getTargetModel()->getNumTargetsByAccount($this->account->getId());
            $numTargetsFiltered = $this->getTargetModel()->getNumTargetsByAccount($this->account->getId(), $search);

            $targets = $this->getTargetModel()->getTargetsByAccount($this->account->getId(), $orderColumn, $orderDirection, $length, $start, $search);

            $result['recordsTotal'] = intval($numTargets);
            $result['recordsFiltered'] = intval($numTargetsFiltered);

            foreach ($targets as $target) {
                /** @var Target $target */

                $t = array();
                $t[] = $target->getId();
                $t[] = $target->getUrl();
                $t[] = intval($target->getCounterUpdated()) > 0 ? $target->getCounterUpdated() : 0;
                $result['data'][] = $t;
            }
        }

        header('Content-type: application/json');

        echo json_encode($result);
    }



    public function showImageInfoPage($imageId)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $this->redirectNoAccount();

        $image = $this->getImageModel()->getById($imageId);
        $target = $this->getTargetModel()->getById($image->getTargetId());

        $this->smarty->assignByRef('image', $image);
        $this->smarty->assignByRef('target', $target);

        // TEST Thumbnail
        $this->loadAndValidateCommonParameters("thumbnail");
        $this->loadAndValidateThumbnailParameters($image->getWidth(), $image->getEffect(), $target->getUrl(), null, null, false, null, null);
        Settings::setImageWatermarksEnabled(false);

        $thumbnail = $this->processThumbnailRequest();

	    $this->getLogger()->log(__METHOD__, "thumbnail: " . print_r($thumbnail, true), LOG_INFO);

        $this->smarty->assignByRef('thumbnail', $thumbnail);

        $this->smarty->display('imageinfo.tpl');
    }


    public function showAllReferrersPage()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $this->redirectNoAdmin();

        $this->smarty->display('allreferrers.tpl');
    }


    public function showAllReferrersJson($draw, $start, $length, $search, $orderColumn, $orderDirection)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $result = array();
        $result['draw'] = $draw;
        $result['recordsTotal'] = 0;
        $result['recordsFiltered'] = 0;
        $result['data'] = array();

        if($this->isAdmin()) {
            $numReferrers = $this->getReferrerModel()->getNumReferrers();
            $numReferrersFiltered = $this->getReferrerModel()->getNumReferrers(null, $search);

            $referrers = $this->getReferrerModel()->getReferrers($orderColumn, $orderDirection, $length, $start, $search, null);

            $result['recordsTotal'] = intval($numReferrers);
            $result['recordsFiltered'] = intval($numReferrersFiltered);

            foreach ($referrers as $referrer) {
                /** @var Referrer $referrer */

                $r = array();
                $r[] = $referrer->getId();
                $r[] = $referrer->getUrlBase();
                $r[] = date("d.m.Y H:i:s", $referrer->getTsAdded());
                $r[] = date("d.m.Y H:i:s", $referrer->getTsLastSeen());
                $r[] = $referrer->getNumRequests();

                $result['data'][] = $r;
            }
        }

        header('Content-type: application/json');

        echo json_encode($result);
    }


    public function showMyReferrersJson($draw, $start, $length, $search, $orderColumn, $orderDirection)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $result = array();
        $result['draw'] = $draw;
        $result['recordsTotal'] = 0;
        $result['recordsFiltered'] = 0;
        $result['data'] = array();

        if($this->isAccount()) {
            $numReferrers = $this->getReferrerModel()->getNumReferrersByAccountId($this->account->getId());
            $numReferrersFiltered = $this->getReferrerModel()->getNumReferrersByAccountId($this->account->getId(), $search);

            $referrers = $this->getReferrerModel()->getReferrers($orderColumn, $orderDirection, $length, $start, $search, $this->account->getId());

            $result['recordsTotal'] = intval($numReferrers);
            $result['recordsFiltered'] = intval($numReferrersFiltered);

            foreach ($referrers as $referrer) {
                /** @var Referrer $referrer */

                $r = array();
                $r[] = $referrer->getId();
                $r[] = $referrer->getUrlBase();
                $r[] = date("d.m.Y H:i:s", $referrer->getTsAdded());
                $r[] = date("d.m.Y H:i:s", $referrer->getTsLastSeen());
                $r[] = $referrer->getNumRequests();
                $result['data'][] = $r;
            }
        }

        header('Content-type: application/json');

        echo json_encode($result);
    }


    public function showTargetInfoPage($targetId, $action = NULL)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        //TODO: wie erfahre ich, ob ein Account über Referrer berechtigt ist, das Target zu sehen?

        $this->redirectNoAccount();

        $numReferrers = $this->getReferrerModel()->getNumReferrers($targetId, null);
        //$referrers = $this->getReferrerModel()->getTargetReferrers($targetId, ($this->isAdminMode() ? NULL : $this->account->getId()), "id", "asc", null, 0, null);

        if($numReferrers > 0 || $this->isAdminMode())
        {
            //TODO: darf der User das uneingeschränkt sehen?
            $target = $this->getTargetModel()->getById($targetId);

            if(isset($action))
            {
                switch ($action) {
                    case "forceUpdate":
                        $this->getTargetModel()->forceUpdate($target->getId());
                        $target->setForcedUpdate(true);

                        $targetPriority = Settings::getTargetPriority($this->account->getApiKeyType());
                        $imageMaxAge = Settings::getImageMaxAge($this->account->getApiKeyType());

                        $this->getTargetModel()->checkTargetCurrentness($target, $targetPriority, $imageMaxAge);

                        header("Location: /pages/targetinfo.php?id=" . $target->getId());
                        exit;
                    break;
                }
            }

            $this->smarty->assignByRef('target', $target);
            $this->smarty->assign('isBlacklisted', $this->getTargetModel()->isBlacklisted($target->getUrl()));
            //$this->smarty->assignByRef('referrers', $referrers);
        }

        $this->smarty->display('targetinfo.tpl');
    }


    public function showTargetInfoImagesJson($draw, $start, $length, $search, $orderColumn, $orderDirection, $targetId)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        //TODO: wie erfahre ich, ob ein Account über Referrer berechtigt ist, das Target zu sehen?

        $result = array();
        $result['draw'] = $draw;
        $result['recordsTotal'] = 0;
        $result['recordsFiltered'] = 0;
        $result['data'] = array();

        if($this->isAccount()) {
            $referrers = $this->getReferrerModel()->getTargetReferrers($targetId, ($this->isAdminMode() ? NULL : $this->account->getId()), "id", "asc", null, 0, null);

            if (count($referrers) > 0 || $this->isAdminMode()) {
                //TODO: darf der User das uneingeschränkt sehen?
                $images = $this->getImageModel()->getImages($targetId, $orderColumn, $orderDirection, $length, $start, $search);

                $result['recordsTotal'] = intval($this->getImageModel()->getNumImages($targetId));
                $result['recordsFiltered'] = intval($this->getImageModel()->getNumImages($targetId, $search));

                foreach ($images as $image) {
                    /** @var Image $image */

                    $i = array();
                    $i[] = $image->getId();
                    $i[] = $image->getWidth();
                    $i[] = $image->getEffect();
                    $i[] = $image->getFileNameSuffix();
                    $i[] = date("d.m.Y H:i:s", $image->getTsAdded());
                    $result['data'][] = $i;
                }
            }
        }

        header('Content-type: application/json');

        echo json_encode($result);
    }



    public function showTargetInfoReferrersJson($draw, $start, $length, $search, $orderColumn, $orderDirection, $targetId)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        //TODO: wie erfahre ich, ob ein Account über Referrer berechtigt ist, das Target zu sehen?

        $result = array();
        $result['draw'] = $draw;
        $result['recordsTotal'] = 0;
        $result['recordsFiltered'] = 0;
        $result['data'] = array();

        if($this->isAccount()) {

            $referrers = $this->getReferrerModel()->getTargetReferrers($targetId, ($this->isAdminMode() ? NULL : $this->account->getId()), $orderColumn, $orderDirection, $length, $start, $search);

            if (count($referrers) > 0 || $this->isAdminMode()) {
                $result['recordsTotal'] = intval($this->getReferrerModel()->getNumReferrers($targetId, null, ($this->isAdminMode() ? NULL : $this->account->getId())));
                $result['recordsFiltered'] = intval($this->getReferrerModel()->getNumReferrers($targetId, $search, ($this->isAdminMode() ? NULL : $this->account->getId())));

                foreach ($referrers as $referrer) {
                    /** @var Referrer $referrer */

                    $r = array();
                    $r[] = $referrer->getId();
                    $r[] = $referrer->getUrlBase();
                    $r[] = date("d.m.Y H:i:s", $referrer->getTsAdded());
                    $result['data'][] = $r;
                }
            }
        }

        header('Content-type: application/json');

        echo json_encode($result);
    }



    public function showReferrerInfoPage($referrerId)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $this->redirectNoAccount();

        $referrer = $this->getReferrerModel()->getById($referrerId);

        try {
            if ($referrer->getAccountId() != $this->account->getId() && !$this->isAdminMode()) {
                throw new FrontendException("no permission");
            } else {
                $this->smarty->assignByRef('referrer', $referrer);

                if ($referrer->getAccountId() != NULL) {
                    $referrerAccount = $this->getAccountModel()->getById($referrer->getAccountId());
                    $this->smarty->assignByRef('referrerAccount', $referrerAccount);
                }
            }
        }catch (FrontendException $e)
        {
            $this->getLogger()->log(__METHOD__, "FrontendException: " . $e->getMessage(), LOG_ERR);
            $this->smarty->assign('error', $e->getMessage());
        }

        $this->smarty->display('referrerinfo.tpl');
    }


    public function showReferrerInfoDeeplinksJson($draw, $start, $length, $search, $orderColumn, $orderDirection, $referrerId)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $result = array();

        $result['draw'] = $draw;
        $result['recordsTotal'] = 0;
        $result['recordsFiltered'] = 0;
        $result['data'] = array();

        if($this->isAccount()) {
            $referrer = $this->getReferrerModel()->getById($referrerId);

            if ($referrer->getAccountId() != $this->account->getId() && !$this->isAdminMode()) {
                //TODO: not allowed, show error
            } else {
                $deeplinks = $this->getReferrerDeeplinkModel()->getReferrerDeeplinks($referrer->getId(), $orderColumn, $orderDirection, $length, $start, $search);

                if (count($deeplinks) > 0) {

                    $result['recordsTotal'] = intval($this->getReferrerDeeplinkModel()->getNumReferrerDeeplinks($referrer->getId(), null));
                    $result['recordsFiltered'] = intval($this->getReferrerDeeplinkModel()->getNumReferrerDeeplinks($referrer->getId(), $search));

                    foreach ($deeplinks as $deeplink) {
                        /** @var ReferrerDeeplink $deeplink */

                        $d = array();
                        $d[] = $deeplink->getUrl();
                        $d[] = $deeplink->getNumRequests();
                        $result['data'][] = $d;
                    }
                }
            }
        }

        header('Content-type: application/json');

        echo json_encode($result);
    }


    public function showReferrerInfoTargetsJson($draw, $start, $length, $search, $orderColumn, $orderDirection, $referrerId)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $result = array();
        $result['draw'] = $draw;
        $result['recordsTotal'] = 0;
        $result['recordsFiltered'] = 0;
        $result['data'] = array();

        if($this->isAccount()) {
            $referrer = $this->getReferrerModel()->getById($referrerId);

            if ($referrer->getAccountId() != $this->account->getId() && !$this->isAdminMode()) {
                //TODO: not allowed, show error
            } else {
                $filteredTargets = $this->getTargetModel()->getReferrerTargets($referrerId, null, $orderColumn, $orderDirection, $length, $start, $search);

                $result['draw'] = $draw;
                $result['recordsTotal'] = intval($this->getTargetModel()->getNumReferrerTargets($referrerId, null, null));
                $result['recordsFiltered'] = intval($this->getTargetModel()->getNumReferrerTargets($referrerId, null, $search));
                $result['data'] = array();

                foreach ($filteredTargets as $target) {
                    /** @var Target $target */

                    $t = array();
                    $t[] = $target->getId();
                    $t[] = $target->getUrl();
                    $t[] = date("d.m.Y H:i:s", $target->getTsAdded());
                    $result['data'][] = $t;
                }

                //$result['data'] = array_slice($result['data'], $start, $length);
            }
        }

        header('Content-type: application/json');

        echo json_encode($result);
    }


    public function showDailyRequestsJson($days)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $result = array();

        if($this->isAdminMode()) {
            $dailyRequests = $this->getApiStatistics()->getDeliveryDailyStats();

            if(is_array($dailyRequests)) {
                if (count($dailyRequests) > $days) {
                    $dailyRequests = array_slice($dailyRequests, count($dailyRequests) - $days, count($dailyRequests), true);
                }

                foreach ($dailyRequests as $day => $num) {
                    $row['date'] = $day;
                    $row['value'] = $num;
                    $result[] = $row;
                }
            }else {
                $date = new \DateTime();
                $result[] = array('date' => $date->format('Y-m-d'), 'value' => 0);
            }
        }

        header('Content-type: application/json');

        echo json_encode($result);
    }


    public function showDailyProcessedItemsJson($days)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $result = array();


        if($this->isAdminMode()) {
            $dailyImagesUpdated = $this->getApiStatistics()->getImagesUpdatedDailyStats();
            $dailyImagesFailed = $this->getApiStatistics()->getImagesFailedDailyStats();

            $dailyTargetsUpdatedNormal = $this->getApiStatistics()->getTargetsUpdatedDailyStats('normal');
            $dailyTargetsFailedNormal = $this->getApiStatistics()->getTargetsFailedDailyStats('normal');
            $dailyTargetsForbiddenNormal = $this->getApiStatistics()->getTargetsForbiddenDailyStats('normal');

            $dailyTargetsUpdatedLongrun = $this->getApiStatistics()->getTargetsUpdatedDailyStats('longrun');
            $dailyTargetsFailedLongrun = $this->getApiStatistics()->getTargetsFailedDailyStats('longrun');
            $dailyTargetsForbiddenLongrun = $this->getApiStatistics()->getTargetsForbiddenDailyStats('longrun');

            $dailyTargetsUpdated = $dailyTargetsUpdatedNormal;
            foreach($dailyTargetsUpdatedLongrun as $day=>$num)
            {
                if(!isset($dailyTargetsUpdated[$day]))
                {
                    $dailyTargetsUpdated[$day] = $num;
                }else {
                    $dailyTargetsUpdated[$day] += $num;
                }
            }

            $dailyTargetsFailed = $dailyTargetsFailedNormal;
            foreach($dailyTargetsFailedLongrun as $day=>$num)
            {
                if(!isset($dailyTargetsFailed[$day]))
                {
                    $dailyTargetsFailed[$day] = $num;
                }else {
                    $dailyTargetsFailed[$day] += $num;
                }
            }

            $dailyTargetsForbidden = $dailyTargetsForbiddenNormal;
            foreach($dailyTargetsForbiddenLongrun as $day=>$num)
            {
                if(!isset($dailyTargetsForbidden[$day]))
                {
                    $dailyTargetsForbidden[$day] = $num;
                }else {
                    $dailyTargetsForbidden[$day] += $num;
                }
            }

            if(count($dailyImagesUpdated) > $days) {
                $dailyImagesUpdated = array_slice($dailyImagesUpdated, count($dailyImagesUpdated) - $days, count($dailyImagesUpdated), true);
            }

            if(count($dailyImagesFailed) > $days) {
                $dailyImagesFailed = array_slice($dailyImagesFailed, count($dailyImagesFailed) - $days, count($dailyImagesFailed), true);
            }

            if(count($dailyTargetsUpdated) > $days) {
                $dailyTargetsUpdated = array_slice($dailyTargetsUpdated, count($dailyTargetsUpdated) - $days, count($dailyTargetsUpdated), true);
            }

            if(count($dailyTargetsFailed) > $days) {
                $dailyTargetsFailed = array_slice($dailyTargetsFailed, count($dailyTargetsFailed) - $days, count($dailyTargetsFailed), true);
            }

            if(count($dailyTargetsForbidden) > $days) {
                $dailyTargetsForbidden = array_slice($dailyTargetsForbidden, count($dailyTargetsForbidden) - $days, count($dailyTargetsForbidden), true);
            }

            $dates = array_merge(array_keys($dailyTargetsUpdated), array_keys($dailyImagesUpdated), array_keys($dailyTargetsFailed), array_keys($dailyImagesFailed), array_keys($dailyTargetsForbidden));

            foreach($dates as $date) {
                $row = array();
                $row['date'] = $date;
                $row['targetsUpdated'] = isset($dailyTargetsUpdated[$date]) ? $dailyTargetsUpdated[$date] : 0;
                $row['imagesUpdated'] = isset($dailyImagesUpdated[$date]) ? $dailyImagesUpdated[$date] : 0;
                $row['targetsFailed'] = isset($dailyTargetsFailed[$date]) ? $dailyTargetsFailed[$date] : 0;
                $row['targetsForbidden'] = isset($dailyTargetsForbidden[$date]) ? $dailyTargetsForbidden[$date] : 0;
                $row['imagesFailed'] = isset($dailyImagesFailed[$date]) ? $dailyImagesFailed[$date] : 0;
                $result[] = $row;
            }
        }

        header('Content-type: application/json');

        echo json_encode($result);
    }


    public function showDailyNewTargetsJson($days)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $result = array();

        if($this->isAdminMode()) {
            $dailyNewTargets = $this->getApiStatistics()->getNewTargetsDailyStats();

            if(count($dailyNewTargets) > 0) {

                if(count($dailyNewTargets) > $days) {
                    $dailyNewTargets = array_slice($dailyNewTargets, count($dailyNewTargets) - $days, count($dailyNewTargets), true);
                }

                foreach ($dailyNewTargets as $day => $num) {
                    $row['date'] = $day;
                    $row['value'] = $num;
                    $result[] = $row;
                }
            }else {
                $date = new \DateTime();

                $result[] = array('date' => $date->format('Y-m-d'), 'value' => 0);
            }
        }

        header('Content-type: application/json');

        echo json_encode($result);
    }


    public function showDailyNewReferrersJson($days)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $result = array();

        if($this->isAdminMode()) {
            $dailyNewReferrers = $this->getApiStatistics()->getNewReferrersDailyStats();

            if(count($dailyNewReferrers) > 0) {
                if(count($dailyNewReferrers) > $days) {
                    $dailyNewReferrers = array_slice($dailyNewReferrers, count($dailyNewReferrers) - $days, count($dailyNewReferrers), true);
                }

                foreach ($dailyNewReferrers as $day => $num) {
                    $row['date'] = $day;
                    $row['value'] = $num;
                    $result[] = $row;
                }
            }else {
                $date = new \DateTime();

                $result[] = array('date' => $date->format('Y-m-d'), 'value' => 0);
            }
        }

        header('Content-type: application/json');

        echo json_encode($result);
    }


    public function showQueueSizesJson()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $result = array();

        if($this->isAdminMode()) {

                //$date = \DateTime::createFromFormat('Ymd', time());
                $row['date'] = date('H:i:s', time());
                $row['targetNormal'] = $this->getTargetModel()->getQueueSizeJobTargetNormal();
                $row['targetLongrun'] = $this->getTargetModel()->getQueueSizeJobTargetLongrun();
                $row['image'] = $this->getImageModel()->getThumbnailJobQueueSize();
                $result[] = $row;
        }

        header('Content-type: application/json');

        echo json_encode($result);
    }


    public function showNumTotalObjectivesJson()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $result = array();

        if($this->isAdminMode()) {

            //$date = \DateTime::createFromFormat('Ymd', time());
            $row['date'] = date('H:i:s', time());
            $row['targets'] = (int) $this->getTargetModel()->getNumTargets();
            $row['images'] = (int) $this->getImageModel()->getNumImages();
            $row['referrers'] = (int) $this->getReferrerModel()->getNumReferrers();
            $result[] = $row;
        }

        header('Content-type: application/json');

        echo json_encode($result);
    }


    public function showDailyActiveAccountsJson($days)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $result = array();

        if($this->isAdminMode()) {
            $dailyActiveAccounts = $this->getApiStatistics()->getDailyActiveAccounts($days);

            if(count($dailyActiveAccounts) > 0) {
                foreach ($dailyActiveAccounts as $day => $num) {
                    $row['date'] = $day;
                    $row['value'] = $num;
                    $result[] = $row;
                }
            }else {
                $date = new \DateTime();

                $result[] = array('date' => $date->format('Y-m-d'), 'value' => 0);
            }
        }

        header('Content-type: application/json');

        echo json_encode($result);
    }


    public function showNumImageReqestsDailyJson($imageId, $days)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $result = array();

        //TODO: is a normal user allowed to see this?
        // check permissions for imageId

        if($this->isAccount()) {
            $dailyNumRequests = $this->getImageModel()->getNumRequests($imageId, $days);

            if(is_array($dailyNumRequests) && !empty($dailyNumRequests)) {
                foreach ($dailyNumRequests as $day => $num) {
                    $row['date'] = $day;
                    $row['value'] = $num;
                    $result[] = $row;
                }
            }
        }

        if(empty($result)) {
            $date = new \DateTime();
            $result[] = array('date' => $date->format('Y-m-d'), 'value' => 0);
        }

        header('Content-type: application/json');

        echo json_encode($result);
    }


    public function showNumTargetReqestsDailyJson($targetId, $days)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $result = array();

        if($this->isAccount()) {

            $images = $this->getImageModel()->getImages($targetId);

            $rows = array();

            /** @var Image $image */
            foreach($images as $image) {
                $dailyNumRequests = $this->getImageModel()->getNumRequests($image->getId(), $days);

                if (is_array($dailyNumRequests) && !empty($dailyNumRequests)) {
                    foreach ($dailyNumRequests as $day => $num) {
                        if(!isset($rows[$day]))
                        {
                            $rows[$day] = 0;
                        }

                        $rows[$day] += $num;
                    }
                }
            }

            if(is_array($rows) && !empty($rows)) {
                foreach($rows as $date => $value)
                {
                    $row['date'] = $date;
                    $row['value'] = $value;
                    $result[] = $row;
                }
            }
        }

        if(empty($result))
        {
            $date = new \DateTime();
            $result[] = array('date' => $date->format('Y-m-d'), 'value' => 0);
        }

        header('Content-type: application/json');

        echo json_encode($result);
    }


    public function showNumReferrerReqestsDailyJson($referrerId, $days)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $result = array();

        if($this->isAccount()) {
            $referrer = $this->getReferrerModel()->getById($referrerId);

            try {
                if ($referrer->getAccountId() != $this->account->getId() && !$this->isAdminMode()) {
                    throw new \Exception("no permission");
                } else {
                    $dailyNumRequests = $this->getReferrerModel()->getNumRequestsDaily($referrerId, $days);

                    if (is_array($dailyNumRequests) && !empty($dailyNumRequests)) {
                        foreach ($dailyNumRequests as $day => $num) {
                            $row['date'] = $day;
                            $row['value'] = $num;
                            $result[] = $row;
                        }
                    } else {
                        $date = new \DateTime();

                        $result[] = array('date' => $date->format('Y-m-d'), 'value' => 0);
                    }
                }
            } catch (\Exception $e) {
                $this->getLogger()->log(__METHOD__, "Exception: " . $e->getMessage(), LOG_ERR);
            }
        }

        header('Content-type: application/json');

        echo json_encode($result);
    }


    public function showInitAllDummiesPage()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $this->redirectNoAdmin();

        $images = array();

        foreach(array_keys(Settings::getActiveImageEffects()) as $effect)
        {
            foreach(Settings::getApiValidWidths() as $width)
            {
                $url = Settings::getFrontendImagesUrl() . "/static/dummy.png";
                $target = $this->getTargetModel()->getOrCreateByUrl($url, $width, $effect);
                $thumbsniperClient = new ThumbSniperClient();

                if($target instanceof Target)
                {
                    $this->getTargetModel()->checkTargetCurrentness($target, 1000, Settings::getImageDefaultMaxAge());
                }

                $image = array();
                $image['width'] = $width;
                $image['effect'] = $effect;
                $image['url'] = $thumbsniperClient->getThumbnailUrl($url, $width, $effect);

                $images[] = $image;
            }
        }

        $this->smarty->assignByRef('images', $images);

        $this->smarty->display('initallimages.tpl');
    }


    public function showInitAllRobotsPage()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $this->redirectNoAdmin();

        $images = array();

        foreach(array_keys(Settings::getActiveImageEffects()) as $effect)
        {
            foreach(Settings::getApiValidWidths() as $width)
            {
                $url = Settings::getFrontendImagesUrl() . "/static/robots.png";
                $target = $this->getTargetModel()->getOrCreateByUrl($url, $width, $effect);
                $thumbsniperClient = new ThumbSniperClient();

                if($target instanceof Target)
                {
                    $this->getTargetModel()->checkTargetCurrentness($target, 1000, Settings::getImageDefaultMaxAge());
                }

                $image = array();
                $image['width'] = $width;
                $image['effect'] = $effect;
                $image['url'] = $thumbsniperClient->getThumbnailUrl($url, $width, $effect);

                $images[] = $image;
            }
        }

        $this->smarty->assignByRef('images', $images);

        $this->smarty->display('initallimages.tpl');
    }


    public function showInitAllBrokenPage()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $this->redirectNoAdmin();

        $images = array();

        foreach(array_keys(Settings::getActiveImageEffects()) as $effect)
        {
            foreach(Settings::getApiValidWidths() as $width)
            {
                $url = Settings::getFrontendImagesUrl() . "/static/broken.png";
                $target = $this->getTargetModel()->getOrCreateByUrl($url, $width, $effect);
                $thumbsniperClient = new ThumbSniperClient();

                if($target instanceof Target)
                {
                    $this->getTargetModel()->checkTargetCurrentness($target, 1000, Settings::getImageDefaultMaxAge());
                }

                $image = array();
                $image['width'] = $width;
                $image['effect'] = $effect;
                $image['url'] = $thumbsniperClient->getThumbnailUrl($url, $width, $effect);

                $images[] = $image;
            }
        }

        $this->smarty->assignByRef('images', $images);

        $this->smarty->display('initallimages.tpl');
    }


    public function showInitAllViolationPage()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $this->redirectNoAdmin();

        $images = array();

        foreach(array_keys(Settings::getActiveImageEffects()) as $effect)
        {
            foreach(Settings::getApiValidWidths() as $width)
            {
                $url = Settings::getFrontendImagesUrl() . "/static/violation.png";
                $target = $this->getTargetModel()->getOrCreateByUrl($url, $width, $effect);
                $thumbsniperClient = new ThumbSniperClient();

                if($target instanceof Target)
                {
                    $this->getTargetModel()->checkTargetCurrentness($target, 1000, Settings::getImageDefaultMaxAge());
                }

                $image = array();
                $image['width'] = $width;
                $image['effect'] = $effect;
                $image['url'] = $thumbsniperClient->getThumbnailUrl($url, $width, $effect);

                $images[] = $image;
            }
        }

        $this->smarty->assignByRef('images', $images);

        $this->smarty->display('initallimages.tpl');
    }


    public function showAllAccountsPage()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $this->redirectNoAdmin();

        $this->smarty->display('allaccounts.tpl');
    }


    public function showAllAccountsJson($draw, $start, $length, $search, $orderColumn, $orderDirection)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $result = array();
        $result['draw'] = $draw;
        $result['recordsTotal'] = 0;
        $result['recordsFiltered'] = 0;
        $result['data'] = array();

        if($this->isAdminMode()) {
            $numAccounts = $this->getAccountModel()->getNumAccounts();
            $numAccountsFiltered = $this->getAccountModel()->getNumAccounts($search);

            $accounts = $this->getAccountModel()->getAccounts($orderColumn, $orderDirection, $length, $start, $search);

            $result['recordsTotal'] = intval($numAccounts);
            $result['recordsFiltered'] = intval($numAccountsFiltered);

            foreach ($accounts as $account) {
                /** @var Account $account */

                $a = array();
                $a[] = $account->getId();
                $a[] = $account->getFirstName();
                $a[] = $account->getLastName();
                $a[] = $account->getEmail();
                $a[] = $account->isEmailVerified();
                $a[] = date("d.m.Y H:i:s", $account->getTsAdded());
                $result['data'][] = $a;
            }
        }

        header('Content-type: application/json');

        echo json_encode($result);
    }


    public function showAccountInfoPage($action = "view", $oauthId = NULL)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $this->redirectNoAccount();
        $account = $this->account;
        $privateApiKey = NULL;

        if(isset($action))
        {
            switch($action)
            {
                case "create":
                case "reset":
                    $privateApiKey = $this->getAccountModel()->createApiKey($this->account->getId());
                    $this->setGlobals();
                    $account = $this->account;
                    break;

	            case "view":
		            if($this->isAdminMode() && isset($oauthId))
		            {
			            $account = $this->getAccountModel()->getById($oauthId);
		            }
	                break;
            }
        }

        $this->smarty->assignByRef("myaccount", $account);

        if ($privateApiKey) {
            $this->smarty->assignByRef('privateApiKey', $privateApiKey);
        }

        $oauthProfiles = array();

        if(Settings::isGoogleAuthEnabled()) {
            $oauthProfileGoogle = $this->getOauthModel()->getByProvider($account->getId(), "google");

            if($oauthProfileGoogle instanceof Oauth)
            {
                $oauthProfiles['google'] = $oauthProfileGoogle;
            }
        }

        if(Settings::isTwitterAuthEnabled()) {
            $oauthProfileTwitter = $this->getOauthModel()->getByProvider($account->getId(), "twitter");

            if ($oauthProfileTwitter instanceof Oauth) {
                $oauthProfiles['twitter'] = $oauthProfileTwitter;
            }
        }

        if(!empty($oauthProfiles)) {
            $this->smarty->assignByRef('oauthProfiles', $oauthProfiles);
        }

        $this->smarty->assign('googleAuthEnabled', Settings::isGoogleAuthEnabled());
        $this->smarty->assign('twitterAuthEnabled', Settings::isTwitterAuthEnabled());

        $this->smarty->display('accountinfo.tpl');
    }


    public function showNumAccountReqestsDailyJson($oauthId, $days)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $result = array();

        if($this->isAdminMode()) {
            $dailyNumRequests = $this->getAccountModel()->getNumRequests($oauthId, $days);
        }else {
            $dailyNumRequests = $this->getAccountModel()->getNumRequests($this->account->getId(), $days);
        }

        if(is_array($dailyNumRequests) && !empty($dailyNumRequests)) {
            foreach ($dailyNumRequests as $day => $num) {
                $row['date'] = $day;
                $row['numRequests'] = $num;
                $result[] = $row;
            }
        }else {
            $date = new \DateTime();

            $result[] = array('date' => $date->format('Y-m-d'), 'value' => 0);
        }


        header('Content-type: application/json');

        echo json_encode($result);
    }



    public function showDailyProcessedMastersJson($mode, $days)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $result = array();


        if($this->isAdminMode()) {
            $dailyMasterAgentConnections = $this->getApiStatistics()->getMasterAgentConnectionsDailyStats($mode);
            $dailyTargetsUpdated = $this->getApiStatistics()->getTargetsUpdatedDailyStats($mode);
            $dailyTargetsFailed = $this->getApiStatistics()->getTargetsFailedDailyStats($mode);
            $dailyTargetsForbidden = $this->getApiStatistics()->getTargetsForbiddenDailyStats($mode);

            if(count($dailyMasterAgentConnections) > $days) {
                $dailyMasterAgentConnections = array_slice($dailyMasterAgentConnections, count($dailyMasterAgentConnections) - $days, count($dailyMasterAgentConnections), true);
            }

            if(count($dailyTargetsUpdated) > $days) {
                $dailyTargetsUpdated = array_slice($dailyTargetsUpdated, count($dailyTargetsUpdated) - $days, count($dailyTargetsUpdated), true);
            }

            if(count($dailyTargetsFailed) > $days) {
                $dailyTargetsFailed = array_slice($dailyTargetsFailed, count($dailyTargetsFailed) - $days, count($dailyTargetsFailed), true);
            }

            if(count($dailyTargetsForbidden) > $days) {
                $dailyTargetsForbidden = array_slice($dailyTargetsForbidden, count($dailyTargetsForbidden) - $days, count($dailyTargetsForbidden), true);
            }

            $dates = array_merge(array_keys($dailyMasterAgentConnections), array_keys($dailyTargetsUpdated), array_keys($dailyTargetsFailed), array_keys($dailyTargetsForbidden));

            foreach($dates as $date) {
                $row = array();
                $row['date'] = $date;
                $row['agentConnections'] = isset($dailyMasterAgentConnections[$date]) ? $dailyMasterAgentConnections[$date] : 0;
                $row['targetsUpdated'] = isset($dailyTargetsUpdated[$date]) ? $dailyTargetsUpdated[$date] : 0;
                $row['targetsFailed'] = isset($dailyTargetsFailed[$date]) ? $dailyTargetsFailed[$date] : 0;
                $row['targetsForbidden'] = isset($dailyTargetsForbidden[$date]) ? $dailyTargetsForbidden[$date] : 0;
                $result[] = $row;
            }
        }

        header('Content-type: application/json');

        echo json_encode($result);
    }



    public function showDailyProcessedThumbnailsJson($days)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $result = array();


        if($this->isAdminMode()) {
            $dailyThumbnailAgentConnections = $this->getApiStatistics()->getThumbnailAgentConnectionsDailyStats();
            $dailyImagesUpdated = $this->getApiStatistics()->getImagesUpdatedDailyStats();
            $dailyImagesFailed = $this->getApiStatistics()->getImagesFailedDailyStats();

            if(count($dailyThumbnailAgentConnections) > $days) {
                $dailyThumbnailAgentConnections = array_slice($dailyThumbnailAgentConnections, count($dailyThumbnailAgentConnections) - $days, count($dailyThumbnailAgentConnections), true);
            }

            if(count($dailyImagesUpdated) > $days) {
                $dailyImagesUpdated = array_slice($dailyImagesUpdated, count($dailyImagesUpdated) - $days, count($dailyImagesUpdated), true);
            }

            if(count($dailyImagesFailed) > $days) {
                $dailyImagesFailed = array_slice($dailyImagesFailed, count($dailyImagesFailed) - $days, count($dailyImagesFailed), true);
            }

            $dates = array_merge(array_keys($dailyThumbnailAgentConnections), array_keys($dailyImagesUpdated), array_keys($dailyImagesFailed));

            foreach($dates as $date) {
                $row = array();
                $row['date'] = $date;
                $row['agentConnections'] = isset($dailyThumbnailAgentConnections[$date]) ? $dailyThumbnailAgentConnections[$date] : 0;
                $row['imagesUpdated'] = isset($dailyImagesUpdated[$date]) ? $dailyImagesUpdated[$date] : 0;
                $row['imagesFailed'] = isset($dailyImagesFailed[$date]) ? $dailyImagesFailed[$date] : 0;
                $result[] = $row;
            }
        }

        header('Content-type: application/json');

        echo json_encode($result);
    }


	public function addNewAccount($userName, $password)
	{
		$this->getLogger()->log(__METHOD__, NULL, LOG_ERR);

		//TODO: compare passwords

		$password = Helpers::getSaltedPasswordHash($password, Settings::getOauthLocalPasswordSalt());

		if($password != null)
		{
			$_SESSION['oauth_temp'] = array(
				'id' => $userName,
				'provider' => 'local',
				'password' => $password
			);
		}

		header("Location: " . Settings::getPanelUrl());
		exit;
	}



	public function tryLocalAuth($userName, $password)
	{
		$this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

		if($password === null)
		{
			//TODO: show error on user frontend
			header("Location: " . Settings::getPanelUrl());
			exit;
		}

		$oauth = $this->getOauthModel()->getById($userName);

		if (!$oauth instanceof Oauth) {
			Panel::addUserMessage('danger', 'Login failed. Please try again!');
			header("Location: " . Settings::getPanelUrl());
			exit;
		}

		$password = Helpers::getSaltedPasswordHash($password, Settings::getOauthLocalPasswordSalt());

		if($oauth->getPassword() == null || $password == null || $password !== $oauth->getPassword())
		{
			Panel::addUserMessage('danger', 'Login failed. Please try again!');
			header("Location: " . Settings::getPanelUrl());
			exit;
		}

		$_SESSION['oauth_temp'] = array(
			'id' => $oauth->getId(),
			'provider' => $oauth->getProvider(),
			'password' => $oauth->getPassword()
		);

		if(!isset($_SESSION['oauth']) && $_SESSION['oauth_temp']['id']) {
			$this->login($_SESSION['oauth_temp']);
		}else {
			Panel::addUserMessage('danger', 'Login failed. Please try again!');
		}

		header("Location: " . Settings::getPanelUrl());
		exit;
	}



	public function isValidUserName($userName)
	{
		$this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

		if(!$userName || empty($userName) || !preg_match('/^[a-z][a-z0-9]{5,20}$/', $userName))
		{
			return false;
		}else
		{
			return true;
		}
	}


	public function isValidPassword($password)
	{
		$this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

		/*
		Between Start -> ^
		And End -> $
		of the string there has to be at least one number -> (?=.*\d)
		and at least one letter -> (?=.*[A-Za-z])
		and it has to be a number, a letter or one of the following: !@#$% -> [0-9A-Za-z!@#$%]
		and there have to be 8-12 characters -> {8,50}
		*/

		if(!$password || empty($password) || !preg_match('/^(?=.*\d)(?=.*[A-Za-z])[0-9A-Za-z!@#$%;:-_]{8,50}$/', $password))
		{
			return false;
		}else
		{
			return true;
		}
	}


	public function isValidFirstName($firstName)
	{
		$this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

		if(!$firstName || empty($firstName) || !preg_match('/^[a-z][a-z0-9-.\s]{5,50}$/i', $firstName))
		{
            $this->getLogger()->log(__METHOD__, "invalid first name: " . $firstName, LOG_ERR);
			return false;
		}else
		{
			return true;
		}
	}


	public function isValidLastName($lastName)
	{
		$this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

		if(!$lastName || empty($lastName) || !preg_match('/^[a-z][a-z0-9-.\s]{5,50}$/i', $lastName))
		{
            $this->getLogger()->log(__METHOD__, "invalid last name: " . $lastName, LOG_ERR);
			return false;
		}else
		{
			return true;
		}
	}


	public function isValidEmail($email)
	{
		$this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

		if(!$email || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
		{
            $this->getLogger()->log(__METHOD__, "invalid email address: " . $email, LOG_ERR);
			return false;
		}else
		{
			return true;
		}
	}


	public static function addUserMessage($type, $msg)
	{
		@session_start();

		if(!isset($_SESSION['panel_messages']) || !is_array($_SESSION['panel_messages']))
		{
			$_SESSION['panel_messages'] = array();
		}
		$_SESSION['panel_messages'][] = array('type' => $type, 'msg' => $msg);
	}


	private function showUserMessages()
	{
		$this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

		if(isset($_SESSION['panel_messages']))
		{
			$this->smarty->assign('errors', $_SESSION['panel_messages']);
			unset($_SESSION['panel_messages']);
		}
	}


    public function showAllUserAgentsPage()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $this->redirectNoAdmin();

        $this->smarty->display('alluseragents.tpl');
    }


    public function showAllUserAgentsJson($draw, $start, $length, $search, $orderColumn, $orderDirection)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $result = array();
        $result['draw'] = $draw;
        $result['recordsTotal'] = 0;
        $result['recordsFiltered'] = 0;
        $result['data'] = array();

        if($this->isAdmin()) {
            $numUserAgents = $this->getUserAgentModel()->getNumUserAgents();
            $numUserAgentsFiltered = $this->getUserAgentModel()->getNumUserAgents(null, $search);

            $userAgents = $this->getUserAgentModel()->getUserAgents($orderColumn, $orderDirection, $length, $start, $search, null);

            $result['recordsTotal'] = intval($numUserAgents);
            $result['recordsFiltered'] = intval($numUserAgentsFiltered);

            foreach ($userAgents as $userAgent) {
                /** @var UserAgent $userAgent */

                $u = array();
                $u[] = $userAgent->getId();
                $u[] = $userAgent->getDescription();
                $u[] = date("d.m.Y H:i:s", $userAgent->getTsAdded());
                $u[] = date("d.m.Y H:i:s", $userAgent->getTsLastSeen());
                $u[] = $userAgent->getNumRequests();

                $result['data'][] = $u;
            }
        }

        header('Content-type: application/json');

        echo json_encode($result);
    }



    public function showUserAgentInfoPage($userAgentId)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $this->redirectNoAdmin();

        try {
            $userAgent = $this->getUserAgentModel()->getById($userAgentId);
            $this->smarty->assignByRef('userAgent', $userAgent);
        }catch (FrontendException $e)
        {
            $this->getLogger()->log(__METHOD__, "FrontendException: " . $e->getMessage(), LOG_ERR);
            $this->smarty->assign('error', $e->getMessage());
        }

        $this->smarty->display('useragentinfo.tpl');
    }


    public function showNumUserAgentReqestsDailyJson($userAgentId, $days)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $this->redirectNoAdmin();

        $result = array();

        try {
            $dailyNumRequests = $this->getUserAgentModel()->getNumRequestsDaily($userAgentId, $days);

            if (is_array($dailyNumRequests) && !empty($dailyNumRequests)) {
                foreach ($dailyNumRequests as $day => $num) {
                    $row['date'] = $day;
                    $row['value'] = $num;
                    $result[] = $row;
                }
            } else {
                $date = new \DateTime();

                $result[] = array('date' => $date->format('Y-m-d'), 'value' => 0);
            }
        } catch (\Exception $e) {
            $this->getLogger()->log(__METHOD__, "Exception: " . $e->getMessage(), LOG_ERR);
        }

        header('Content-type: application/json');

        echo json_encode($result);
    }



    public function showUserAgentInfoTargetsJson($draw, $start, $length, $search, $orderColumn, $orderDirection, $userAgentId)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $this->redirectNoAdmin();

        $result = array();
        $result['draw'] = $draw;
        $result['recordsTotal'] = 0;
        $result['recordsFiltered'] = 0;
        $result['data'] = array();

        $filteredTargets = $this->getTargetModel()->getUserAgentTargets($userAgentId, null, $orderColumn, $orderDirection, $length, $start, $search);

        $result['draw'] = $draw;
        $result['recordsTotal'] = intval($this->getTargetModel()->getNumUserAgentTargets($userAgentId, null, null));
        $result['recordsFiltered'] = intval($this->getTargetModel()->getNumUserAgentTargets($userAgentId, null, $search));
        $result['data'] = array();

        foreach ($filteredTargets as $target) {
            /** @var Target $target */

            $t = array();
            $t[] = $target->getId();
            $t[] = $target->getUrl();
            $t[] = date("d.m.Y H:i:s", $target->getTsAdded());
            $result['data'][] = $t;
        }

        header('Content-type: application/json');

        echo json_encode($result);
    }


    public function showTargetInfoUserAgentsJson($draw, $start, $length, $search, $orderColumn, $orderDirection, $targetId)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        //TODO: wie erfahre ich, ob ein Account über Referrer berechtigt ist, das Target zu sehen?

        $result = array();
        $result['draw'] = $draw;
        $result['recordsTotal'] = 0;
        $result['recordsFiltered'] = 0;
        $result['data'] = array();

        if($this->isAccount()) {
            if ($this->isAdminMode()) {

                $userAgents = $this->getUserAgentModel()->getTargetUserAgents($targetId, ($this->isAdminMode() ? NULL : $this->account->getId()), $orderColumn, $orderDirection, $length, $start, $search);

                $result['recordsTotal'] = intval($this->getUserAgentModel()->getNumUserAgents($targetId, null, null));
                $result['recordsFiltered'] = intval($this->getUserAgentModel()->getNumUserAgents($targetId, $search, null));

                foreach ($userAgents as $userAgent) {
                    /** @var UserAgent $userAgent */

                    $u = array();
                    $u[] = $userAgent->getId();
                    $u[] = $userAgent->getDescription();
                    $u[] = date("d.m.Y H:i:s", $userAgent->getTsAdded());
                    $result['data'][] = $u;
                }
            }
        }

        header('Content-type: application/json');

        echo json_encode($result);
    }


    public function showTargetHostsBlacklistPage()
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $this->redirectNoAdmin();

        $this->smarty->display('targethostsblacklist.tpl');
    }


    public function showTargetHostsBlacklistJson($draw, $start, $length, $search, $orderColumn, $orderDirection)
    {
        $this->getLogger()->log(__METHOD__, NULL, LOG_DEBUG);

        $result = array();
        $result['draw'] = $draw;
        $result['recordsTotal'] = 0;
        $result['recordsFiltered'] = 0;
        $result['data'] = array();


        if($this->isAdmin()) {
            $numTargetsBlacklisted = $this->getTargetModel()->getNumTargetHostsBlacklisted();
            $numTargetsBlacklistedFiltered = $this->getTargetModel()->getNumTargetHostsBlacklisted($search);

            $targetsBlacklisted = $this->getTargetModel()->getTargetHostsBlacklisted($orderColumn, $orderDirection, $length, $start, $search);

            $result['recordsTotal'] = intval($numTargetsBlacklisted);
            $result['recordsFiltered'] = intval($numTargetsBlacklistedFiltered);

            foreach ($targetsBlacklisted as $targetBlacklisted) {
                /** @var TargetHostBlacklisted $targetBlacklisted */

                $t = array();
                $t[] = $targetBlacklisted->getId();
                $t[] = $targetBlacklisted->getType();
                $t[] = $targetBlacklisted->getHost();
                $t[] = $targetBlacklisted->getComment();
                $result['data'][] = $t;
            }
        }

        header('Content-type: application/json');

        echo json_encode($result);
    }
}
