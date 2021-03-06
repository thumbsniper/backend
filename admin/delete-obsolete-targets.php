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



class DeleteObsoleteTargets extends ApiV3
{
    public function mainLoop()
    {
        $targetModel = $this->getTargetModel();
        $imageModel = $this->getImageModel();

        try {
            $collection = new MongoCollection($this->getMongoDB(), Settings::getMongoCollectionTargets());
            $td = new DateTime();
            $td->modify('-8 months');

            $query = array(
                Settings::getMongoKeyTargetAttrTsLastRequested() => array(
                    '$lt' => new MongoTimestamp($td->getTimestamp())
                )
            );

            $fields = array(
                Settings::getMongoKeyTargetAttrId() => true
            );

            $cursor = $collection->find($query, $fields);

            foreach ($cursor as $doc) {
                $t = $targetModel->getById($doc[Settings::getMongoKeyTargetAttrId()]);

                if ($t instanceof Target) {
                    $images = $imageModel->getImages($t->getId());

                    if(!empty($images)) {
                        continue;
//                        /** @var Image $image */
//                        foreach($images as $image)
//                        {
//                            if($image->getTsLastUpdated()) {
//                                echo "  IMAGE  - " . date("d.m.Y H:i:s", $image->getTsLastRequested()) . " - " . $image->getId() . "\n";
////                                if($imageModel->deleteImageFile($t, $image))
////                                {
////                                    $imageModel->delete($t, $image);
////                                }
//                            }
//                        }
//                        echo "=======================\n";
                    }else {
                        echo "* TARGET - " . date("d.m.Y H:i:s", $t->getTsLastRequested()) . " - " . $t->getUrl() . " (" . $t->getId() . ")\n";
                        $this->setForceDebug(true);
                        $targetModel->delete($t->getId());
                        $this->setForceDebug(false);
                        echo "=======================\n";
                    }
                }
            }

        } catch (Exception $e) {
            echo "exception while searching for targets: " . $e->getMessage() . "\n";
            return false;
        }

        return true;
    }

}


$main = new DeleteObsoleteTargets(false);
$main->mainLoop();
