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

ini_set('include_path', ".:/usr/share/php5:/usr/share/php5/PEAR:/opt/thumbsniper");

define('DIRECTORY_ROOT', dirname(__DIR__));

require_once(DIRECTORY_ROOT . '/config/backend-config.inc.php');

use ThumbSniper\common\Settings;
use ThumbSniper\api\ApiV3;
use ThumbSniper\shared\Target;
use ThumbSniper\shared\Image;


class UpdateImageLocalPath extends ApiV3
{
    public function mainLoop()
    {
        $targetModel = $this->getTargetModel();
        $imageModel = $this->getImageModel();

        for($i = 0; $i < $targetModel->getNumTargets(); $i = $i+100)
        {
            echo "==== offset: " . ($i > 0 ? $i-1 : $i) . " ====\n";
            foreach($targetModel->getTargets("_id", "asc", 100, ($i > 0 ? $i-1 : $i)) as $target) {
                /** @var Target $target */
                
                foreach ($imageModel->getImages($target->getId()) as $image) {
                    /** @var Image $image */
                    if ($image->getTsLastUpdated() == null || $image->getLocalPath()) {
                        continue;
                    }

                    echo $target->getUrl() . "\n";
                    
                    $path = THUMBNAILS_DIR;
                    $imgFileName = $target->getFileNameBase() . $image->getFileNameSuffix() . '.' . Settings::getImageFiletype($image->getEffect());

                    $dirLevels = array(
                        substr($target->getId(), 0, 1),
                        substr($target->getId(), 1, 1),
                        substr($target->getId(), 2, 1),
                        substr($target->getId(), 3, 1)
                    );

                    foreach ($dirLevels as $dirLevel) {
                        $path .= $dirLevel . "/";
                    }

                    $path .= $imgFileName;

                    if (file_exists($path)) {
                        $relativePath = str_replace(THUMBNAILS_DIR, "", $path);
                        $image->setLocalPath($relativePath);

                        if (!$this->updateLocalPath($this->getMongoDB(), $image)) {
                            echo "something went wrong\n";
                        }
                    }else {
                        echo "File " . $path . " does not exist.\n";
                    }
                }
            }
        }
    }


    private function updateLocalPath($mongoDB, Image $image)
    {
        try {
            $collection = new MongoCollection($mongoDB, Settings::getMongoCollectionImages());

            $query = array(
                Settings::getMongoKeyImageAttrId() => $image->getId()
            );

            $update = array(
                '$set' => array(
                    Settings::getMongoKeyImageAttrLocalPath() => $image->getLocalPath()
                )
            );

            if($collection->update($query, $update))
            {
                echo "image " . $image->getId() . " committed successfully (localPath: " . $image->getLocalPath() . ")\n";
            }
        } catch (Exception $e) {
            echo "exception while committing image " . $image->getId() . ": " . $e->getMessage() . "\n";
            return false;
        }

        return true;
    }
}


$updateHeight = new UpdateImageLocalPath(false);
$updateHeight->mainLoop();
