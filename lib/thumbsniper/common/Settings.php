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



abstract class Settings
{
    // Maintenance
    static private $maintenance;

	// Performance
	static private $energySaveActive;

    // MySQL
    static private $mysqlHost;
    static private $mysqlUser;
    static private $mysqlPass;
    static private $mysqlDb;

    // MongoDB
    static private $mongoHost;
    static private $mongoPort = 27017;
    static private $mongoUser;
    static private $mongoPass;
    static private $mongoDb;

	// Redis
	/** @var string */
	static private $redisScheme = 'tcp';
	/** @var string */
	static private $redisHost;
	/** @var int */
	static private $redisPort = 6379;
	/** @var string */
	static private $redisDb;

    // GoogleAuth
    static private $googleAuthEnabled = false;
    static private $googleAuthUrl;
    static private $googleClientId;
    static private $googleClientSecret;

    // TwitterAuth
    static private $twitterAuthEnabled = false;
    static private $twitterAuthRedirectUrl;
    static private $twitterAuthCallbackUrl;
    static private $twitterConsumerKey;
    static private $twitterConsumerSecret;
    static private $twitterAccessToken;
    static private $twitterAccessTokenSecret;

    // User Agent
    static private $userAgentName;
    static private $userAgentUrl;

    // Piwik Tracking
    static private $piwikTrackingEnabled;
    static private $piwikUrl;
    static private $piwikSiteId;
    static private $piwikTokenAuth;

    // Logging
    static private $logSeverity = LOG_INFO;
    static private $logToApache = true;
    static private $logToFile = false;
    static private $logFile;
    static private $logMethod_thumbsniper_account_AccountModel;
    static private $logMethod_thumbsniper_api_ApiV2;
    static private $logMethod_thumbsniper_api_ApiTasks;
    static private $logMethod_thumbsniper_common_LockableModel;
    static private $logMethod_thumbsniper_common_Logger;
    static private $logMethod_thumbsniper_objective_ImageModel;
    static private $logMethod_thumbsniper_objective_ReferrerModel;
    static private $logMethod_thumbsniper_objective_TargetModel;

    // Web
    static private $webUrl;

    // Panel
    static private $panelTitle;
    static private $panelUrl;

    // Frontend
    static private $frontendImagesUrl;
    static private $frontendImageHosts;

    static private $frontendImagesPathWatermark = "/static/thumbsniper.png";
    static private $frontendImagesPathViolation = "/static/violation.png";
    static private $frontendImagesPathBroken = "/static/broken.png";
    static private $frontendImagesPathDummy = "/static/dummy.png";
    static private $frontendImagesPathRobots = "/static/robots.png";
    static private $frontendImagesPathTransparentPixel = "/static/transparent-pixel.png";

    static private $frontendAdminEmail;

    // MongoDB queues
    static private $mongoCollectionQueueJobsMasters = "queue_jobs_masters";
    static private $mongoCollectionQueueJobsThumbnails = "queue_jobs_thumbnails";

    // Misc
    static private $httpProxyUrl;
    static private $domain;
    static private $imageWatermarksEnabled;
    static private $targetMaxTries = 4;
    static private $imageMaxAgeVariance = 10;
    static private $robotsCheckMaxAge = 2592000; // 30 days
    static private $robotsMaxAgeVariance = 10;
    
    static private $targetLastFailExpiry = 2592000; // 30 days

    static private $targetDefaultPriority = 10;
    static private $targetPriorities = array(
        'member' => 20,
        'advanced' => 30,
        'professional' => 40,
        'dedicated' => 50
    );

    static private $imageDefaultMaxAge = 2592000; // 30 days
    static private $imageMaxAges = array(
        'member' => 2592000, // 30 days
        'advanced' => 2592000, // 30 days
        'professional' => 1296000, // 15 days
        'dedicated' => 864000 // 10 days
    );

    static private $accountDefaultMaxDailyRequests = 500;
    static private $accountMaxDailyRequests = array(
        'member' => 1000,
        'advanced' => 5000,
        'professional' => 50000,
        'dedicated' => null
    );

    static private $checkoutExpire = 300; // 5 minutes
    static private $weaponCutycapt = "cutycapt";
    static private $weaponWkhtml = "wkhtml";
    static private $domainVerificationExpire = 2592000; // 30 days
    static private $agentMaxSleepDuration = 10; // 10 seconds
    static private $storeUserAgents = false;

    // API
    static private $apiHost;
    static private $apiKeyExpire;
    static private $apiKeyDefaultType = 'member';
    static private $apiValidVersions = array(1);
    static private $apiValidActions = array(
        'thumbnail',
        'cachedImage',
        'jobMasterNormal',
        'jobMasterLongrun',
        'jobImage',
        'masterCommit',
        'thumbnailsCommit',
        'masterFailure',
        'imageFailure',
        'initDummies'
    );
    static private $apiAgentSecret;

//    // 1st row: ThumbSniper new, 2nd row: ThumbSniper old, 3rd row: compatibility with m-software and fadeout
//    static private $apiValidWidths = array( // min 110 pixel (Watermark + 2x 5px border)
//        120, 150, 180, 210, 240, 270, 300, 330, 360, 390, 420, 450, 500, 600, 800,
//        91, 104, 121, 146, 182, 242, 365,
//        90, 123, 148, 185, 225, 246, 370,
//        80, 133, 200, 267, 400,
//        119, 143, 178, 238, 356,
//        555
//    );

    static private $apiValidWidths = array( // min 110 pixel (Watermark + 2x 5px border)
        90, 120, 150, 180, 200, 300
    );

    static private $imageEffects = array(
        'plain' => 'jpeg',
        'button1' => 'png',
        'curly' => 'png',
        'blur1' => 'png',
        'blur2' => 'png',
        'tornpaper1' => 'png',
        'polaroid1' => 'png'
    );

    static private $imageEffectsExtra = array(
        'fade1' => array('png', false),
        'fade2' => array('png', false)
    );

    static private $masterFiletype = 'png';

    static private $apiKeyOrReferrerWhitelistOnly = true;
	static private $oauthLocalPasswordSalt = "secret";

    // MAIL
    static private $mailFromName;
    static private $mailFromAddress;
    static private $mailSubjectPrefix;
    static private $mailSmtpHost;
    static private $mailSmtpPort;
    static private $mailUser;
    static private $mailPassword;

    // MD5 id prefixes (doesn't mean anything, it's just some text)
    static private $accountIdPrefix = "account:username:";
    static private $targetIdPrefix = "target:url:";
    static private $imageIdPrefix = "image:targetId:width:effect:";
    static private $referrerIdPrefix = "referrer:urlBase:";
    static private $referrerDeeplinkIdPrefix = "referrerDeeplink:url:";

    // EXPIRY

	static private $redisMasterImageExpire = 3600; // 1 hour until master image expires
    //TODO: use separate expire property for Amazon S3 (don't use the redis cache expire anymore)
	static private $redisImageCacheExpire = 28800; // 8 hours
    static private $amazonS3presignedUrlExpireSeconds = 28800; // 8 hours
    static private $amazonS3presignedUrlExpireStr = '+8 hours'; // must match $redisImageCacheExpire

    // LOCAL STORAGE
    /** @var bool */
    static private $localThumbnailStorageEnabled = true;
    
    // AMAZON S3
    /** @var bool */
    static private $amazonS3enabled = false;
    static private $amazonS3region;
    static private $amazonS3credentialsKey;
    static private $amazonS3credentialsSecret;
    static private $amazonS3credentialsSignature;
    static private $amazonS3bucketThumbnails;
    

    // OAUTH
    //MongoDB
    static private $mongoKeyOauthAttrId = "id";
    static private $mongoKeyOauthAttrProvider = "provider";
    static private $mongoKeyOauthAttrAccessToken = "accessToken";
    static private $mongoKeyOauthAttrAccessTokenSecret = "accessTokenSecret";
    static private $mongoKeyOauthAttrAccessTokenType = "accessTokenType";
    static private $mongoKeyOauthAttrAccessTokenExpiry = "accessTokenExpiry";
    static private $mongoKeyOauthAttrRefreshToken = "refreshToken";
    static private $mongoKeyOauthAttrScreenName = "screenName";
	static private $mongoKeyOauthAttrPassword = "password";
    static private $mongoKeyOauthAttrTsAdded = "tsAdded";
    static private $mongoKeyOauthAttrTsLastUpdated = "tsLastUpdated";

