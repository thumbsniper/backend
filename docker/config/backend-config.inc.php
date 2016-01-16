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

require_once(DIRECTORY_ROOT . '/vendor/autoload.php');

date_default_timezone_set("Europe/Berlin");

use ThumbSniper\common\Settings;

Settings::setDomain('example.com');

// MongoDB
Settings::setMongoHost("mongo");
Settings::setMongoUser("thumbsniper");
Settings::setMongoPass('secret');
Settings::setMongoDb("thumbsniper");

// Redis
Settings::setRedisHost("redis");
Settings::setRedisDb(0);

// Google Auth
Settings::setGoogleAuthEnabled(false);
Settings::setGoogleAuthUrl('http://panel.' . Settings::getDomain() . '/pages/googleAuth.php');
Settings::setGoogleClientId('XXX.apps.googleusercontent.com');
Settings::setGoogleClientSecret('secret');

// Twitter Auth
Settings::setTwitterAuthEnabled(false);
Settings::setTwitterAuthRedirectUrl('http://panel.' . Settings::getDomain() . '/pages/twitterAuthRedirect.php');
Settings::setTwitterAuthCallbackUrl('http://panel.' . Settings::getDomain() . '/pages/twitterAuthCallback.php');
Settings::setTwitterConsumerKey('changeme');
Settings::setTwitterConsumerSecret('secret');
Settings::setTwitterAccessToken('changeme');
Settings::setTwitterAccessTokenSecret('secret');

// Piwik Tracking
Settings::setPiwikTrackingEnabled(false);
Settings::setPiwikUrl('https://piwik');
Settings::setPiwikSiteId(1);
Settings::setPiwikTokenAuth('secret');

// Logging
Settings::setLogMethodThumbSniperAccountAccountModel(true);
Settings::setLogMethodThumbSniperApiApiV2(true);
Settings::setLogMethodThumbSniperApiApiTasks(true);
Settings::setLogMethodThumbSniperCommonLockableModel(true);
Settings::setLogMethodThumbSniperCommonLogger(true);
Settings::setLogMethodThumbSniperObjectiveImageModel(true);
Settings::setLogMethodThumbSniperObjectiveReferrerModel(true);
Settings::setLogMethodThumbSniperObjectiveTargetModel(true);

// Web
Settings::setWebUrl('http://www.' . Settings::getDomain());

// Panel
Settings::setPanelTitle('ThumbSniper');
Settings::setPanelUrl('http://panel.' . Settings::getDomain());

// Frontend
Settings::setFrontendAdminEmail('my-admin-address@example.com');
Settings::setFrontendImagesUrl('http://img.' . Settings::getDomain());

Settings::setFrontendImageHosts(array(
    'img.' . Settings::getDomain()
));

/* Example for using multiple (numbered) alias names:
Settings::setFrontendImageHosts(array(
    'img1.' . Settings::getDomain(),
    'img2.' . Settings::getDomain(),
    'img3.' . Settings::getDomain()
));
*/

// Misc
Settings::setUserAgentName("Example");
Settings::setUserAgentUrl(Settings::getDomain());
Settings::setImageWatermarksEnabled(false);
Settings::setApiKeyOrReferrerWhitelistOnly(true);
Settings::setAgentMaxSleepDuration(30);
Settings::setStoreUserAgents(true);

// API
Settings::setApiHost('api.' . Settings::getDomain());
Settings::setApiKeyExpire(31536000); // 1 year
Settings::setApiAgentSecret('secret');

///

define('WEB_PANEL_DIR', DIRECTORY_ROOT . '/web_panel');
define('WEB_API_DIR', DIRECTORY_ROOT . '/web_api');
define('WEB_IMAGES_DIR', DIRECTORY_ROOT . '/web_images');

define('THUMBNAILS_DIR', WEB_IMAGES_DIR . '/thumbnails/');

