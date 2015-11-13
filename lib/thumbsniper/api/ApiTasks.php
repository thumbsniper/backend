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

namespace ThumbSniper\api;

require_once('vendor/autoload.php');


use Predis\Client;
use ThumbSniper\common\Logger;
use ThumbSniper\common\Settings;
use ThumbSniper\db\Redis;
use ThumbSniper\shared\Image;
use ThumbSniper\objective\ImageModel;
use ThumbSniper\objective\ReferrerModel;
use ThumbSniper\shared\Target;
use ThumbSniper\objective\TargetModel;
use ThumbSniper\db\Mongo;
use MongoDB;


class ApiTasks
{
    private $forceDebug;

    /** @var MongoDB */
    protected $mongodb;

	/** @var Client */
	private $redis;

    /** @var Logger */
    protected $logger;

    /** @var TargetModel */
    private $targetModel;

    /** @var ImageModel */
    private $imageModel;

    /** @var ReferrerModel */
    private $referrerModel;



    function __construct($forceDebug)
    {
        $this->forceDebug = $forceDebug;

        $this->logger = new Logger($this->forceDebug);
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $mongodb = new Mongo($this->logger, Settings::getMongoHost(), Settings::getMongoPort(), Settings::getMongoUser(), Settings::getMongoPass(), Settings::getMongoDb());
	    $redis = new Redis($this->logger, Settings::getRedisScheme(), Settings::getRedisHost(), Settings::getRedisPort(), Settings::getRedisDb());

        if($mongodb->getConnection()) {
            $this->mongodb = $mongodb->getConnection();
        }else {
            $this->logger->log(__METHOD__, "Could not connect to MongoDB", LOG_ERR);
            die();
        }

	    if($redis->getConnection()) {
		    $this->redis = $redis->getConnection();
	    }else {
		    $this->logger->log(__METHOD__, "Could not connect to Redis", LOG_ERR);
		    die();
	    }

        $this->targetModel = new TargetModel($this->mongodb, $this->redis, $this->logger);
        $this->imageModel = new ImageModel($this->mongodb, $this->redis, $this->logger);
        $this->referrerModel = new ReferrerModel($this->mongodb, $this->logger);
    } // function



    public function forceTargetUpdate($targetId)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $target = $this->targetModel->getById($targetId);

        if(!$target instanceof Target)
        {
            return false;
        }

        $images = $this->imageModel->getImages($targetId);

        /** @var Image $image */
        foreach($images as $image)
        {
            if($image instanceof Image)
            {
                $target->setCurrentImage($image);

                //not required with MongoDB
                //$this->targetModel->checkTargetCurrentness($target, true);
                //$this->imageModel->checkImageCurrentness($target, $image, true);
            }
        }
    }



    public function resetTargetFailures()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $targets = $this->targetModel->getFailedTargets();

        /** @var Target $target */
        foreach($targets as $target)
        {
            if($target instanceof Target)
            {
                $this->logger->log(__METHOD__, "resetting failure counts for target " . $target->getId() . " (enqueued)", LOG_INFO);
                $this->targetModel->resetTargetFailures($target->getId());

                //not required with mongodb
                //$this->targetModel->enqueue($target, "normal");
            }
        }
    }
}