    // ACCOUNT
    static private $mongoCollectionAccounts = "accounts";
    static private $mongoKeyAccountAttrId = "_id";
    static private $mongoKeyAccountAttrFirstName = "firstName";
    static private $mongoKeyAccountAttrLastName = "lastName";
    static private $mongoKeyAccountAttrEmail = "eMail";
    static private $mongoKeyAccountAttrEmailVerified = "eMailVerified";
    static private $mongoKeyAccountAttrTsAdded = "tsAdded";
    static private $mongoKeyAccountAttrActive = "active";
    static private $mongoKeyAccountAttrApiKey = "apiKey";
    static private $mongoKeyAccountAttrApiKeyType = "apiKeyType";
    static private $mongoKeyAccountAttrApiKeyTsAdded = "apiKeyTsAdded";
    static private $mongoKeyAccountAttrApiKeyTsExpiry = "apiKeyTsExpiry";
    static private $mongoKeyAccountAttrApiKeyActive = "apiKeyActive";
    static private $mongoKeyAccountAttrDomainVerificationKey = "domainVerificationKey";
    static private $mongoKeyAccountAttrWhitelistActive = "whitelistActive";
    static private $mongoKeyAccountAttrNumRequestsDaily = "numRequestsDaily";


    // REFERRER
    static private $mongoCollectionReferrers = "referrers";
    static private $mongoCollectionReferrersBlacklist = "referrers_blacklist";
    static private $mongoKeyReferrerAttrId = "_id";
    static private $mongoKeyReferrerAttrUrlBase = "urlBase";
    static private $mongoKeyReferrerAttrAccountId = "accountId";
    static private $mongoKeyReferrerAttrTsAdded = "tsAdded";
    static private $mongoKeyReferrerAttrTsLastSeen = "tsLastSeen";
    static private $mongoKeyReferrerAttrTsLastUpdated = "tsLastUpdated";
    static private $mongoKeyReferrerAttrBlacklisted = "blacklisted";
    static private $mongoKeyReferrerAttrTsDomainVerification = "tsDomainVerification";
    static private $mongoKeyReferrerAttrNumRequests = "numRequests";
    static private $mongoKeyReferrerAttrNumRequestsDaily = "numRequestsDaily";

    //REFERRER DEEPLINKS
    static private $mongoCollectionReferrerDeeplinks = "referrer_deeplinks";
    static private $mongoKeyReferrerDeeplinkAttrId = "_id";
    static private $mongoKeyReferrerDeeplinkAttrReferrerId = "referrerId";
    static private $mongoKeyReferrerDeeplinkAttrUrl = "url";
    static private $mongoKeyReferrerDeeplinkAttrTsAdded = "tsAdded";
    static private $mongoKeyReferrerDeeplinkAttrTsLastSeen = "tsLastSeen";
    static private $mongoKeyReferrerDeeplinkAttrNumRequests = "numRequests";


    // TARGET
    static private $mongoCollectionTargets = "targets";
    static private $mongoKeyTargetAttrId = "_id";
    static private $mongoKeyTargetAttrUrl = "url";
    static private $mongoKeyTargetAttrFileNameBase = "fileNameBase";
    static private $mongoKeyTargetAttrFileNameSuffix = "fileNameSuffix";
    static private $mongoKeyTargetAttrTsAdded = "tsAdded";
    static private $mongoKeyTargetAttrTsLastUpdated = "tsLastUpdated";
    static private $mongoKeyTargetAttrTsCheckedOut = "tsCheckedOut";
    static private $mongoKeyTargetAttrTsLastFailed = "tsLastFailed";
    static private $mongoKeyTargetAttrCounterCheckedOut = "counterCheckedOut";
    static private $mongoKeyTargetAttrCounterUpdated = "counterUpdated";
    static private $mongoKeyTargetAttrCounterFailed = "counterFailed";
    static private $mongoKeyTargetAttrJavaScriptEnabled = "javaScriptEnabled";
    static private $mongoKeyTargetAttrTsRobotsCheck = "tsRobotsCheck";
    static private $mongoKeyTargetAttrRobotsAllowed = "robotsAllowed";
    static private $mongoKeyTargetAttrSnipeDuration = "snipeDuration";
    static private $mongoKeyTargetAttrWeapon = "weapon";
    static private $mongoKeyTargetAttrForcedUpdate = "forcedUpdate";
    static private $mongoKeyTargetAttrTsLastRequested = "tsLastRequested";
    static private $mongoKeyTargetAttrNumRequests = "numRequests";
	static private $mongoKeyTargetAttrLastErrorMessage = "lastErrorMessage";
	static private $mongoKeyTargetAttrCensored = "censored";
    static private $mongoKeyTargetAttrMimeType = "mimeType";

	static private $redisKeyTargetMasterImageData = "transient:key:target:cache:data:"; // . targetId

    // TARGETS BLACKLIST
    static private $mongoCollectionTargetHostsBlacklist = "targets_blacklist";
    static private $mongoKeyTargetHostsBlacklistAttrId = "_id";
    static private $mongoKeyTargetHostsBlacklistAttrHost = "host";
    static private $mongoKeyTargetHostsBlacklistAttrType = "type";
    static private $mongoKeyTargetHostsBlacklistAttrComment = "comment";

    // IMAGE
    static private $mongoCollectionImages = "images";
    static private $mongoKeyImageAttrId = "_id";
    static private $mongoKeyImageAttrTargetId = "targetId";
    static private $mongoKeyImageAttrWidth = "width";
    static private $mongoKeyImageAttrHeight = "height";
    static private $mongoKeyImageAttrEffect = "effect";
    static private $mongoKeyImageAttrFileNameSuffix = "fileNameSuffix";
    static private $mongoKeyImageAttrTsAdded = "tsAdded";
    static private $mongoKeyImageAttrTsLastUpdated = "tsLastUpdated";
    static private $mongoKeyImageAttrTsLastRequested = "tsLastRequested";
    static private $mongoKeyImageAttrTsCheckedOut = "tsCheckedOut";
    static private $mongoKeyImageAttrCounterCheckedOut = "counterCheckedOut";
    static private $mongoKeyImageAttrCounterUpdated = "counterUpdated";
    static private $mongoKeyImageAttrNumRequests = "numRequests";
    static private $mongoKeyImageAttrNumRequestsDaily = "numRequestsDaily";
    static private $mongoKeyImageAttrLocalPath = "localPath";
    static private $mongoKeyImageAttrAmazonS3url = "amazonS3url";
    
	static private $redisKeyImageCacheData = "transient:key:image:cache:data:"; // . $imageId
	static private $redisKeyImageCacheKeyBranded = "transient:key:image:cache:key:branded:"; // + imageId
	static private $redisKeyImageCacheKeyUnbranded = "transient:key:image:cache:key:unbranded:"; // + imageId

    //TODO: differ between branded and unbranded
    static private $redisKeyImageAmazonS3url = "transient:key:image:amazons3url:"; // . $targetId . $imageId
    
    // USERAGENT
    static private $mongoCollectionUserAgents = "useragents";
    static private $mongoCollectionUserAgentsBlacklist = "useragents_blacklist";
    static private $mongoKeyUserAgentAttrId = "_id";
    static private $mongoKeyUserAgentAttrDescription = "description";
    static private $mongoKeyUserAgentAttrTsAdded = "tsAdded";
    static private $mongoKeyUserAgentAttrTsLastUpdated = "tsLastUpdated";
    static private $mongoKeyUserAgentAttrTsLastSeen = "tsLastSeen";
    static private $mongoKeyUserAgentAttrBlacklisted = "blacklisted";
    static private $mongoKeyUserAgentAttrNumRequests = "numRequests";
    static private $mongoKeyUserAgentAttrNumRequestsDaily = "numRequestsDaily";

    // STATISTICS
    static private $mongoCollectionStatistics = "statistics";
    
    // CONFIGURATION
    static private $redisKeyAgentLastSleepDurationPrefix = "agent:lastsleepduration:";

    /**
     * @return mixed
     */
    public static function getDomain()
    {
        return self::$domain;
    }

    /**
     * @param mixed $domain
     */
    public static function setDomain($domain)
    {
        self::$domain = $domain;
    } // . $cacheKey

    /**
     * @return mixed
     */
    public static function getApiHost()
    {
        return self::$apiHost;
    }

    /**
     * @param mixed $apiHost
     */
    public static function setApiHost($apiHost)
    {
        self::$apiHost = $apiHost;
    }

    /**
     * @return mixed
     */
    public static function getApiKeyDefaultType()
    {
        return self::$apiKeyDefaultType;
    }

    /**
     * @param mixed $apiKeyDefaultType
     */
    public static function setApiKeyDefaultType($apiKeyDefaultType)
    {
        self::$apiKeyDefaultType = $apiKeyDefaultType;
    }

