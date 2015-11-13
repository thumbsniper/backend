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

require_once('config/config.inc.php');
require_once('config/config-panel.inc.php');

require_once('vendor/autoload.php');

use ThumbSniper\common\Settings;
use ThumbSniper\frontend\Panel;

if(Settings::getMaintenance()) { die("MAINTENANCE"); }

/////////////////////////////////////

session_start();

$frontend = new Panel(false, SMARTY_TEMPLATE_DIR, SMARTY_COMPILE_DIR, SMARTY_CONFIG_DIR, SMARTY_CACHE_DIR);

$frontend->showQueueSizesJson();
