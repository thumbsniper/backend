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

use ThumbSniper\client\ThumbSniperClientSettings;

define('SMARTY_TEMPLATE_DIR', WEB_PANEL_DIR . '/templates/');
define('SMARTY_COMPILE_DIR', WEB_PANEL_DIR . '/templates_c/');
define('SMARTY_CONFIG_DIR', WEB_PANEL_DIR . '/configs/');
define('SMARTY_CACHE_DIR', WEB_PANEL_DIR . '/cache/');

ThumbSniperClientSettings::setTimezone("Europe/Berlin");
ThumbSniperClientSettings::setApiUrl("http://api.example.com");
//ThumbSniperClientSettings::setApiKey('secret');
