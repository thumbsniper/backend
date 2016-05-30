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



class DeleteAmazonS3 extends ApiV3
{
    public function mainLoop()
    {
        $targetModel = $this->getTargetModel();
        $imageModel = $this->getImageModel();

        $target = $targetModel->getById('000232354e9ecda572420bd526a9c4af');
        $image = $imageModel->getById('8d6fef17f31da24cec53bf290d5d7ec2');

        //$imageModel->deleteAmazonS3ImageFile($target, $image);

        return true;
    }

}


$main = new DeleteAmazonS3(false);
$main->mainLoop();
