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

define('DIRECTORY_ROOT', dirname(dirname(__DIR__)));

require_once(DIRECTORY_ROOT . '/config/config.inc.php');
require_once(DIRECTORY_ROOT . '/config/config-panel.inc.php');

use ThumbSniper\common\Settings;
use ThumbSniper\frontend\Panel;

if(Settings::getMaintenance()) { die("MAINTENANCE"); }

/////////////////////////////////////

session_start();

$frontend = new Panel(false, SMARTY_TEMPLATE_DIR, SMARTY_COMPILE_DIR, SMARTY_CONFIG_DIR, SMARTY_CACHE_DIR);

//FIXME: GET-Variablen validieren und an diese Funktion übergeben

$draw = 0;
$length = 10;
$search = null;
$start = 0;
$orderColumn = "host";
$orderDirection = "ASC";

if(isset($_GET['draw']))
{
    $draw = (int)$_GET['draw'] > 0 ? (int)$_GET['draw'] : $draw;
}

if(isset($_GET['length']))
{
    $length = (int)$_GET['length'] > 0 ? (int)$_GET['length'] : $length;
}

if(isset($_GET['search']['value']))
{
    $search = $_GET['search']['value'] ? $_GET['search']['value'] : $search;
}

if(isset($_GET['start']))
{
    $start = $_GET['start'] ? (int)$_GET['start'] : $start;
}

if(isset($_GET['order'][0]['column']))
{
    switch((int)$_GET['order'][0]['column'])
    {
        case 1:
            $orderColumn = Settings::getMongoKeyTargetHostsBlacklistAttrId();
            break;

        case 2:
            $orderColumn = Settings::getMongoKeyTargetHostsBlacklistAttrHost();
            break;
    }
}

if(isset($_GET['order'][0]['dir']) && in_array($_GET['order'][0]['dir'], array("asc", "desc")))
{
    $orderDirection = $_GET['order'][0]['dir'];
}

$frontend->showTargetHostsBlacklistJson($draw, $start, $length, $search, $orderColumn, $orderDirection);
