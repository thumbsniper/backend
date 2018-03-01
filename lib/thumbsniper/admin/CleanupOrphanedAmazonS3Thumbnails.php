<?php
/**
 *  Copyright (C) 2015  Thomas Schulte <thomas@cupracer.de>
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  Copyright (C) 2018  Thomas Schulte <thomas@cupracer.de>
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace ThumbSniper\cron;

use ThumbSniper\common\Settings;
use ThumbSniper\api\ApiV3;
use Exception;
use Aws\S3\S3Client;


class CleanupOrphanedAmazonS3Thumbnails extends ApiV3
{
    public function log($source, $msg, $severity)
    {
        $now = microtime();
        list($ms, $timestamp) = explode(" ", $now);
        $ms = substr($ms, 1, 5);

        echo date("Y-m-d H:i:s", $timestamp) . $ms . " - " . $source . " - " . $msg . "\n";
    }
    
    
    private function run()
    {
        $notFoundCounter = 0;

        try {
            $mongo = $this->getMongoDB(true);

            // Instantiate the S3 client with your AWS credentials
            $client = S3Client::factory(array(
                'region' => Settings::getAmazonS3region(),
                'credentials' => array(
                    'key' => Settings::getAmazonS3credentialsKey(),
                    'secret' => Settings::getAmazonS3credentialsSecret(),
                    'signature' => Settings::getAmazonS3credentialsSignature(),
                )
            ));

            $s3baseUrl = 'https://thumbsniper.s3.eu-central-1.amazonaws.com';

            $objects = $client->getIterator('ListObjects', array('Bucket' => Settings::getAmazonS3bucketThumbnails()));

            $this->log(__METHOD__, "Amazon S3 keys received", LOG_DEBUG);

            foreach ($objects as $object) {

                if(preg_match('/logs\//', $object['Key'])) {
                    continue;
                }

                $collection = new \MongoCollection($mongo, Settings::getMongoCollectionImages());
                $imageData = $collection->findOne(array('amazonS3url' => $s3baseUrl . "/" . $object['Key']));

                if(!is_array($imageData)) {
                    $notFoundCounter++;
                    $this->log(__METHOD__, "Going to delete: " . $s3baseUrl . "/" . $object['Key'], LOG_DEBUG);

                    $result = $client->deleteObject(array(
                        'Bucket' => Settings::getAmazonS3bucketThumbnails(),
                        'Key'    => $object['Key']
                    ));
                }
            }

        } catch (Exception $e) {
            $this->log(__METHOD__, "Exception while doing S3: " . $e->getMessage(), LOG_ERR);
        }

        echo "Counter (not found): " . $notFoundCounter . "\n";
    }
}