    /**
     * @return mixed
     */
    public static function getApiKeyExpire()
    {
        return self::$apiKeyExpire;
    }

    /**
     * @param mixed $apiKeyExpire
     */
    public static function setApiKeyExpire($apiKeyExpire)
    {
        self::$apiKeyExpire = $apiKeyExpire;
    }

    /**
     * @return mixed
     */
    public static function getApiValidActions()
    {
        return self::$apiValidActions;
    }

    /**
     * @return mixed
     */
    public static function getApiValidVersions()
    {
        return self::$apiValidVersions;
    }

    /**
     * @return mixed
     */
    public static function getApiValidWidths()
    {
        return self::$apiValidWidths;
    }

    /**
     * @return mixed
     */
    public static function getFrontendImageHosts()
    {
        return self::$frontendImageHosts;
    }

    /**
     * @param mixed $frontendImageHosts
     */
    public static function setFrontendImageHosts(array $frontendImageHosts)
    {
        self::$frontendImageHosts = $frontendImageHosts;
    }

    /**
     * @return mixed
     */
    public static function getFrontendImagesUrl()
    {
        return self::$frontendImagesUrl;
    }

    /**
     * @param mixed $frontendImagesUrl
     */
    public static function setFrontendImagesUrl($frontendImagesUrl)
    {
        self::$frontendImagesUrl = $frontendImagesUrl;
    }

    /**
     * @return mixed
     */
    public static function getFrontendAdminEmail()
    {
        return self::$frontendAdminEmail;
    }

    /**
     * @param mixed $frontendAdminEmail
     */
    public static function setFrontendAdminEmail($frontendAdminEmail)
    {
        self::$frontendAdminEmail = $frontendAdminEmail;
    }

    /**
     * @return mixed
     */
    public static function getGoogleAuthUrl()
    {
        return self::$googleAuthUrl;
    }

    /**
     * @param mixed $googleAuthUrl
     */
    public static function setGoogleAuthUrl($googleAuthUrl)
    {
        self::$googleAuthUrl = $googleAuthUrl;
    }

    /**
     * @return mixed
     */
    public static function getGoogleClientId()
    {
        return self::$googleClientId;
    }

    /**
     * @param mixed $googleClientId
     */
    public static function setGoogleClientId($googleClientId)
    {
        self::$googleClientId = $googleClientId;
    }

    /**
     * @return mixed
     */
    public static function getGoogleClientSecret()
    {
        return self::$googleClientSecret;
    }

    /**
     * @param mixed $googleClientSecret
     */
    public static function setGoogleClientSecret($googleClientSecret)
    {
        self::$googleClientSecret = $googleClientSecret;
    }

    /**
     * @return mixed
     */
    public static function getLogMethodThumbSniperAccountAccountModel()
    {
        return self::$logMethod_thumbsniper_account_AccountModel;
    }

    /**
     * @param mixed $logMethod_thumbsniper_account_AccountModel
     */
    public static function setLogMethodThumbSniperAccountAccountModel($logMethod_thumbsniper_account_AccountModel)
    {
        self::$logMethod_thumbsniper_account_AccountModel = $logMethod_thumbsniper_account_AccountModel;
    }

    /**
     * @return mixed
     */
    public static function getLogMethodThumbSniperApiApiV2()
    {
        return self::$logMethod_thumbsniper_api_ApiV2;
    }

    /**
     * @param mixed $logMethod_thumbsniper_api_ApiV2
     */
    public static function setLogMethodThumbSniperApiApiV2($logMethod_thumbsniper_api_ApiV2)
    {
        self::$logMethod_thumbsniper_api_ApiV2 = $logMethod_thumbsniper_api_ApiV2;
    }

    /**
     * @return mixed
     */
    public static function getLogMethodThumbSniperApiApiTasks()
    {
        return self::$logMethod_thumbsniper_api_ApiTasks;
    }

    /**
     * @param mixed $logMethod_thumbsniper_api_ApiTasks
     */
    public static function setLogMethodThumbSniperApiApiTasks($logMethod_thumbsniper_api_ApiTasks)
    {
        self::$logMethod_thumbsniper_api_ApiTasks = $logMethod_thumbsniper_api_ApiTasks;
    }

    /**
     * @return mixed
     */
    public static function getLogMethodThumbSniperCommonLockableModel()
    {
        return self::$logMethod_thumbsniper_common_LockableModel;
    }

    /**
     * @param mixed $logMethod_thumbsniper_common_LockableModel
     */
    public static function setLogMethodThumbSniperCommonLockableModel($logMethod_thumbsniper_common_LockableModel)
    {
        self::$logMethod_thumbsniper_common_LockableModel = $logMethod_thumbsniper_common_LockableModel;
    }

    /**
     * @return mixed
     */
    public static function getLogMethodThumbSniperCommonLogger()
    {
        return self::$logMethod_thumbsniper_common_Logger;
    }

    /**
     * @param mixed $logMethod_thumbsniper_common_Logger
     */
    public static function setLogMethodThumbSniperCommonLogger($logMethod_thumbsniper_common_Logger)
    {
        self::$logMethod_thumbsniper_common_Logger = $logMethod_thumbsniper_common_Logger;
    }

    /**
     * @return mixed
     */
    public static function getLogMethodThumbSniperObjectiveImageModel()
    {
        return self::$logMethod_thumbsniper_objective_ImageModel;
    }

    /**
     * @param mixed $logMethod_thumbsniper_objective_ImageModel
     */
    public static function setLogMethodThumbSniperObjectiveImageModel($logMethod_thumbsniper_objective_ImageModel)
    {
        self::$logMethod_thumbsniper_objective_ImageModel = $logMethod_thumbsniper_objective_ImageModel;
    }

    /**
     * @return mixed
     */
    public static function getLogMethodThumbSniperObjectiveReferrerModel()
    {
        return self::$logMethod_thumbsniper_objective_ReferrerModel;
    }

    /**
     * @param mixed $logMethod_thumbsniper_objective_ReferrerModel
     */
    public static function setLogMethodThumbSniperObjectiveReferrerModel($logMethod_thumbsniper_objective_ReferrerModel)
    {
        self::$logMethod_thumbsniper_objective_ReferrerModel = $logMethod_thumbsniper_objective_ReferrerModel;
    }

    /**
     * @return mixed
     */
    public static function getLogMethodThumbSniperObjectiveTargetModel()
    {
        return self::$logMethod_thumbsniper_objective_TargetModel;
    }

    /**
     * @param mixed $logMethod_thumbsniper_objective_TargetModel
     */
    public static function setLogMethodThumbSniperObjectiveTargetModel($logMethod_thumbsniper_objective_TargetModel)
    {
        self::$logMethod_thumbsniper_objective_TargetModel = $logMethod_thumbsniper_objective_TargetModel;
    }

    /**
     * @return mixed
     */
    public static function getLogSeverity()
    {
        return self::$logSeverity;
    }

    /**
     * @param mixed $logSeverity
     */
    public static function setLogSeverity($logSeverity)
    {
        self::$logSeverity = $logSeverity;
    }

    /**
     * @return mixed
     */
    public static function getLogToApache()
    {
        return self::$logToApache;
    }

    /**
     * @param mixed $logToApache
     */
    public static function setLogToApache($logToApache)
    {
        self::$logToApache = $logToApache;
    }

    /**
     * @return mixed
     */
    public static function getLogFile()
    {
        return self::$logFile;
    }



    /**
     * @return mixed
     */
    public static function getLogToFile()
    {
        return self::$logToFile;
    }

    /**
     * @param mixed $logToFile
     */
    public static function setLogToFile($logToFile)
    {
        self::$logToFile = $logToFile;
    }

    /**
     * @return mixed
     */
    public static function getMaintenance()
    {
        return self::$maintenance;
    }

    /**
     * @param mixed $maintenance
     */
    public static function setMaintenance($maintenance)
    {
        self::$maintenance = $maintenance;
    }

    /**
     * @return mixed
     */
    public static function getMysqlDb()
    {
        return self::$mysqlDb;
    }

    /**
     * @param mixed $mysqlDb
     */
    public static function setMysqlDb($mysqlDb)
    {
        self::$mysqlDb = $mysqlDb;
    }

    /**
     * @return mixed
     */
    public static function getMysqlHost()
    {
        return self::$mysqlHost;
    }

    /**
     * @param mixed $mysqlHost
     */
    public static function setMysqlHost($mysqlHost)
    {
        self::$mysqlHost = $mysqlHost;
    }

    /**
     * @return mixed
     */
    public static function getMysqlPass()
    {
        return self::$mysqlPass;
    }

