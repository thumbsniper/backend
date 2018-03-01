<?php
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

ini_set('include_path', ".:/usr/share/php5:/usr/share/php5/PEAR:/opt/thumbsniper");

define('DIRECTORY_ROOT', dirname(__DIR__));

require_once(DIRECTORY_ROOT . '/config/backend-config.inc.php');

use ThumbSniper\api\ApiV3;
use ThumbSniper\shared\Target;
use ThumbSniper\shared\Image;


class CreateIndexes extends ApiV3
{
    public function mainLoop()
    {

        $this->createMongoIndexes();
    }

//    public function deleteTarget($id)
//    {
//        $targetModel = $this->getTargetModel();
//        $targetModel->delete($id);
//    }

}


$main = new CreateIndexes(true);
//$main->deleteTarget("886e5c3d13187cc181da57242eb0139b");
$main->mainLoop();
