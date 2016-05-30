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



class DeleteObsoleteTargets extends ApiV3
{
    public function mainLoop()
    {
        $targetModel = $this->getTargetModel();
        $imageModel = $this->getImageModel();

        try {
            $collection = new MongoCollection($this->getMongoDB(), Settings::getMongoCollectionTargets());
            $td = new DateTime();
            $td->modify('-12 months');
            $td->modify('+27 days');

            $query = array(
                Settings::getMongoKeyTargetAttrTsLastRequested() => array(
                    '$lt' => new MongoTimestamp($td->getTimestamp())
                )
            );

            $fields = array(
                Settings::getMongoKeyTargetAttrId() => true
            );

            $numTargets = $collection->count($query);
            echo "num target to delete: " . $numTargets . "\n";

            $cursor = $collection->find($query, $fields);
            
            foreach ($cursor as $doc) {
                $t = $targetModel->getById($doc[Settings::getMongoKeyTargetAttrId()]);

                if ($t instanceof Target) {
                    echo "(" . $numTargets . ") TARGET - " . date("d.m.Y H:i:s", $t->getTsLastRequested()) . " - " . $t->getUrl() . " (" . $t->getId() . ")\n";
//                    $images = $imageModel->getImages($t->getId());
//                    echo "\n";
//                    print_r($t);
//                    print_r($images);
//                    echo "\n";
                    $this->setForceDebug(true);
                    $targetModel->delete($t->getId());
                    $this->setForceDebug(false);
                    echo "=======================\n";
//                    break;
                    $numTargets--;
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
