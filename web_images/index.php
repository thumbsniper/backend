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

define('DIRECTORY_ROOT', dirname(__DIR__));

require_once(DIRECTORY_ROOT . '/config/config.inc.php');

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



/**
 * @param $slim
 * @param $response
 */
function output(Slim $slim, SlimResponse $response)
{
	if($response->getCacheControl())
	{
		$slim->response()->header('Cache-Control', $response->getCacheControl());
	}

	if($response->getLastModified())
	{
		$slim->lastModified($response->getLastModified());
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
		//FIXME: does not return 508, but 200 OK
		$slim->halt(508, $response->getHttpStatus());
	}

}

$slim->get('/image/:fileName', function ($fileName) use ($slim, $api)
{
	$cacheId = substr($fileName, 0, 32);

	if(!$api->loadAndValidateCachedImageParameters($cacheId))
	{
		//$api->publishLogsAsHeaders();
		die("invalid cachedImage parameters");
	}

	//$api->publishLogsAsHeaders();
    $cachedImageOutput = $api->outputCachedImage();

    if($cachedImageOutput instanceof SlimResponse) {
        output($slim, $cachedImageOutput);
    }else {
        die("invalid cachedImage data");
    }
})->conditions(array(
    'fileName'    => '[a-z0-9]{32}\.(png|jpeg|jpg)'
));

$slim->run();
