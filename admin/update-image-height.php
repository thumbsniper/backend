<?php

ini_set('include_path', ".:/usr/share/php5:/usr/share/php5/PEAR:/opt/thumbsniper");

define('DIRECTORY_ROOT', dirname(__DIR__));

require_once(DIRECTORY_ROOT . '/config/backend-config.inc.php');

use ThumbSniper\common\Settings;
use ThumbSniper\api\ApiV3;
use ThumbSniper\shared\Target;
use ThumbSniper\shared\Image;


class UpdateImageHeight extends ApiV3
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
                echo $target->getUrl() . "\n";

                foreach ($imageModel->getImages($target->getId()) as $image) {
                    /** @var Image $image */
                    if ($image->getTsLastUpdated() == null || $image->getHeight() != null) {
                        continue;
                    }

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
                        $imageSize = getimagesize($path);
                        $image->setHeight($imageSize[1]);
                        echo $path . " height: " . $image->getHeight() . "\n";

                        if (!$this->updateHeight($this->getMongoDB(), $image)) {
                            echo "something went wrong\n";
                        }
                    }
                }
            }
        }
    }


    private function updateHeight($mongoDB, Image $image)
    {
        try {
            $collection = new MongoCollection($mongoDB, Settings::getMongoCollectionImages());

            $query = array(
                Settings::getMongoKeyImageAttrId() => $image->getId()
            );

            //TODO: $unset fileId is just to repair existing data, because fileId is obsolete - 2016-01-07

            $update = array(
                '$set' => array(
                    Settings::getMongoKeyImageAttrHeight() => $image->getHeight()
                ),
                '$unset' => array(
                    Settings::getMongoKeyImageAttrFileId() => ''
                )
            );

            if($collection->update($query, $update))
            {
                echo "image " . $image->getId() . " committed successfully (height: " . $image->getHeight() . ")\n";
            }
        } catch (Exception $e) {
            echo "exception while committing image " . $image->getId() . ": " . $e->getMessage() . "\n";
            return false;
        }

        return true;
    }
}


$updateHeight = new UpdateImageHeight(false);
$updateHeight->mainLoop();