    /**
     * @param mixed $mysqlPass
     */
    public static function setMysqlPass($mysqlPass)
    {
        self::$mysqlPass = $mysqlPass;
    }

    /**
     * @return mixed
     */
    public static function getMysqlUser()
    {
        return self::$mysqlUser;
    }

    /**
     * @param mixed $mysqlUser
     */
    public static function setMysqlUser($mysqlUser)
    {
        self::$mysqlUser = $mysqlUser;
    }

    /**
     * @return mixed
     */
    public static function getPiwikSiteId()
    {
        return self::$piwikSiteId;
    }

    /**
     * @param mixed $piwikSiteId
     */
    public static function setPiwikSiteId($piwikSiteId)
    {
        self::$piwikSiteId = $piwikSiteId;
    }

    /**
     * @return mixed
     */
    public static function getPiwikTokenAuth()
    {
        return self::$piwikTokenAuth;
    }

    /**
     * @param mixed $piwikTokenAuth
     */
    public static function setPiwikTokenAuth($piwikTokenAuth)
    {
        self::$piwikTokenAuth = $piwikTokenAuth;
    }

    /**
     * @return mixed
     */
    public static function getPiwikTrackingEnabled()
    {
        return self::$piwikTrackingEnabled;
    }

    /**
     * @param mixed $piwikTrackingEnabled
     */
    public static function setPiwikTrackingEnabled($piwikTrackingEnabled)
    {
        self::$piwikTrackingEnabled = $piwikTrackingEnabled;
    }

    /**
     * @return mixed
     */
    public static function getPiwikUrl()
    {
        return self::$piwikUrl;
    }

    /**
     * @param mixed $piwikUrl
     */
    public static function setPiwikUrl($piwikUrl)
    {
        self::$piwikUrl = $piwikUrl;
    }


    /**
     * @return string
     */
    public static function getFrontendImagesPathBroken()
    {
        return self::$frontendImagesPathBroken;
    }

    /**
     * @return string
     */
    public static function getFrontendImagesPathDummy()
    {
        return self::$frontendImagesPathDummy;
    }

    /**
     * @return string
     */
    public static function getFrontendImagesPathRobots()
    {
        return self::$frontendImagesPathRobots;
    }

    /**
     * @return string
     */
    public static function getFrontendImagesPathViolation()
    {
        return self::$frontendImagesPathViolation;
    }

    /**
     * @return string
     */
    public static function getFrontendImagesPathWatermark()
    {
        return self::$frontendImagesPathWatermark;
    }

    /**
     * @return boolean
     */
    public static function isImageWatermarksEnabled()
    {
        return self::$imageWatermarksEnabled;
    }


    /**
     * @param boolean $imageWatermarksEnabled
     */
    public static function setImageWatermarksEnabled($imageWatermarksEnabled)
    {
        self::$imageWatermarksEnabled = $imageWatermarksEnabled;
    }


    /**
     * @return boolean
     */
    public static function isApiKeyOrReferrerWhitelistOnly()
    {
        return self::$apiKeyOrReferrerWhitelistOnly;
    }

    /**
     * @param boolean $apiKeyOrReferrerWhitelistOnly
     */
    public static function setApiKeyOrReferrerWhitelistOnly($apiKeyOrReferrerWhitelistOnly)
    {
        self::$apiKeyOrReferrerWhitelistOnly = $apiKeyOrReferrerWhitelistOnly;
    }

