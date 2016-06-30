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

define('DIRECTORY_ROOT', dirname(dirname(__DIR__)));

require_once(DIRECTORY_ROOT . '/config/backend-config.inc.php');

use ThumbSniper\common\Settings;
use ThumbSniper\api\ApiV3;
use ThumbSniper\api\SlimResponse;
use Slim\Slim;

if(Settings::getMaintenance()) { die("MAINTENANCE"); }

/////

$slim = new Slim(array(
    'debug' => true
));

$api = new ApiV3($slim->request->get('otnDebug') ? true : false);

// fetch parameters

$apiParams = array();
$apiParams['url'] = $slim->request->params('url');

if($slim->request->params('waitimg'))
{
	$apiParams['waitimg'] = $slim->request->params('waitimg');
}else if($slim->request->params('waitImage'))
{
	$apiParams['waitimg'] = $slim->request->params('waitImage');
}else {
	$apiParams['waitimg'] = null;
}

$apiParams['referrer'] = $slim->request->getReferer();
$apiParams['userAgent'] = $slim->request->getUserAgent();
$apiParams['visitorAddress'] = $slim->request->getIp();
$apiParams['callback'] = $slim->request->params('callback');
//$forceUpdate


/**
 * @param $slim
 * @param $response
 */
function output($slim, $response)
{
	/**
	* @var Slim $slim
	* @var SlimResponse $response
	*/

	if($response->getCacheControl())
	{
		$slim->response()->header('Cache-Control', $response->getCacheControl());
	}

	if($response->getExpires())
	{
		$slim->expires($response->getExpires());
	}

	if($response->getPragma())
	{
		$slim->response()->header('Pragma', $response->getPragma());
	}

	if($response->getContentType())
	{
		$slim->contentType($response->getContentType());
	}

	if($response->getOutput())
	{
		$slim->response()->setBody($response->getOutput());
	}

	if(is_array($response->getRedirect()))
	{
		$redirect = $response->getRedirect();
		$slim->redirect($redirect[0], $redirect[1]);
	}

    if($response->getHttpStatus())
    {
        $slim->response->setStatus($response->getHttpStatus());
    }

}

$slim->get('/thumbnail/:apiKey/:width/:effect/', function ($apiKey, $width, $effect) use ($slim, $api, $apiParams)
{
	if(!$api->loadAndValidateCommonParameters('thumbnail'))
	{
		echo "invalid common parameters";
		die();
	}

    $response = $api->outputThumbnail($apiKey, $width, $effect, $apiParams['url'], $apiParams['waitimg'], 
        $apiParams['referrer'], null, $apiParams['callback'], $apiParams['userAgent'], $apiParams['visitorAddress']);

    if(!$response)
    {
        //$api->publishLogsAsHeaders();
        echo "invalid thumbnail parameters";
    }else
    {
        output($slim, $response);
    }

})->conditions(array(
    'apiKey'    => '[a-z0-9]{32}',
    'width'     => '[1-9][0-9]{1,2}',
    'effect'    => '[a-z][a-z0-9]{1,20}'
));

$slim->get('/thumbnail/:width/:effect/', function ($width, $effect) use ($slim, $api, $apiParams)
{
	if(!$api->loadAndValidateCommonParameters('thumbnail'))
	{
		echo "invalid common parameters";
        die();
	}

	$response = $api->outputThumbnail(null, $width, $effect, $apiParams['url'], $apiParams['waitimg'],
        $apiParams['referrer'], null, $apiParams['callback'], $apiParams['userAgent'], $apiParams['visitorAddress']);

	if(!$response)
	{
		//$api->publishLogsAsHeaders();
		echo "invalid thumbnail parameters";
        die();
	}else
	{
		output($slim, $response);
	}
})->conditions(array(
    'width'     => '[1-9][0-9]{1,2}',
    'effect'    => '[a-z][a-z0-9]{1,20}'
));

$slim->get('/agent/:apiAgentSecret/master/job/:mode/', function ($apiAgentSecret, $mode) use ($api)
{
    echo $api->agentGetMasterJob($mode);
})->conditions(array(
	'apiAgentSecret' => Settings::getApiAgentSecret(),
    'mode'           => '(normal|longrun|phantom)'
));

$slim->post('/agent/:apiAgentSecret/thumbnail/job/', function () use ($slim, $api)
{
    $body = $slim->request->getBody();
    $jsonData = json_decode($body, true);
    $featuredEffects = $jsonData['featuredEffects'];

    echo $api->agentGetThumbnailJob($featuredEffects);
})->conditions(array(
    'apiAgentSecret' => Settings::getApiAgentSecret()
));

$slim->post('/agent/:apiAgentSecret/master/commit/:mode/', function ($apiAgentSecret, $mode) use ($slim, $api)
{
    if($mode == "phantom") {
        $data = $slim->request->getBody();
    }else {
        $data = $slim->request()->params('data');
    }
	 
    if($data) {
        echo $api->agentProcessMasterCommit($data, $mode);
    }
    
})->conditions(array(
    'apiAgentSecret' => Settings::getApiAgentSecret(),
    'mode'           => '(normal|longrun|phantom)'
));

$slim->post('/agent/:apiAgentSecret/thumbnail/commit/', function () use ($slim, $api)
{
    $data = $slim->request()->params('data');
    if($data) {
        echo $api->agentProcessThumbnailsCommit($data);
    }else {
        echo "no data";
    }
})->conditions(array(
    'apiAgentSecret' => Settings::getApiAgentSecret()
));

$slim->post('/agent/:apiAgentSecret/master/failure/:mode/', function ($apiAgentSecret, $mode) use ($slim, $api)
{
    if($mode == "phantom") {
        $data = $slim->request->getBody();
    }else {
        $data = $slim->request()->params('data');
    }

    if($data) {
        echo $api->agentProcessMasterFailure($data, $mode);
    }else {
		echo "no data";
	}
})->conditions(array(
    'apiAgentSecret' => Settings::getApiAgentSecret(),
    'mode'           => '(normal|longrun|phantom)'
));

$slim->post('/agent/:apiAgentSecret/thumbnail/failure/', function () use ($slim, $api)
{
    $data = $slim->request()->params('data');
    if($data) {
        //TODO create function for image failure
    }
})->conditions(array(
    'apiAgentSecret' => Settings::getApiAgentSecret()
));

$slim->run();
