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

use Guzzle\Http\Message\RequestFactory;
use Predis\Client;
use ThumbSniper\common\Helpers;
use ThumbSniper\common\Logger;
use ThumbSniper\common\Settings;
use ThumbSniper\shared\Image;
use ThumbSniper\shared\Target;
use MongoDB;
use MongoTimestamp;
use MongoCursor;
use MongoCollection;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Exception;


class ImageModel
{
    /** @var MongoDB */
    protected $mongoDB;

    /** @var Client */
    private $redis;

    /** @var Logger */
    protected $logger;


    function __construct(MongoDB $mongoDB, Client $redis, Logger $logger)
    {
        $this->mongoDB = $mongoDB;
        $this->redis = $redis;
        $this->logger = $logger;

        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

    }



    private static function load($data) {
        $image = new Image();

        if(!is_array($data))
        {
            return false;
        }

        $image->setId(isset($data[Settings::getMongoKeyImageAttrId()]) ? $data[Settings::getMongoKeyImageAttrId()] : null);
        $image->setTargetId(isset($data[Settings::getMongoKeyImageAttrTargetId()]) ? $data[Settings::getMongoKeyImageAttrTargetId()] : null);
        $image->setWidth(isset($data[Settings::getMongoKeyImageAttrWidth()]) ? $data[Settings::getMongoKeyImageAttrWidth()] : null);
        $image->setHeight(isset($data[Settings::getMongoKeyImageAttrHeight()]) ? $data[Settings::getMongoKeyImageAttrHeight()] : null);
        $image->setEffect(isset($data[Settings::getMongoKeyImageAttrEffect()]) ? $data[Settings::getMongoKeyImageAttrEffect()] : null);
        $image->setFileNameSuffix(isset($data[Settings::getMongoKeyImageAttrFileNameSuffix()]) ? $data[Settings::getMongoKeyImageAttrFileNameSuffix()] : null);
        $image->setTsLastRequested(isset($data[Settings::getMongoKeyImageAttrTsLastRequested()]) ? $data[Settings::getMongoKeyImageAttrTsLastRequested()] : null);
        $image->setCounterCheckedOut(isset($data[Settings::getMongoKeyImageAttrCounterCheckedOut()]) ? $data[Settings::getMongoKeyImageAttrCounterCheckedOut()] : 0);
        $image->setCounterUpdated(isset($data[Settings::getMongoKeyImageAttrCounterUpdated()]) ? $data[Settings::getMongoKeyImageAttrCounterUpdated()] : 0);
        $image->setNumRequests(isset($data[Settings::getMongoKeyImageAttrNumRequests()]) ? $data[Settings::getMongoKeyImageAttrNumRequests()] : 0);
        $image->setLocalPath(isset($data[Settings::getMongoKeyImageAttrLocalPath()]) ? $data[Settings::getMongoKeyImageAttrLocalPath()] : null);
        $image->setAmazonS3url(isset($data[Settings::getMongoKeyImageAttrAmazonS3url()]) ? $data[Settings::getMongoKeyImageAttrAmazonS3url()] : null);
        
        $tsAdded = null;
        if(isset($data[Settings::getMongoKeyImageAttrTsAdded()]))
        {
            if($data[Settings::getMongoKeyImageAttrTsAdded()] instanceof MongoTimestamp) {
                /** @var MongoTimestamp $mongoTs */
                $mongoTs = $data[Settings::getMongoKeyImageAttrTsAdded()];
                $tsAdded = $mongoTs->sec;
            }
        }
        $image->setTsAdded($tsAdded);

        $tsLastUpdated = null;
        if(isset($data[Settings::getMongoKeyImageAttrTsLastUpdated()]))
        {
            if($data[Settings::getMongoKeyImageAttrTsLastUpdated()] instanceof MongoTimestamp) {
                /** @var MongoTimestamp $mongoTs */
                $mongoTs = $data[Settings::getMongoKeyImageAttrTsLastUpdated()];
                $tsLastUpdated = $mongoTs->sec;
            }
        }
        $image->setTsLastUpdated($tsLastUpdated);

        $tsLastRequested = null;
        if(isset($data[Settings::getMongoKeyImageAttrTsLastRequested()]))
        {
            if($data[Settings::getMongoKeyImageAttrTsLastRequested()] instanceof MongoTimestamp) {
                /** @var MongoTimestamp $mongoTs */
                $mongoTs = $data[Settings::getMongoKeyImageAttrTsLastRequested()];
                $tsLastRequested = $mongoTs->sec;
            }
        }
        $image->setTsLastRequested($tsLastRequested);

        return $image;
    }