    /**
     *
     * @param mixed $effect
     *
     * @return string
     */
    public static function getImageFiletype($effect)
    {
        if(array_key_exists($effect, self::getImageEffects()))
        {
            return self::getImageEffects()[$effect];
        }

        if(array_key_exists($effect, self::getImageEffectsExtra()))
        {
            if(self::getImageEffectsExtra()[$effect][1] == true)
            {
                return self::getImageEffectsExtra()[$effect][0];
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public static function getMasterFiletype()
    {
        return self::$masterFiletype;
    }



    /**
     * @return int
     */
    public static function getTargetMaxTries()
    {
        return self::$targetMaxTries;
    }

    /**
     * @return int
     */
    public static function getCheckoutExpire()
    {
        return self::$checkoutExpire;
    }

    /**
     * @return int
     */
    public static function getImageMaxAgeVariance()
    {
        return self::$imageMaxAgeVariance;
    }


    /**
     * @return string
     */
    public static function getWeaponCutycapt()
    {
        return self::$weaponCutycapt;
    }

    /**
     * @return string
     */
    public static function getWeaponWkhtml()
    {
        return self::$weaponWkhtml;
    }


    /**
     * @return int
     */
    public static function getDomainVerificationExpire()
    {
        return self::$domainVerificationExpire;
    }

    /**
     * @return mixed
     */
    public static function getUserAgentName()
    {
        return self::$userAgentName;
    }

    /**
     * @param mixed $userAgentName
     */
    public static function setUserAgentName($userAgentName)
    {
        self::$userAgentName = $userAgentName;
    }

    /**
     * @return mixed
     */
    public static function getUserAgentUrl()
    {
        return self::$userAgentUrl;
    }

    /**
     * @param mixed $userAgentUrl
     */
    public static function setUserAgentUrl($userAgentUrl)
    {
        self::$userAgentUrl = $userAgentUrl;
    }

    /**
     * @return string
     */
    public static function getUserAgent()
    {
        if(empty(self::$userAgentName) || empty(self::$userAgentUrl))
        {
            return false;
        }else
        {
            return self::$userAgentName . " (" . self::$userAgentUrl . ")";
        }
    }


    /**
     * @return int
     */
    public static function getRobotsCheckMaxAge()
    {
        return self::$robotsCheckMaxAge;
    }

    /**
     * @return int
     */
    public static function getRobotsMaxAgeVariance()
    {
        return self::$robotsMaxAgeVariance;
    }

    /**
     * @return int
     */
    public static function getTargetLastFailExpiry()
    {
        return self::$targetLastFailExpiry;
    }

    /**
     * @return mixed
     */
    public static function getWebUrl()
    {
        return self::$webUrl;
    }

    /**
     * @param mixed $webUrl
     */
    public static function setWebUrl($webUrl)
    {
        self::$webUrl = $webUrl;
    }

    /**
     * @return mixed
     */
    public static function getPanelTitle()
    {
        return self::$panelTitle;
    }

    /**
     * @param mixed $panelTitle
     */
    public static function setPanelTitle($panelTitle)
    {
        self::$panelTitle = $panelTitle;
    }

    /**
     * @return mixed
     */
    public static function getPanelUrl()
    {
        return self::$panelUrl;
    }

    /**
     * @param mixed $panelUrl
     */
    public static function setPanelUrl($panelUrl)
    {
        self::$panelUrl = $panelUrl;
    }


    /**
     * @return mixed
     */
    public static function getTwitterConsumerKey()
    {
        return self::$twitterConsumerKey;
    }

    /**
     * @param mixed $twitterConsumerKey
     */
    public static function setTwitterConsumerKey($twitterConsumerKey)
    {
        self::$twitterConsumerKey = $twitterConsumerKey;
    }

    /**
     * @return mixed
     */
    public static function getTwitterConsumerSecret()
    {
        return self::$twitterConsumerSecret;
    }

    /**
     * @param mixed $twitterConsumerSecret
     */
    public static function setTwitterConsumerSecret($twitterConsumerSecret)
    {
        self::$twitterConsumerSecret = $twitterConsumerSecret;
    }

    /**
     * @return mixed
     */
    public static function getTwitterAccessToken()
    {
        return self::$twitterAccessToken;
    }

    /**
     * @param mixed $twitterAccessToken
     */
    public static function setTwitterAccessToken($twitterAccessToken)
    {
        self::$twitterAccessToken = $twitterAccessToken;
    }

    /**
     * @return mixed
     */
    public static function getTwitterAccessTokenSecret()
    {
        return self::$twitterAccessTokenSecret;
    }

    /**
     * @param mixed $twitterAccessTokenSecret
     */
    public static function setTwitterAccessTokenSecret($twitterAccessTokenSecret)
    {
        self::$twitterAccessTokenSecret = $twitterAccessTokenSecret;
    }


    /**
     * @return mixed
     */
    public static function getTwitterAuthRedirectUrl()
    {
        return self::$twitterAuthRedirectUrl;
    }

    /**
     * @return mixed
     */
    public static function getTwitterAuthCallbackUrl()
    {
        return self::$twitterAuthCallbackUrl;
    }

    /**
     * @param mixed $twitterAuthRedirectUrl
     */
    public static function setTwitterAuthRedirectUrl($twitterAuthRedirectUrl)
    {
        self::$twitterAuthRedirectUrl = $twitterAuthRedirectUrl;
    }

    /**
     * @param mixed $twitterAuthCallbackUrl
     */
    public static function setTwitterAuthCallbackUrl($twitterAuthCallbackUrl)
    {
        self::$twitterAuthCallbackUrl = $twitterAuthCallbackUrl;
    }


    /**
     * @return mixed
     */
    public static function getMailFromAddress()
    {
        return self::$mailFromAddress;
    }

    /**
     * @param mixed $mailFromAddress
     */
    public static function setMailFromAddress($mailFromAddress)
    {
        self::$mailFromAddress = $mailFromAddress;
    }

    /**
     * @return mixed
     */
    public static function getMailFromName()
    {
        return self::$mailFromName;
    }

    /**
     * @param mixed $mailFromName
     */
    public static function setMailFromName($mailFromName)
    {
        self::$mailFromName = $mailFromName;
    }


    /**
     * @return mixed
     */
    public static function getMailSubjectPrefix()
    {
        return self::$mailSubjectPrefix;
    }

    /**
     * @param mixed $mailSubjectPrefix
     */
    public static function setMailSubjectPrefix($mailSubjectPrefix)
    {
        self::$mailSubjectPrefix = $mailSubjectPrefix;
    }

    /**
     * @return mixed
     */
    public static function getMailSmtpHost()
    {
        return self::$mailSmtpHost;
    }

    /**
     * @param mixed $mailSmtpHost
     */
    public static function setMailSmtpHost($mailSmtpHost)
    {
        self::$mailSmtpHost = $mailSmtpHost;
    }

    /**
     * @return mixed
     */
    public static function getMailSmtpPort()
    {
        return self::$mailSmtpPort;
    }

    /**
     * @param mixed $mailSmtpPort
     */
    public static function setMailSmtpPort($mailSmtpPort)
    {
        self::$mailSmtpPort = $mailSmtpPort;
    }

    /**
     * @return mixed
     */
    public static function getMailUser()
    {
        return self::$mailUser;
    }

    /**
     * @param mixed $mailUser
     */
    public static function setMailUser($mailUser)
    {
        self::$mailUser = $mailUser;
    }

    /**
     * @return mixed
     */
    public static function getMailPassword()
    {
        return self::$mailPassword;
    }

    /**
     * @param mixed $mailPassword
     */
    public static function setMailPassword($mailPassword)
    {
        self::$mailPassword = $mailPassword;
    }

    /**
     * @return string
     */
    public static function getAccountIdPrefix()
    {
        return self::$accountIdPrefix;
    }

    /**
     * @return string
     */
    public static function getTargetIdPrefix()
    {
        return self::$targetIdPrefix;
    }

    /**
     * @return string
     */
    public static function getImageIdPrefix()
    {
        return self::$imageIdPrefix;
    }

    /**
     * @return string
     */
    public static function getReferrerIdPrefix()
    {
        return self::$referrerIdPrefix;
    }


    /**
     * @param string $frontendImagesPathWatermark
     */
    public static function setFrontendImagesPathWatermark($frontendImagesPathWatermark)
    {
        self::$frontendImagesPathWatermark = $frontendImagesPathWatermark;
    }

    /**
     * @param string $logFile
     */
    public static function setLogFile($logFile)
    {
        self::$logFile = $logFile;
    }

    /**
     * @return mixed
     */
    public static function getMongoHost()
    {
        return self::$mongoHost;
    }

    /**
     * @param mixed $mongoHost
     */
    public static function setMongoHost($mongoHost)
    {
        self::$mongoHost = $mongoHost;
    }

    /**
     * @return mixed
     */
    public static function getMongoPort()
    {
        return self::$mongoPort;
    }

    /**
     * @param mixed $mongoPort
     */
    public static function setMongoPort($mongoPort)
    {
        self::$mongoPort = $mongoPort;
    }

    /**
     * @return mixed
     */
    public static function getMongoUser()
    {
        return self::$mongoUser;
    }

    /**
     * @param mixed $mongoUser
     */
    public static function setMongoUser($mongoUser)
    {
        self::$mongoUser = $mongoUser;
    }

    /**
     * @return mixed
     */
    public static function getMongoPass()
    {
        return self::$mongoPass;
    }

    /**
     * @param mixed $mongoPass
     */
    public static function setMongoPass($mongoPass)
    {
        self::$mongoPass = $mongoPass;
    }

    /**
     * @return mixed
     */
    public static function getMongoDb()
    {
        return self::$mongoDb;
    }

    /**
     * @param mixed $mongoDb
     */
    public static function setMongoDb($mongoDb)
    {
        self::$mongoDb = $mongoDb;
    }

    /**
     * @return string
     */
    public static function getMongoKeyTargetAttrId()
    {
        return self::$mongoKeyTargetAttrId;
    }

    /**
     * @return string
     */
    public static function getMongoKeyTargetAttrUrl()
    {
        return self::$mongoKeyTargetAttrUrl;
    }

    /**
     * @return string
     */
    public static function getMongoKeyTargetAttrTsAdded()
    {
        return self::$mongoKeyTargetAttrTsAdded;
    }

    /**
     * @return string
     */
    public static function getMongoKeyTargetAttrCounterCheckedOut()
    {
        return self::$mongoKeyTargetAttrCounterCheckedOut;
    }

    /**
     * @return string
     */
    public static function getMongoKeyTargetAttrCounterUpdated()
    {
        return self::$mongoKeyTargetAttrCounterUpdated;
    }

    /**
     * @return string
     */
    public static function getMongoKeyTargetAttrCounterFailed()
    {
        return self::$mongoKeyTargetAttrCounterFailed;
    }

    /**
     * @return string
     */
    public static function getMongoKeyTargetAttrJavaScriptEnabled()
    {
        return self::$mongoKeyTargetAttrJavaScriptEnabled;
    }

    /**
     * @return string
     */
    public static function getMongoKeyTargetAttrTsRobotsCheck()
    {
        return self::$mongoKeyTargetAttrTsRobotsCheck;
    }

    /**
     * @return string
     */
    public static function getMongoKeyTargetAttrRobotsAllowed()
    {
        return self::$mongoKeyTargetAttrRobotsAllowed;
    }

    /**
     * @return string
     */
    public static function getMongoKeyTargetAttrSnipeDuration()
    {
        return self::$mongoKeyTargetAttrSnipeDuration;
    }

    /**
     * @return string
     */
    public static function getMongoKeyTargetAttrWeapon()
    {
        return self::$mongoKeyTargetAttrWeapon;
    }

    /**
     * @return string
     */
    public static function getMongoKeyTargetAttrForcedUpdate()
    {
        return self::$mongoKeyTargetAttrForcedUpdate;
    }

    /**
     * @return string
     */
    public static function getMongoKeyTargetAttrTsCheckedOut()
    {
        return self::$mongoKeyTargetAttrTsCheckedOut;
    }

    /**
     * @return string
     */
    public static function getMongoKeyAccountAttrId()
    {
        return self::$mongoKeyAccountAttrId;
    }

    /**
     * @return string
     */
    public static function getMongoKeyAccountAttrFirstName()
    {
        return self::$mongoKeyAccountAttrFirstName;
    }

    /**
     * @return string
     */
    public static function getMongoKeyAccountAttrLastName()
    {
        return self::$mongoKeyAccountAttrLastName;
    }

    /**
     * @return string
     */
    public static function getMongoKeyAccountAttrEmail()
    {
        return self::$mongoKeyAccountAttrEmail;
    }

    /**
     * @return string
     */
    public static function getMongoKeyAccountAttrEmailVerified()
    {
        return self::$mongoKeyAccountAttrEmailVerified;
    }

    /**
     * @return string
     */
    public static function getMongoKeyAccountAttrTsAdded()
    {
        return self::$mongoKeyAccountAttrTsAdded;
    }

    /**
     * @return string
     */
    public static function getMongoKeyAccountAttrActive()
    {
        return self::$mongoKeyAccountAttrActive;
    }

    /**
     * @return string
     */
    public static function getMongoKeyAccountAttrApiKey()
    {
        return self::$mongoKeyAccountAttrApiKey;
    }

    /**
     * @return string
     */
    public static function getMongoKeyAccountAttrApiKeyType()
    {
        return self::$mongoKeyAccountAttrApiKeyType;
    }

    /**
     * @return string
     */
    public static function getMongoKeyAccountAttrApiKeyTsAdded()
    {
        return self::$mongoKeyAccountAttrApiKeyTsAdded;
    }

    /**
     * @return string
     */
    public static function getMongoKeyAccountAttrApiKeyTsExpiry()
    {
        return self::$mongoKeyAccountAttrApiKeyTsExpiry;
    }

    /**
     * @return string
     */
    public static function getMongoKeyAccountAttrApiKeyActive()
    {
        return self::$mongoKeyAccountAttrApiKeyActive;
    }

    /**
     * @return string
     */
    public static function getMongoKeyAccountAttrDomainVerificationKey()
    {
        return self::$mongoKeyAccountAttrDomainVerificationKey;
    }

    /**
     * @return string
     */
    public static function getMongoKeyAccountAttrWhitelistActive()
    {
        return self::$mongoKeyAccountAttrWhitelistActive;
    }


    /**
     * @return string
     */
    public static function getMongoKeyReferrerAttrId()
    {
        return self::$mongoKeyReferrerAttrId;
    }

    /**
     * @return string
     */
    public static function getMongoKeyReferrerAttrUrlBase()
    {
        return self::$mongoKeyReferrerAttrUrlBase;
    }

    /**
     * @return string
     */
    public static function getMongoKeyReferrerAttrAccountId()
    {
        return self::$mongoKeyReferrerAttrAccountId;
    }

    /**
     * @return string
     */
    public static function getMongoKeyReferrerAttrTsAdded()
    {
        return self::$mongoKeyReferrerAttrTsAdded;
    }

    /**
     * @return string
     */
    public static function getMongoKeyReferrerAttrTsLastUpdated()
    {
        return self::$mongoKeyReferrerAttrTsLastUpdated;
    }

    /**
     * @return string
     */
    public static function getMongoKeyReferrerAttrBlacklisted()
    {
        return self::$mongoKeyReferrerAttrBlacklisted;
    }

    /**
     * @return string
     */
    public static function getMongoKeyReferrerAttrTsDomainVerification()
    {
        return self::$mongoKeyReferrerAttrTsDomainVerification;
    }

    /**
     * @return string
     */
    public static function getMongoKeyImageAttrId()
    {
        return self::$mongoKeyImageAttrId;
    }

    /**
     * @return string
     */
    public static function getMongoKeyImageAttrTargetId()
    {
        return self::$mongoKeyImageAttrTargetId;
    }

    /**
     * @return string
     */
    public static function getMongoKeyImageAttrWidth()
    {
        return self::$mongoKeyImageAttrWidth;
    }

    /**
     * @return string
     */
    public static function getMongoKeyImageAttrHeight()
    {
        return self::$mongoKeyImageAttrHeight;
    }

    /**
     * @return string
     */
    public static function getMongoKeyImageAttrEffect()
    {
        return self::$mongoKeyImageAttrEffect;
    }

    /**
     * @return string
     */
    public static function getMongoKeyImageAttrFileNameSuffix()
    {
        return self::$mongoKeyImageAttrFileNameSuffix;
    }

    /**
     * @return string
     */
    public static function getMongoKeyImageAttrTsAdded()
    {
        return self::$mongoKeyImageAttrTsAdded;
    }

    /**
     * @return string
     */
    public static function getMongoKeyImageAttrTsLastUpdated()
    {
        return self::$mongoKeyImageAttrTsLastUpdated;
    }

    /**
     * @return string
     */
    public static function getMongoKeyImageAttrTsLastRequested()
    {
        return self::$mongoKeyImageAttrTsLastRequested;
    }

    /**
     * @return string
     */
    public static function getMongoKeyImageAttrCounterCheckedOut()
    {
        return self::$mongoKeyImageAttrCounterCheckedOut;
    }

    /**
     * @return string
     */
    public static function getMongoKeyImageAttrCounterUpdated()
    {
        return self::$mongoKeyImageAttrCounterUpdated;
    }

    /**
     * @return string
     */
    public static function getMongoKeyImageAttrNumRequests()
    {
        return self::$mongoKeyImageAttrNumRequests;
    }

    /**
     * @return string
     */
    public static function getMongoKeyReferrerDeeplinkAttrId()
    {
        return self::$mongoKeyReferrerDeeplinkAttrId;
    }

    /**
     * @param string $mongoKeyReferrerDeeplinkAttrId
     */
    public static function setMongoKeyReferrerDeeplinkAttrId($mongoKeyReferrerDeeplinkAttrId)
    {
        self::$mongoKeyReferrerDeeplinkAttrId = $mongoKeyReferrerDeeplinkAttrId;
    }

    /**
     * @return string
     */
    public static function getMongoKeyReferrerDeeplinkAttrUrl()
    {
        return self::$mongoKeyReferrerDeeplinkAttrUrl;
    }

    /**
     * @return string
     */
    public static function getMongoKeyReferrerDeeplinkAttrTsAdded()
    {
        return self::$mongoKeyReferrerDeeplinkAttrTsAdded;
    }

    /**
     * @return string
     */
    public static function getReferrerDeeplinkIdPrefix()
    {
        return self::$referrerDeeplinkIdPrefix;
    }

    /**
     * @return string
     */
    public static function getMongoKeyTargetAttrTsLastUpdated()
    {
        return self::$mongoKeyTargetAttrTsLastUpdated;
    }

    /**
     * @return string
     */
    public static function getMongoKeyTargetAttrFileNameBase()
    {
        return self::$mongoKeyTargetAttrFileNameBase;
    }

    /**
     * @return string
     */
    public static function getMongoKeyTargetAttrFileNameSuffix()
    {
        return self::$mongoKeyTargetAttrFileNameSuffix;
    }

    /**
     * @return string
     */
    public static function getMongoKeyReferrerDeeplinkAttrReferrerId()
    {
        return self::$mongoKeyReferrerDeeplinkAttrReferrerId;
    }

    /**
     * @return string
     */
    public static function getMongoKeyReferrerAttrTsLastSeen()
    {
        return self::$mongoKeyReferrerAttrTsLastSeen;
    }

    /**
     * @return string
     */
    public static function getMongoKeyReferrerAttrNumRequestsDaily()
    {
        return self::$mongoKeyReferrerAttrNumRequestsDaily;
    }

    /**
     * @return string
     */
    public static function getMongoKeyImageAttrNumRequestsDaily()
    {
        return self::$mongoKeyImageAttrNumRequestsDaily;
    }

    /**
     * @return string
     */
    public static function getMongoKeyAccountAttrNumRequestsDaily()
    {
        return self::$mongoKeyAccountAttrNumRequestsDaily;
    }

    /**
     * @return string
     */
    public static function getMongoKeyOauthAttrId()
    {
        return self::$mongoKeyOauthAttrId;
    }

    /**
     * @return string
     */
    public static function getMongoKeyOauthAttrProvider()
    {
        return self::$mongoKeyOauthAttrProvider;
    }

    /**
     * @return string
     */
    public static function getMongoKeyOauthAttrAccessToken()
    {
        return self::$mongoKeyOauthAttrAccessToken;
    }

    /**
     * @return string
     */
    public static function getMongoKeyOauthAttrAccessTokenSecret()
    {
        return self::$mongoKeyOauthAttrAccessTokenSecret;
    }

    /**
     * @return string
     */
    public static function getMongoKeyOauthAttrAccessTokenType()
    {
        return self::$mongoKeyOauthAttrAccessTokenType;
    }

    /**
     * @return string
     */
    public static function getMongoKeyOauthAttrAccessTokenExpiry()
    {
        return self::$mongoKeyOauthAttrAccessTokenExpiry;
    }

    /**
     * @return string
     */
    public static function getMongoKeyOauthAttrRefreshToken()
    {
        return self::$mongoKeyOauthAttrRefreshToken;
    }

    /**
     * @return string
     */
    public static function getMongoKeyOauthAttrTsAdded()
    {
        return self::$mongoKeyOauthAttrTsAdded;
    }

    /**
     * @return string
     */
    public static function getMongoKeyOauthAttrTsLastUpdated()
    {
        return self::$mongoKeyOauthAttrTsLastUpdated;
    }

    /**
     * @return string
     */
    public static function getMongoKeyOauthAttrScreenName()
    {
        return self::$mongoKeyOauthAttrScreenName;
    }

    /**
     * @return string
     */
    public static function getMongoKeyImageAttrTsCheckedOut()
    {
        return self::$mongoKeyImageAttrTsCheckedOut;
    }

    /**
     * @return string
     */
    public static function getMongoCollectionQueueJobsMasters()
    {
        return self::$mongoCollectionQueueJobsMasters;
    }

    /**
     * @return string
     */
    public static function getMongoCollectionQueueJobsThumbnails()
    {
        return self::$mongoCollectionQueueJobsThumbnails;
    }

    /**
     * @return string
     */
    public static function getMongoCollectionAccounts()
    {
        return self::$mongoCollectionAccounts;
    }

    /**
     * @return string
     */
    public static function getMongoCollectionReferrers()
    {
        return self::$mongoCollectionReferrers;
    }

    /**
     * @return string
     */
    public static function getMongoCollectionReferrerDeeplinks()
    {
        return self::$mongoCollectionReferrerDeeplinks;
    }

    /**
     * @return string
     */
    public static function getMongoCollectionTargets()
    {
        return self::$mongoCollectionTargets;
    }

    /**
     * @return string
     */
    public static function getMongoCollectionImages()
    {
        return self::$mongoCollectionImages;
    }

    /**
     * @return string
     */
    public static function getMongoCollectionStatistics()
    {
        return self::$mongoCollectionStatistics;
    }

    /**
     * @return string
     */
    public static function getMongoCollectionTargetHostsBlacklist()
    {
        return self::$mongoCollectionTargetHostsBlacklist;
    }

    /**
     * @return string
     */
    public static function getMongoCollectionReferrersBlacklist()
    {
        return self::$mongoCollectionReferrersBlacklist;
    }

    /**
     * @return int
     */
    public static function getTargetDefaultPriority()
    {
        return self::$targetDefaultPriority;
    }

    /**
     * @return string
     */
    public static function getMongoKeyTargetAttrTsLastRequested()
    {
        return self::$mongoKeyTargetAttrTsLastRequested;
    }

    /**
     * @return string
     */
    public static function getMongoKeyTargetAttrTsLastFailed()
    {
        return self::$mongoKeyTargetAttrTsLastFailed;
    }

    /**
     * @return string
     */
    public static function getMongoKeyTargetAttrNumRequests()
    {
        return self::$mongoKeyTargetAttrNumRequests;
    }


    public static function getTargetPriority($key)
    {
        if (is_array(self::$targetPriorities) && array_key_exists($key, self::$targetPriorities))
        {
            return self::$targetPriorities[$key];
        }else {
            return self::getTargetDefaultPriority();
        }
    }


    public static function getAccountMaxDailyRequests($key)
    {
        if (is_array(self::$accountMaxDailyRequests) && array_key_exists($key, self::$accountMaxDailyRequests))
        {
            return self::$accountMaxDailyRequests[$key];
        }else {
            return self::getAccountDefaultMaxDailyRequests();
        }
    }


    /**
     * @param $key
     * @return int
     */
    public static function getImageMaxAge($key)
    {
        if(is_array(self::$imageMaxAges) && array_key_exists($key, self::$imageMaxAges))
        {
            return self::$imageMaxAges[$key];
        }else {
            return self::getImageDefaultMaxAge();
        }
    }


    /**
     * @return mixed
     */
    public static function getHttpProxyUrl()
    {
        return self::$httpProxyUrl;
    }

    /**
     * @param mixed $httpProxyUrl
     */
    public static function setHttpProxyUrl($httpProxyUrl)
    {
        self::$httpProxyUrl = $httpProxyUrl;
    }

    /**
     * @return string
     */
    public static function getMongoKeyReferrerAttrNumRequests()
    {
        return self::$mongoKeyReferrerAttrNumRequests;
    }

    /**
     * @return int
     */
    public static function getAccountDefaultMaxDailyRequests()
    {
        return self::$accountDefaultMaxDailyRequests;
    }

    /**
     * @return string
     */
    public static function getMongoKeyReferrerDeeplinkAttrNumRequests()
    {
        return self::$mongoKeyReferrerDeeplinkAttrNumRequests;
    }

    /**
     * @return string
     */
    public static function getMongoKeyReferrerDeeplinkAttrTsLastSeen()
    {
        return self::$mongoKeyReferrerDeeplinkAttrTsLastSeen;
    }

    /**
     * @return int
     */
    public static function getImageDefaultMaxAge()
    {
        return self::$imageDefaultMaxAge;
    }

    /**
     * @return int
     */
    public static function getAgentMaxSleepDuration()
    {
        return self::$agentMaxSleepDuration;
    }

    /**
     * @param int $agentMaxSleepDuration
     */
    public static function setAgentMaxSleepDuration($agentMaxSleepDuration)
    {
        self::$agentMaxSleepDuration = $agentMaxSleepDuration;
    }

	/**
	 * @return string
	 */
	public static function getMongoKeyTargetAttrLastErrorMessage()
	{
		return self::$mongoKeyTargetAttrLastErrorMessage;
	}

	/**
	 * @return string
	 */
	public static function getMongoKeyTargetAttrCensored()
	{
		return self::$mongoKeyTargetAttrCensored;
	}

	/**
	 * @return string
	 */
	public static function getMongoKeyOauthAttrPassword()
	{
		return self::$mongoKeyOauthAttrPassword;
	}

	/**
	 * @return string
	 */
	public static function getOauthLocalPasswordSalt()
	{
		return self::$oauthLocalPasswordSalt;
	}

	/**
	 * @param string $oauthLocalPasswordSalt
	 */
	public static function setOauthLocalPasswordSalt($oauthLocalPasswordSalt)
	{
		self::$oauthLocalPasswordSalt = $oauthLocalPasswordSalt;
	}

    /**
     * @return string
     */
    public static function getMongoCollectionUserAgents()
    {
        return self::$mongoCollectionUserAgents;
    }

    /**
     * @return string
     */
    public static function getMongoKeyUserAgentAttrId()
    {
        return self::$mongoKeyUserAgentAttrId;
    }

    /**
     * @return string
     */
    public static function getMongoKeyUserAgentAttrDescription()
    {
        return self::$mongoKeyUserAgentAttrDescription;
    }

    /**
     * @return string
     */
    public static function getMongoKeyUserAgentAttrTsAdded()
    {
        return self::$mongoKeyUserAgentAttrTsAdded;
    }

    /**
     * @return string
     */
    public static function getMongoKeyUserAgentAttrTsLastUpdated()
    {
        return self::$mongoKeyUserAgentAttrTsLastUpdated;
    }

    /**
     * @return string
     */
    public static function getMongoKeyUserAgentAttrBlacklisted()
    {
        return self::$mongoKeyUserAgentAttrBlacklisted;
    }

    /**
     * @return string
     */
    public static function getMongoKeyUserAgentAttrNumRequests()
    {
        return self::$mongoKeyUserAgentAttrNumRequests;
    }

    /**
     * @return string
     */
    public static function getMongoKeyUserAgentAttrTsLastSeen()
    {
        return self::$mongoKeyUserAgentAttrTsLastSeen;
    }

    /**
     * @return string
     */
    public static function getMongoKeyUserAgentAttrNumRequestsDaily()
    {
        return self::$mongoKeyUserAgentAttrNumRequestsDaily;
    }

    /**
     * @return string
     */
    public static function getMongoCollectionUserAgentsBlacklist()
    {
        return self::$mongoCollectionUserAgentsBlacklist;
    }

    /**
     * @return boolean
     */
    public static function isStoreUserAgents()
    {
        return self::$storeUserAgents;
    }

    /**
     * @param boolean $storeUserAgents
     */
    public static function setStoreUserAgents($storeUserAgents)
    {
        self::$storeUserAgents = $storeUserAgents;
    }

    /**
     * @return string
     */
    public static function getMongoKeyTargetAttrMimeType()
    {
        return self::$mongoKeyTargetAttrMimeType;
    }

	/**
	 * @return mixed
	 */
	public static function isEnergySaveActive()
	{
		return self::$energySaveActive;
	}

	/**
	 * @param mixed $energySaveActive
	 */
	public static function setEnergySaveActive($energySaveActive)
	{
		self::$energySaveActive = $energySaveActive;
	}

	/**
	 * @return string
	 */
	public static function getRedisScheme()
	{
		return self::$redisScheme;
	}

	/**
	 * @param string $redisScheme
	 */
	public static function setRedisScheme($redisScheme)
	{
		self::$redisScheme = $redisScheme;
	}

	/**
	 * @return string
	 */
	public static function getRedisHost()
	{
		return self::$redisHost;
	}

	/**
	 * @param string $redisHost
	 */
	public static function setRedisHost($redisHost)
	{
		self::$redisHost = $redisHost;
	}

	/**
	 * @return int
	 */
	public static function getRedisPort()
	{
		return self::$redisPort;
	}

	/**
	 * @param int $redisPort
	 */
	public static function setRedisPort($redisPort)
	{
		self::$redisPort = $redisPort;
	}

	/**
	 * @return string
	 */
	public static function getRedisDb()
	{
		return self::$redisDb;
	}

	/**
	 * @param string $redisDb
	 */
	public static function setRedisDb($redisDb)
	{
		self::$redisDb = $redisDb;
	}

	/**
	 * @return int
	 */
	public static function getRedisMasterImageExpire()
	{
		return self::$redisMasterImageExpire;
	}

	/**
	 * @param int $redisMasterImageExpire
	 */
	public static function setRedisMasterImageExpire($redisMasterImageExpire)
	{
		self::$redisMasterImageExpire = $redisMasterImageExpire;
	}

	/**
	 * @return string
	 */
	public static function getRedisKeyTargetMasterImageData()
	{
		return self::$redisKeyTargetMasterImageData;
	}

	/**
	 * @return int
	 */
	public static function getRedisImageCacheExpire()
	{
		return self::$redisImageCacheExpire;
	}

	/**
	 * @param int $redisImageCacheExpire
	 */
	public static function setRedisImageCacheExpire($redisImageCacheExpire)
	{
		self::$redisImageCacheExpire = $redisImageCacheExpire;
	}

	/**
	 * @return string
	 */
	public static function getRedisKeyImageCacheData()
	{
		return self::$redisKeyImageCacheData;
	}

	/**
	 * @return string
	 */
	public static function getRedisKeyImageCacheKeyBranded()
	{
		return self::$redisKeyImageCacheKeyBranded;
	}

	/**
	 * @return string
	 */
	public static function getRedisKeyImageCacheKeyUnbranded()
	{
		return self::$redisKeyImageCacheKeyUnbranded;
	}

    /**
     * @return mixed
     */
    public static function getApiAgentSecret()
    {
        return self::$apiAgentSecret;
    }

    /**
     * @param mixed $apiAgentSecret
     */
    public static function setApiAgentSecret($apiAgentSecret)
    {
        self::$apiAgentSecret = $apiAgentSecret;
    }

    /**
     * @return mixed
     */
    public static function isGoogleAuthEnabled()
    {
        return self::$googleAuthEnabled;
    }

    /**
     * @param mixed $googleAuthEnabled
     */
    public static function setGoogleAuthEnabled($googleAuthEnabled)
    {
        self::$googleAuthEnabled = $googleAuthEnabled;
    }

    /**
     * @return mixed
     */
    public static function isTwitterAuthEnabled()
    {
        return self::$twitterAuthEnabled;
    }

    /**
     * @param mixed $twitterAuthEnabled
     */
    public static function setTwitterAuthEnabled($twitterAuthEnabled)
    {
        self::$twitterAuthEnabled = $twitterAuthEnabled;
    }

    /**
     * @return array
     */
    protected static function getImageEffects()
    {
        return self::$imageEffects;
    }

    /**
     * @param array $imageEffects
     */
    public static function setImageEffects($imageEffects)
    {
        self::$imageEffects = $imageEffects;
    }

    /**
     * @return array
     */
    protected static function getImageEffectsExtra()
    {
        return self::$imageEffectsExtra;
    }

    /**
     * @param array $imageEffectsExtra
     */
    public static function setImageEffectsExtra($imageEffectsExtra)
    {
        self::$imageEffectsExtra = $imageEffectsExtra;
    }

    /**
     * @return array
     */
    public static function getActiveImageEffects()
    {
        $effects = self::getImageEffects();

        foreach(self::getImageEffectsExtra() as $key => $val)
        {
            if($val[1] == true)
            {
                $effects[$key] = $val[0];
            }
        }

        return $effects;
    }

    /**
     * @param array $apiValidWidths
     */
    public static function setApiValidWidths($apiValidWidths)
    {
        self::$apiValidWidths = $apiValidWidths;
    }

    /**
     * @return string
     */
    public static function getFrontendImagesPathTransparentPixel()
    {
        return self::$frontendImagesPathTransparentPixel;
    }

    /**
     * @return string
     */
    public static function getMongoKeyTargetHostsBlacklistAttrId()
    {
        return self::$mongoKeyTargetHostsBlacklistAttrId;
    }

    /**
     * @return string
     */
    public static function getMongoKeyTargetHostsBlacklistAttrHost()
    {
        return self::$mongoKeyTargetHostsBlacklistAttrHost;
    }

    /**
     * @return string
     */
    public static function getMongoKeyTargetHostsBlacklistAttrType()
    {
        return self::$mongoKeyTargetHostsBlacklistAttrType;
    }

    /**
     * @return string
     */
    public static function getMongoKeyTargetHostsBlacklistAttrComment()
    {
        return self::$mongoKeyTargetHostsBlacklistAttrComment;
    }

    /**
     * @return string
     */
    public static function getRedisKeyAgentLastSleepDurationPrefix()
    {
        return self::$redisKeyAgentLastSleepDurationPrefix;
    }

    /**
     * @return boolean
     */
    public static function isAmazonS3enabled()
    {
        return self::$amazonS3enabled;
    }

    /**
     * @param boolean $amazonS3enabled
     */
    public static function setAmazonS3enabled($amazonS3enabled)
    {
        self::$amazonS3enabled = $amazonS3enabled;
    }

    /**
     * @return mixed
     */
    public static function getAmazonS3region()
    {
        return self::$amazonS3region;
    }

    /**
     * @param mixed $amazonS3region
     */
    public static function setAmazonS3region($amazonS3region)
    {
        self::$amazonS3region = $amazonS3region;
    }

    /**
     * @return mixed
     */
    public static function getAmazonS3credentialsKey()
    {
        return self::$amazonS3credentialsKey;
    }

    /**
     * @param mixed $amazonS3credentialsKey
     */
    public static function setAmazonS3credentialsKey($amazonS3credentialsKey)
    {
        self::$amazonS3credentialsKey = $amazonS3credentialsKey;
    }

    /**
     * @return mixed
     */
    public static function getAmazonS3credentialsSecret()
    {
        return self::$amazonS3credentialsSecret;
    }

    /**
     * @param mixed $amazonS3credentialsSecret
     */
    public static function setAmazonS3credentialsSecret($amazonS3credentialsSecret)
    {
        self::$amazonS3credentialsSecret = $amazonS3credentialsSecret;
    }

    /**
     * @return mixed
     */
    public static function getAmazonS3credentialsSignature()
    {
        return self::$amazonS3credentialsSignature;
    }

    /**
     * @param mixed $amazonS3credentialsSignature
     */
    public static function setAmazonS3credentialsSignature($amazonS3credentialsSignature)
    {
        self::$amazonS3credentialsSignature = $amazonS3credentialsSignature;
    }

    /**
     * @return mixed
     */
    public static function getAmazonS3bucketThumbnails()
    {
        return self::$amazonS3bucketThumbnails;
    }

    /**
     * @param mixed $amazonS3bucketThumbnails
     */
    public static function setAmazonS3bucketThumbnails($amazonS3bucketThumbnails)
    {
        self::$amazonS3bucketThumbnails = $amazonS3bucketThumbnails;
    }

    /**
     * @return string
     */
    public static function getMongoKeyImageAttrAmazonS3url()
    {
        return self::$mongoKeyImageAttrAmazonS3url;
    }

    /**
     * @return string
     */
    public static function getRedisKeyImageAmazonS3url()
    {
        return self::$redisKeyImageAmazonS3url;
    }

    /**
     * @return string
     */
    public static function getMongoKeyImageAttrLocalPath()
    {
        return self::$mongoKeyImageAttrLocalPath;
    }

    /**
     * @return boolean
     */
    public static function isLocalThumbnailStorageEnabled()
    {
        return self::$localThumbnailStorageEnabled;
    }

    /**
     * @param boolean $localThumbnailStorageEnabled
     */
    public static function setLocalThumbnailStorageEnabled($localThumbnailStorageEnabled)
    {
        self::$localThumbnailStorageEnabled = $localThumbnailStorageEnabled;
    }

    /**
     * @return int
     */
    public static function getAmazonS3presignedUrlExpireSeconds()
    {
        return self::$amazonS3presignedUrlExpireSeconds;
    }

    /**
     * @param int $amazonS3presignedUrlExpireSeconds
     */
    public static function setAmazonS3presignedUrlExpireSeconds($amazonS3presignedUrlExpireSeconds)
    {
        self::$amazonS3presignedUrlExpireSeconds = $amazonS3presignedUrlExpireSeconds;
    }

    /**
     * @return string
     */
    public static function getAmazonS3presignedUrlExpireStr()
    {
        return self::$amazonS3presignedUrlExpireStr;
    }

    /**
     * @param string $amazonS3presignedUrlExpireStr
     */
    public static function setAmazonS3presignedUrlExpireStr($amazonS3presignedUrlExpireStr)
    {
        self::$amazonS3presignedUrlExpireStr = $amazonS3presignedUrlExpireStr;
    }
}
