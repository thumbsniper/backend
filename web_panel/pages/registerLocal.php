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

require_once(DIRECTORY_ROOT . '/config/backend-config.inc.php');
require_once(DIRECTORY_ROOT . '/config/panel-config.inc.php');

use ThumbSniper\common\Settings;
use ThumbSniper\frontend\Panel;

if(Settings::getMaintenance()) { die("MAINTENANCE"); }

/////////////////////////////////////

session_start();

$frontend = new Panel(false, SMARTY_TEMPLATE_DIR, SMARTY_COMPILE_DIR, SMARTY_CONFIG_DIR, SMARTY_CACHE_DIR);

$action = "view";
$data = null;

if(isset($_POST['firstName']) && isset($_POST['lastName']) && isset($_POST['email']))
{
    $action = "register";

    //TODO: validate
    $data['firstName'] = trim($_POST['firstName']);
    $data['lastName'] = trim($_POST['lastName']);
    $data['email'] = strtolower(trim($_POST['email']));

	if($frontend->isValidFirstName($data['firstName']) && $frontend->isValidLastName($data['lastName']) &&
		$frontend->isValidEmail($data['email']))
	{
		$frontend->showRegisterPage($action, $data);
	}else
	{
		Panel::addUserMessage('danger', 'Invalid values were submitted. Please try again!');
	}

}elseif(isset($_GET['action']) && $_GET['action'] == "cancel") {
	$frontend->showRegisterPage("cancel", null);
}else {
	//TODO: show error
}