    private function calculateId($targetId, $width, $effect)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        return md5(Settings::getImageIdPrefix() . $targetId . ':' . $width . ':' . $effect);
    }



    public function getById($id)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $image = NULL;


        // refresh from DB
        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionImages());
            $imageData = $collection->findOne(array('_id' => $id));

            if(is_array($imageData)) {
                $image = ImageModel::load($imageData);

                if($image instanceof Image) {
                    $this->logger->log(__METHOD__, "found image " . $image->getId() . " in MongoDB", LOG_DEBUG);
                }
            }
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "died (" . $e->getMessage() . ")", LOG_ERR);
            die($e->getMessage());
        }

        //TODO: check if this is really an image
        return $image;
    }



    public function getOrCreate($targetId, $width, $effect)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $imageId = $this->calculateId($targetId, $width, $effect);
        $image = $this->getById($imageId);


        do {
            $this->logger->log(__METHOD__, "start loop", LOG_DEBUG);

            if (!$image instanceof Image) {

                // not found - create new image
                // save new image to DB

                try {
                    $targetCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionImages());

                    $query = array(
                        Settings::getMongoKeyImageAttrId() => $imageId
                    );

                    $imageData = array(
                        Settings::getMongoKeyImageAttrTargetId() => $targetId,
                        Settings::getMongoKeyImageAttrFileNameSuffix() => $imageId,
                        Settings::getMongoKeyImageAttrWidth() => $width,
                        Settings::getMongoKeyImageAttrEffect() => $effect,
                        Settings::getMongoKeyImageAttrTsAdded() => new MongoTimestamp()
                    );

                    $update = array(
                        '$setOnInsert' => $imageData
                    );

                    $options = array(
                        'upsert' => true
                    );

                    $result = $targetCollection->update($query, $update, $options);

                    if(is_array($result)) {
                        if($result['n'] == true) {
                            $this->logger->log(__METHOD__, "new image created: " . $imageId, LOG_INFO);
                        }elseif($result['updatedExisting']) {
                            $this->logger->log(__METHOD__, "updated image " . $imageId . " instead of creating a new one. Works fine. :-)", LOG_INFO);
                        }
                    }
                } catch (\Exception $e) {
                    $this->logger->log(__METHOD__, "exception while creating image " . $width . "/" . $effect . " for target " . $targetId . ": " . $e->getMessage(), LOG_ERR);
                }

                $image = $this->getById($imageId);
            }
        }while(!$image instanceof Image);
        $this->logger->log(__METHOD__, "end loop", LOG_DEBUG);

        return $image;
    }



    public function incrementNumRequests($imageId)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $now = time();
        $today = date("Y-m-d", $now);

        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionImages());

            $imageQuery = array(
                Settings::getMongoKeyImageAttrId() => $imageId
            );

            $imageUpdate = array(
                '$inc' => array(
                    Settings::getMongoKeyImageAttrNumRequests()  => 1,
                    Settings::getMongoKeyImageAttrNumRequestsDaily() . '.' . $today => 1
                ),
                '$set' => array(
                    Settings::getMongoKeyImageAttrTsLastRequested()  => new MongoTimestamp()
                )
            );

            $collection->update($imageQuery, $imageUpdate);
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception while incrementing image daily request stats for " . $imageId . ": " . $e->getMessage(), LOG_ERR);
        }

        $this->logger->log(__METHOD__, "incremented image daily request stats for " . $imageId, LOG_DEBUG);

        //$this->incrementDailyRequests($imageId);

        //TODO: check result
        return true;
    }



    public function getNumRequests($imageId, $days)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $stats = array();

        $now = time();

        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionImages());

            $query = array(
                Settings::getMongoKeyImageAttrId() => $imageId,
                Settings::getMongoKeyImageAttrNumRequestsDaily() => array(
                    '$exists' => true
                )
            );

            $fields = array(
                Settings::getMongoKeyImageAttrNumRequestsDaily() => true
            );

            $imageData = $collection->findOne($query, $fields);

            if(is_array($imageData)) {
                //$this->logger->log(__METHOD__, "JOJO fuer " . $imageId . ": " . print_r($imageData, true), LOG_DEBUG);

                for ($i = 0; $i < $days; $i++) {
                    $day = date("Y-m-d", $now - (86400 * $i));
                    //$this->logger->log(__METHOD__, "check day: " . $day, LOG_DEBUG);
                    if (isset($imageData[Settings::getMongoKeyImageAttrNumRequestsDaily()][$day])) {
                        $this->logger->log(__METHOD__, "check day: " . $day . ": " . $imageData[Settings::getMongoKeyImageAttrNumRequestsDaily()][$day], LOG_DEBUG);
                        $stats[$day] = $imageData[Settings::getMongoKeyImageAttrNumRequestsDaily()][$day];
                    }
                }
            }

        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception while getting image daily request stats for " . $imageId . ": " . $e->getMessage(), LOG_ERR);
        }

        return $stats;
    }


    //TODO: use branded and unbranded images
    public function getAmazonS3presignedUrl(Target $target)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $redisKey = Settings::getRedisKeyImageAmazonS3url() . $target->getId() . $target->getCurrentImage()->getId();
        $resultUrl = null;

        if ($this->redis->exists($redisKey)) {
            $resultUrl = $this->redis->get($redisKey);
        }else {
            $imgFileName = $target->getFileNameBase() . $target->getCurrentImage()->getFileNameSuffix() . '.'
                . Settings::getImageFiletype($target->getCurrentImage()->getEffect());

            try {
                // Instantiate the S3 client with your AWS credentials
                $client = S3Client::factory(array(
                    'region' => Settings::getAmazonS3region(),
                    'credentials' => array(
                        'key' => Settings::getAmazonS3credentialsKey(),
                        'secret' => Settings::getAmazonS3credentialsSecret(),
                        'signature' => Settings::getAmazonS3credentialsSignature(),
                    )
                ));

                $rf = RequestFactory::getInstance();

                $rf->create(
                    'GET',
                    $client->getBaseUrl() . '/' . Settings::getAmazonS3bucketThumbnails() . '/' . $imgFileName
                )->setClient($client);

                $url = $client->createPresignedUrl($client->get(Settings::getAmazonS3bucketThumbnails() . '/' . $imgFileName), Settings::getAmazonS3presignedUrlExpireStr());

                if($url) {
                    $this->redis->set($redisKey, $url);
                    $this->redis->expire($redisKey, Settings::getAmazonS3presignedUrlExpireSeconds());
                    $resultUrl = $url;
                }else {
                    $this->logger->log(__METHOD__, "Invalid S3 pre-signed URL", LOG_ERR);
                }
            } catch (Exception $e) {
                $this->logger->log(__METHOD__, "Exception during creation of S3 pre-signed URL: " . $e->getMessage(), LOG_ERR);
            }
        }
        
        return $resultUrl;
    }


    public function prepareCachedImage(Target $target, $branded = FALSE)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $imageCacheKey = NULL;

        if ($branded) {
            $imageCacheKey = Settings::getRedisKeyImageCacheKeyBranded() . $target->getCurrentImage()->getId();
        } else {
            $imageCacheKey = Settings::getRedisKeyImageCacheKeyUnbranded() . $target->getCurrentImage()->getId();
        }

        if (!$this->redis->exists($imageCacheKey) || !$this->redis->exists(Settings::getRedisKeyImageCacheData() . $this->redis->get($imageCacheKey))) {
            $this->logger->log(__METHOD__, "could not find cached image in Redis", LOG_DEBUG);
            return $this->saveCachedImageToRedis($target->getCurrentImage(), $target, $branded);
        } else {
            $this->logger->log(__METHOD__, "found cached image in Redis", LOG_DEBUG);
            return $this->redis->get($imageCacheKey);
        }
    }



    private function deleteCachedImages($imageId)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $imageCacheKeyBranded = Settings::getRedisKeyImageCacheKeyBranded() . $imageId;
        $imageCacheKeyUnbranded = Settings::getRedisKeyImageCacheKeyUnbranded() . $imageId;

        if($this->redis->exists($imageCacheKeyBranded))
        {
            if($this->redis->exists(Settings::getRedisKeyImageCacheData() . $this->redis->get($imageCacheKeyBranded))) {
                $this->redis->del(Settings::getRedisKeyImageCacheData() . $this->redis->get($imageCacheKeyBranded));
                $this->logger->log(__METHOD__, "deleted branded image cache for " . $imageId, LOG_INFO);
            }
            $this->redis->del($imageCacheKeyBranded);
            $this->logger->log(__METHOD__, "deleted branded image cache key for " . $imageId, LOG_INFO);
        }

        if($this->redis->exists($imageCacheKeyUnbranded))
        {
            if($this->redis->exists(Settings::getRedisKeyImageCacheData() . $this->redis->get($imageCacheKeyUnbranded))) {
                $this->redis->del(Settings::getRedisKeyImageCacheData() . $this->redis->get($imageCacheKeyUnbranded));
                $this->logger->log(__METHOD__, "deleted unbranded image cache for " . $imageId, LOG_INFO);
            }
            $this->redis->del($imageCacheKeyUnbranded);
            $this->logger->log(__METHOD__, "deleted unbranded image cache key for " . $imageId, LOG_INFO);
        }
    }



    private function saveCachedImageToRedis(Image $image, Target $target, $branded = FALSE)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $base64 = NULL;

        $imageCacheKey = NULL;
        $cacheKey = Helpers::genRandomString(32);
        $imageDataKey = Settings::getRedisKeyImageCacheData() . $cacheKey;

        if ($branded) {
            $imageCacheKey = Settings::getRedisKeyImageCacheKeyBranded() . $image->getId();
            $this->logger->log(__METHOD__, "caching branded image data (" . $image->getId() . ")", LOG_INFO);
        } else {
            $imageCacheKey = Settings::getRedisKeyImageCacheKeyUnbranded() . $image->getId();
            $this->logger->log(__METHOD__, "caching unbranded image data (" . $image->getId() . ")", LOG_INFO);
        }

        try {
            $imageDataBase64 = null;

            //get from filesystem

            $imagePath = THUMBNAILS_DIR;

            //TODO: This is a workaround for images without the localPath attribute
            
            if($image->getLocalPath()) {
                $imagePath.= $image->getLocalPath();
            }else {
                $imagePath .= substr($target->getId(), 0, 1) . "/" . substr($target->getId(), 1, 1) . "/" . substr($target->getId(), 2, 1) . "/" . substr($target->getId(), 3, 1) . "/" .
                    $target->getFileNameBase() . $image->getFileNameSuffix() . '.' . Settings::getImageFiletype($image->getEffect());
            }
            
            $this->logger->log(__METHOD__, "retrieving thumbnail for image '". $image->getId() . "' from filesystem: " . $imagePath, LOG_DEBUG);
            $this->logger->log(__METHOD__, "image path: " . $imagePath, LOG_DEBUG);

            if(!file_exists($imagePath) || !filesize($imagePath) > 0)
            {
                throw new \Exception("image missing: " . $image->getId());
            }

            $imageData = file_get_contents($imagePath);
            $imageDataBase64 = base64_encode($imageData);

            if ($branded) {
                $imageDataBase64 = $this->addWatermark($imageDataBase64, Settings::getImageFiletype($image->getEffect()));
            }

            $cachedImage = new CachedImage();
            $cachedImage->setId($image->getId());
            $cachedImage->setTargetId($target->getId());
            $cachedImage->setImageData(utf8_encode($imageDataBase64));
            $cachedImage->setTsCaptured($image->getTsLastUpdated());
            $cachedImage->setWeapon($target->getWeapon());
            $cachedImage->setSnipeDuration($target->getSnipeDuration());
            $cachedImage->setFileType(Settings::getImageFiletype($image->getEffect()));

            $this->redis->set($imageDataKey, json_encode($cachedImage));
            $this->redis->expire($imageDataKey, Settings::getRedisImageCacheExpire());

            //FIXME: nur schreiben, wenn Datei wirklich in Redis geschrieben wurde

            $this->redis->set($imageCacheKey, $cacheKey);
            $this->redis->expire($imageCacheKey, Settings::getRedisImageCacheExpire() - (Settings::getRedisImageCacheExpire() / 10)); // expires before cached image

            return $cacheKey;
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, $e->getMessage(), LOG_ERR);
        }

        return false;
    }



    public function getMasterImage(Target $target)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        //FIXME: Fehler, wenn MasterImage nicht existiert

        $imageData = NULL;

        $redisTargetMastImageKey = Settings::getRedisKeyTargetMasterImageData() . $target->getId();

        if ($this->redis->exists($redisTargetMastImageKey)) {
            $imageData = $this->redis->get($redisTargetMastImageKey);
        }

        if ($imageData != NULL) {
            $this->logger->log(__METHOD__, "found master image data (" . $target->getId() . ")", LOG_DEBUG);
            return $imageData;
        } else {
            $this->logger->log(__METHOD__, "couldn't find master image data (" . $target->getId() . ")", LOG_ERR);
            return false;
        }
    }



    public function getCachedImage($cacheKey)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $cachedImage = NULL;
        $imageData = NULL;
        $imageDataKey = Settings::getRedisKeyImageCacheData() . $cacheKey;
        $target = NULL;

        if ($this->redis->exists($imageDataKey))
        {
            $cachedImageData = $this->redis->get($imageDataKey);
            $cachedImage = new CachedImage(json_decode($cachedImageData, true));
            $cachedImage->setImageData(utf8_decode($cachedImage->getImageData()));
            $cachedImage->setTtl($this->redis->ttl($imageDataKey) >= 0 ? $this->redis->ttl($imageDataKey) : null);

            if($cachedImage instanceof CachedImage)
            {
                $this->logger->log(__METHOD__, "found cached image data (" . $cacheKey . ")", LOG_INFO);

                return $cachedImage;
            }
        }

        $this->logger->log(__METHOD__, "invalid base64 image data for cache key '" . $cacheKey . "'", LOG_ERR);
        return false;
    }



    private function addWatermark($imageDataBase64, $fileType)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        if(!Settings::getFrontendImagesPathWatermark())
        {
            $this->logger->log(__METHOD__, "path for watermark image is missing", LOG_ERR);
            return $imageDataBase64;
        }

        $watermarkImagePath = WEB_IMAGES_DIR . Settings::getFrontendImagesPathWatermark();
        if(!file_exists($watermarkImagePath))
        {
            $this->logger->log(__METHOD__, "watermark image (" . $watermarkImagePath . ") is missing", LOG_ERR);
            return $imageDataBase64;
        }

        // Bilder laden

        $thumbnail = imagecreatefromstring(base64_decode($imageDataBase64));
        $banner = imagecreatefrompng($watermarkImagePath);

        // Bild Infos
        $thumbnailWidth = imagesx($thumbnail);
        $thumbnailHeight = imagesy($thumbnail);

        $bannerWidth = imagesx($banner);
        $bannerHeight = imagesy($banner);

        // Bilder erzeugen
        $output = imagecreatetruecolor($thumbnailWidth, $thumbnailHeight);

        // Transparenz
        imagesavealpha($output, true);

        $transColor = imagecolorallocatealpha($output, 0, 0, 0, 127);
        imagefill($output, 0, 0, $transColor);

        // Bild einfügen
        imagecopy($output, $thumbnail, 0, 0, 0, 0, $thumbnailWidth, $thumbnailHeight);

        // Wasserzeichen einfügen (bei Thumbnail-Breite <= 80 wird das Bild ganz nach unten in die Ecke gepackt)
        imagecopy($output, $banner, $thumbnailWidth - $bannerWidth - ($thumbnailWidth <= 80 ? 0 : 5), $thumbnailHeight - $bannerHeight - ($thumbnailWidth <= 80 ? 0 : 5), 0, 0, $bannerWidth, $bannerHeight);

        ob_start();

        switch($fileType)
        {
            case "jpeg":
                imagejpeg($output);
                break;

            case "png":
                imagepng($output);
                break;

            default:
                $this->logger->log(__METHOD__, "unknown fileType: " . $fileType, LOG_ERR);
                return false;
        }

        $imagevariable = ob_get_contents();
        ob_end_clean();

        // Speicher freigeben
        imagedestroy($output);

        return base64_encode($imagevariable);
    }



    private function getTsCheckedOut(Image $image)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionImages());

        $query = array(
            Settings::getMongoKeyImageAttrId() => $image->getId(),
            Settings::getMongoKeyImageAttrTsCheckedOut() => array(
                '$exists' => true
            )
        );
        $fields = array(
            Settings::getMongoKeyImageAttrTsCheckedOut() => true
        );

        $result = $collection->findOne($query, $fields);
        $tsCheckedOut = null;

        if($result != null)
        {
            //$this->logger->log(__METHOD__, "RESULT: " . print_r($result, true), LOG_DEBUG);

            if(isset($result[Settings::getMongoKeyImageAttrTsCheckedOut()]))
            {
                if($result[Settings::getMongoKeyImageAttrTsCheckedOut()] instanceof MongoTimestamp) {
                    /** @var MongoTimestamp $mongoTs */
                    $mongoTs = $result[Settings::getMongoKeyImageAttrTsCheckedOut()];
                    $tsCheckedOut = $mongoTs->sec;
                }
            }
        }

        return $tsCheckedOut;
    }


    //TODO: this shouldn't be named "dequeue", just "removeCheckout". Real "dequeue" needs to be implemented, if it doesn't already exist.
    public function dequeue(Image $image)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionImages());

            $query = array(
                Settings::getMongoKeyImageAttrId() => $image->getId()
            );

            $update = array(
                '$unset' => array(
                    Settings::getMongoKeyImageAttrTsCheckedOut() => ""
                )
            );

            if($collection->update($query, $update))
            {
                $this->logger->log(__METHOD__, "image " . $image->getId() . " dequeued successfully", LOG_INFO);
            }
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception while dequeuing image " . $image->getId() . ": " . $e->getMessage(), LOG_ERR);
        }
    }



    public function commit(Image $image)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionImages());

            $query = array(
                Settings::getMongoKeyImageAttrId() => $image->getId()
            );

            $update = array(
                '$set' => array(
                    Settings::getMongoKeyImageAttrTsLastUpdated() => new MongoTimestamp($image->getTsLastUpdated()),
                    Settings::getMongoKeyImageAttrHeight() => $image->getHeight()
                ),
                '$inc' => array(
                    Settings::getMongoKeyImageAttrCounterUpdated() => 1
                )
            );

            //TODO: should we delete local file during an image commit that doesn't contain a local path?
            if($image->getLocalPath()) {
                $update['$set'][Settings::getMongoKeyImageAttrLocalPath()] = $image->getLocalPath();
            }

            //TODO: should we delete AmazonS3 object during an image commit that doesn't contain an Amazon S3 url?
            if($image->getAmazonS3url()) {
                $update['$set'][Settings::getMongoKeyImageAttrAmazonS3url()] = $image->getAmazonS3url();
            }

            if($collection->update($query, $update))
            {
                $this->logger->log(__METHOD__, "image " . $image->getId() . " committed successfully", LOG_INFO);
            }

        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception while committing image " . $image->getId() . ": " . $e->getMessage(), LOG_ERR);
            return false;
        }

        //TODO: vielleicht ist das sofortige Löschen keine gute Idee. Besser auf das normale Cache-Ende warten?
        $this->deleteCachedImages($image->getId());
        $this->dequeue($image);

        if(!Settings::isEnergySaveActive())
        {
            $this->incrementImagesUpdatedDailyStats();
        }

        $this->logger->log(__METHOD__, "updated image (" . $image->getId() . ")", LOG_INFO);

        return true;
    }



    private function isEnqueued($imageId)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionQueueJobsThumbnails());

        $query = array(
            '_id' => $imageId
        );
        $fields = array(
            '_id' => true
        );

        $result = $collection->findOne($query, $fields);

        return $result != null;
    }



    private function isImageCheckedOut(Image $image)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $tsCheckedOut = $this->getTsCheckedOut($image);

        if ($tsCheckedOut != NULL) {
            if (!$this->isEnqueued($image->getId()) && $tsCheckedOut < (time() - Settings::getCheckoutExpire())) {
                $this->logger->log(__METHOD__, "image " . $image->getId() . ": checkout expired (not enqueued)", LOG_INFO);
                $this->removeCheckOut($image->getId());
                return false;
            } else {
                $this->logger->log(__METHOD__, "image " . $image->getId() . " is already checked out and enqueued", LOG_INFO);
                return true;
            }
        }else {
            return false;
        }
    }



    private function incrementImagesUpdatedDailyStats()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $now = time();
        $today = date("Y-m-d", $now);

        try {
            $statsCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionStatistics());

            $statsQuery = array(
                '_id' => 'images'
            );

            $statsUpdate = array(
                '$inc' => array(
                    'updated' . "." . $today => 1
                )
            );

            $statsOptions = array(
                'upsert' => true
            );

            if($statsCollection->update($statsQuery, $statsUpdate, $statsOptions)) {
                $this->logger->log(__METHOD__, "incremented updated images daily stats", LOG_DEBUG);
            }

        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception while incrementing updated images daily: " . $e->getMessage(), LOG_ERR);
        }
    }


    //FIXME: incrementImagesFailedDailyStats() is currently unused
    private function incrementImagesFailedDailyStats()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $now = time();
        $today = date("Y-m-d", $now);

        try {
            $statsCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionStatistics());

            $statsQuery = array(
                '_id' => 'images'
            );

            $statsUpdate = array(
                '$inc' => array(
                    'failed' . "." . $today => 1
                )
            );

            $statsOptions = array(
                'upsert' => true
            );

            if($statsCollection->update($statsQuery, $statsUpdate, $statsOptions)) {
                $this->logger->log(__METHOD__, "incremented failed images daily stats", LOG_INFO);
            }

        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception while incrementing failed images daily: " . $e->getMessage(), LOG_ERR);
        }
    }


    // Zeigt die Anzahl der Bilder ohne Filter für das Frontend (siehe getImages() )
    public function getNumImages($targetId = null, $where = null)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $numImages = null;

        //TODO: add $where

        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionImages());

            $query = array();

            if($targetId)
            {
                $query[Settings::getMongoKeyImageAttrTargetId()] = $targetId;
            }

            if ($where)
            {
                if($targetId) {
                    //TODO: ist es okay, das $query Array hier so neu aufzubauen?
                    $oldQuery = $query;
                    $query = array('$and' => array());
                    $query['$and'][] = $oldQuery;
                    $query['$and'][][Settings::getMongoKeyImageAttrFileNameSuffix()] = array(
                        '$regex' => $where,
                        '$options' => 'i'
                    );
                }else {
                    $query[Settings::getMongoKeyImageAttrFileNameSuffix()] = array(
                        '$regex' => $where,
                        '$options' => 'i'
                    );
                }
            }

            $numImages = $collection->count($query);
            $this->logger->log(__METHOD__, "loaded numImages (" . $numImages . ") for target " . $targetId . " from DB", LOG_DEBUG);
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "died (" . $e->getMessage() . ")", LOG_ERR);
            die($e->getMessage());
        }

        return $numImages;
    }



    public function getImages($targetId, $orderby = '_id', $orderDirection = 'asc', $limit = null, $offset = 0, $where = NULL)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $images = array();

        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionImages());

            $query = array(
                '$query' => array(
                    Settings::getMongoKeyImageAttrTargetId() => $targetId
                )
            );

            if ($orderDirection == "asc") {
                $query['$orderby'] = array(
                    $orderby => 1
                );
            } else {
                $query['$orderby'] = array(
                    $orderby => -1
                );
            }

            if ($where) {
                $query['$query']['$or'] = array(
                    array(
                        Settings::getMongoKeyImageAttrId() => array(
                            '$regex' => $where,
                            '$options' => 'i'
                        )
                    ),
                    array(
                        Settings::getMongoKeyImageAttrFileNameSuffix() => array(
                            '$regex' => $where,
                            '$options' => 'i'
                        )
                    )
                );
            }

            $fields = array(
                Settings::getMongoKeyImageAttrId() => true
            );

            //$this->logger->log(__METHOD__, "HIER: " . print_r($query, true), LOG_DEBUG);

            /** @var MongoCursor $cursor */
            $cursor = $collection->find($query, $fields);
            $cursor->skip($offset);
            if($limit) {
                $cursor->limit($limit);
            }

            foreach ($cursor as $doc) {
                $i = $this->getById($doc[Settings::getMongoKeyImageAttrId()]);
                //$this->logger->log(__METHOD__, "HIER: " . print_r($i, true), LOG_DEBUG);

                if ($i instanceof Image) {
                    $images[] = $i;
                }
            }
        }catch (\Exception $e) {
            $this->logger->log(__METHOD__, "could not find images of target " . $targetId . ": " . $e->getMessage(), LOG_DEBUG);
        }

        return $images;
    }



    public function getNextThumbnailJob($featuredEffects)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $imageId = null;

        if(!Settings::isEnergySaveActive())
        {
            $this->incrementThumbnailAgentConnectionsDailyStats();
        }

        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionQueueJobsThumbnails());

            $conditions = array();

            foreach($featuredEffects as $effect)
            {
                $cond = array(
                    'effect' => array(
                        '$regex' => $effect
                    )
                );

                $conditions[] = $cond;
            }

            $query = array(
                '$or' => $conditions
            );

            $fields = array(
                '_id' => true
            );

            $options = array(
                'remove' => true,
                'new' => false,
                'sort' => array(
                    'tsAdded' => 1,
                    'priority' => -1
                )
            );

            $targetData = $collection->findAndModify($query, null, $fields, $options);
            $imageId = $targetData['_id'];

        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "could not find next thumbnail job: " . $e->getMessage(), LOG_DEBUG);
            die();
        }

        if(!$imageId) {
            $this->logger->log(__METHOD__, "no next thumbnail job found", LOG_INFO);
        }

        return $imageId;
    }



    //FIXME: muss auf neue Queue zeigen
    public function getThumbnailJobQueueSize()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $imageId = null;

        try {
            $targetsCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionQueueJobsThumbnails());

            //FIXME: add more query options

            $query = array(
            );

            $queueSize = $targetsCollection->count($query);
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "could get queue size: " . $e->getMessage(), LOG_DEBUG);
            die();
        }

        return $queueSize;
    }



    private function incrementThumbnailAgentConnectionsDailyStats()
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $now = time();
        $today = date("Y-m-d", $now);

        try {
            $statsCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionStatistics());

            $statsQuery = array(
                '_id' => 'thumbnail_agents'
            );

            $statsUpdate = array(
                '$inc' => array(
                    'numRequests' . "." . $today => 1
                )
            );

            $statsOptions = array(
                'upsert' => true
            );

            if($statsCollection->update($statsQuery, $statsUpdate, $statsOptions)) {
                $this->logger->log(__METHOD__, "incremented thumbnail agent connections daily stats", LOG_DEBUG);
            }

        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception while incrementing thumbnail agent connections daily: " . $e->getMessage(), LOG_ERR);
        }
    }



    public function checkImageCurrentness(Target $target, Image $image, $forcedUpdate)
    {
        $this->logger->log(__METHOD__, '$target = ' . $target->getId() . ', $image = ' . $image->getId() . '$forcedUpdate = ' . $forcedUpdate, LOG_DEBUG);

        // dequeues if expired
        if(!$this->isImageCheckedOut($image))
        {
            $this->logger->log(__METHOD__, "image is not checked out", LOG_DEBUG);

            $image = $this->getById($image->getId());

            if($forcedUpdate || !$image->getTsLastUpdated() ||
                $image->getTsLastUpdated() < (time() - Helpers::getVariancedValue(Settings::getImageDefaultMaxAge(), Settings::getImageMaxAgeVariance())) ||
//FIXME: this may take a while
                (Settings::isLocalThumbnailStorageEnabled() && !$image->getLocalPath()) ||
                (Settings::isAmazonS3enabled() && !$image->getAmazonS3url())) {
                return false;
            }
        }

        // fresh -> not enqueued
        return true;
    }



    public function checkOut($imageId)
    {
        $this->logger->log(__METHOD__, '$imageId = ' . $imageId, LOG_DEBUG);

        $image = null;

        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionImages());

            $query = array(
                Settings::getMongoKeyImageAttrId() => $imageId
            );

            $update = array(
                '$set' => array(
                    Settings::getMongoKeyImageAttrTsCheckedOut() => new MongoTimestamp()
                ),
                '$inc' => array(
                    Settings::getMongoKeyImageAttrCounterCheckedOut() => 1
                )
            );

            $fields = array(
                Settings::getMongoKeyImageAttrId() => true
            );

            $options = array(
                'new' => true
            );

            $imageData = $collection->findAndModify($query, $update, $fields, $options);

            if(is_array($imageData))
            {
                $image = $this->getById($imageData[Settings::getMongoKeyImageAttrId()]);
            }

        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "could not find next thumbnail job: " . $e->getMessage(), LOG_DEBUG);
            die();
        }

        //$this->logger->log(__METHOD__, "HUHUHU: " . print_r($imageData, true), LOG_DEBUG);

        if($image instanceof Image) {
            $this->enqueue($image->getId(), $image->getEffect());
            //$this->logger->log(__METHOD__, "NEXTTARGET: " . print_r($target, true), LOG_ERR);
            return $image;
        }else {
            $this->logger->log(__METHOD__, "no next thumbnail job found", LOG_INFO);
            return false;
        }
    }



    private function removeCheckOut($imageId)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $image = null;

        try {
            $collection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionImages());

            $query = array(
                Settings::getMongoKeyImageAttrId() => $imageId
            );

            $update = array(
                '$unset' => array(
                    Settings::getMongoKeyImageAttrTsCheckedOut() => ''
                ),
            );

            $imageData = $collection->findAndModify($query, $update, null, null);
            $image = ImageModel::load($imageData);

        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "could not find queued item: " . $e->getMessage(), LOG_DEBUG);
            return false;
        }

        if($image instanceof Image) {
            return true;
        }else {
            return false;
        }
    }



    private function enqueue($imageId, $effect, $priority = 50)
    {
        $this->logger->log(__METHOD__, '$imageId = ' . $imageId . ', $priority = ' . $priority, LOG_DEBUG);

        try {
            $collection = new MongoCollection($this->mongoDB, Settings::getMongoCollectionQueueJobsThumbnails());

            $query = array(
                '_id' => $imageId
            );

            $data = array(
                'tsAdded' => new MongoTimestamp(),
                'priority' => $priority,
                'effect' => $effect
            );

            $update = array(
                '$setOnInsert' => $data
            );

            $options = array(
                'upsert' => true
            );

            $result = $collection->update($query, $update, $options);

            if (is_array($result)) {
                if ($result['n'] == true) {
                    $this->logger->log(__METHOD__, "new queue item created: " . $imageId, LOG_INFO);
                } elseif ($result['updatedExisting']) {
                    $this->logger->log(__METHOD__, "updated queue item " . $imageId . " instead of creating a new one. Works fine. :-)", LOG_ERR);
                }
            }
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception while creating queue item: " . $imageId . ": " . $e->getMessage() . "(Code: " . $e->getCode() . ")", LOG_ERR);
        }
    }



    public function createImageFile(Target $target, Image $image, $imageBase64)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $imgFileName = $target->getFileNameBase() . $image->getFileNameSuffix() . '.' . Settings::getImageFiletype($image->getEffect());

        $path = THUMBNAILS_DIR;

        try {
            if(!is_dir($path)) {
                if(!mkdir($path, 0770))
                {
                    $this->logger->log(__METHOD__, "error: " . $path . " is missing", LOG_CRIT);
                    return false;
                }
            }

            $dirLevels = array(
                substr($target->getId(), 0, 1),
                substr($target->getId(), 1, 1),
                substr($target->getId(), 2, 1),
                substr($target->getId(), 3, 1)
            );

            foreach ($dirLevels as $dirLevel)
            {
                $path .= $dirLevel . "/";

                if(!is_dir($path))
                {
                    if(!mkdir($path, 0770))
                    {
                        $this->logger->log(__METHOD__, "error: " . $path . " is missing", LOG_CRIT);
                        return false;
                    }
                }
            }

            // add fileName to $path and $tmpPath
            $path .= $imgFileName;
            $tmpPath = $path . ".tmp";

            $this->logger->log(__METHOD__, "creating image file " . $path, LOG_INFO);

            file_put_contents($tmpPath, base64_decode($imageBase64));

            if(file_exists($path)) {
                unlink($path);
            }

            rename($tmpPath, $path);
            chmod($path, 0640);
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "error while creating image file: " . $e, LOG_ERR);
            return false;
        }

        if ($this->imageFileExists($path)) {
            $this->logger->log(__METHOD__, "image created successfully", LOG_DEBUG);

            $relativePath = str_replace(THUMBNAILS_DIR, "", $path);

            return $relativePath;
        } else {
            $this->logger->log(__METHOD__, "created image not found in filesystem", LOG_ERR);
            return false;
        }
    }



    public function putS3(Target $target, Image $image, $imageBase64)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $imgFileName = $target->getFileNameBase() . $image->getFileNameSuffix() . '.' . Settings::getImageFiletype($image->getEffect());
        $resultUrl = null;

        try {
            // Instantiate the S3 client with your AWS credentials
            $client = S3Client::factory(array(
                'region' => Settings::getAmazonS3region(),
                'credentials' => array(
                    'key'    => Settings::getAmazonS3credentialsKey(),
                    'secret' => Settings::getAmazonS3credentialsSecret(),
                    'signature' => Settings::getAmazonS3credentialsSignature(),
                )
            ));

            // Upload an object to Amazon S3
            $result = $client->putObject(array(
                'Bucket' => Settings::getAmazonS3bucketThumbnails(),
                'Key'    => $imgFileName,
                'Body'   => base64_decode($imageBase64),
                'ContentType'  => 'image/' . Settings::getImageFiletype($image->getEffect()),
                'ACL'          => 'private',
            ));

            if($result && isset($result['ObjectURL'])) {
                $resultUrl = $result['ObjectURL'];
            }
        } catch (S3Exception $e) {
            $this->logger->log(__METHOD__, "Exception during S3 upload: " . $e->getMessage(), LOG_ERR);
        }

        if($resultUrl) {
            $this->logger->log(__METHOD__, "S3 upload successful", LOG_INFO);
        }

        return $resultUrl;
    }



    private function imageFileExists($fileName)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $exists = file_exists($fileName);
        $notEmpty = @filesize($fileName) > 0 ? true : false;

        if ($exists && $notEmpty) {
            $this->logger->log(__METHOD__, "image exists: " . $fileName, LOG_INFO);
        } else {
            $this->logger->log(__METHOD__, "image does not exist: " . $fileName, LOG_INFO);
        }

        return $exists;
    }


    public function deleteImageFile(Target $target, Image $image)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $path = THUMBNAILS_DIR;

        try {
            if(!is_dir($path)) {
                $this->logger->log(__METHOD__, "error: " . $path . " is missing", LOG_CRIT);
                return false;
            }

            //TODO: This is a workaround for images without the localPath attribute
            if($image->getLocalPath()) {
                $path.= $image->getLocalPath();
            }else {
                $dirLevels = array(
                    substr($target->getId(), 0, 1),
                    substr($target->getId(), 1, 1),
                    substr($target->getId(), 2, 1),
                    substr($target->getId(), 3, 1)
                );

                foreach ($dirLevels as $dirLevel) {
                    $path .= $dirLevel . "/";
                }

                // add fileName to $path and $tmpPath
                $imgFileName = $target->getFileNameBase() . $image->getFileNameSuffix() . '.' . Settings::getImageFiletype($image->getEffect());
                $path .= $imgFileName;
            }
            
            if ($this->imageFileExists($path)) {
                $this->logger->log(__METHOD__, "removing image file " . $path, LOG_INFO);
                unlink($path);
            }
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "error while removing image file: " . $e, LOG_ERR);
            return false;
        }

        if ($this->imageFileExists($path)) {
            $this->logger->log(__METHOD__, "could not remove image file", LOG_ERR);
            return false;
        } else {
            //$this->logger->log(__METHOD__, "(double check) image file " . $path . " does not exist", LOG_DEBUG);
            return true;
        }
    }


    public function delete(Target $target, Image $image)
    {
        $this->logger->log(__METHOD__, NULL, LOG_DEBUG);

        $this->deleteCachedImages($image->getId());
        $this->deleteImageFile($target, $image);

        try {
            $imageCollection = new \MongoCollection($this->mongoDB, Settings::getMongoCollectionImages());

            $query = array(
                Settings::getMongoKeyImageAttrId() => $image->getId()
            );

            $options = array(
                'justOne' => true
            );

            $result = $imageCollection->remove($query, $options);

            if(is_array($result)) {
                //$this->logger->log(__METHOD__, print_r($result, true), LOG_DEBUG);

                if($result['ok'] == true) {
                    if($result['n'] > 0) {
                        $this->logger->log(__METHOD__, "image removed: " . $image->getId(), LOG_INFO);
                    }else {
                        $this->logger->log(__METHOD__, "no image was removed (ok): " . $image->getId(), LOG_INFO);
                    }
                    return true;
                }else {
                    $this->logger->log(__METHOD__, "could not remove image " . $image->getId() . ": " . $result['err'] . " - " . $result['errmsg'], LOG_ERR);
                    return false;
                }
            }
        } catch (\Exception $e) {
            $this->logger->log(__METHOD__, "exception while removing image " . $image->getId() . ": " . $e->getMessage(), LOG_ERR);
            return false;
        }

        return false;
    }
}
