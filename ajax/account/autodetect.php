<?php
/**
 * ownCloud - Mail
 *
 * @author Thomas Müller
 * @copyright 2012 Thomas Müller
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

// Check if we are a user
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('mail');

// testing all possible configurations can take some time
set_time_limit(0);

$email_address = isset($_POST['email_address']) ? $_POST['email_address'] : null;
$password = isset( $_POST['password'] ) ? $_POST['password'] : null;
if (!$email_address || !filter_var($email_address, FILTER_VALIDATE_EMAIL)) {
	OCP\JSON::error(array('message' => 'email'));
	exit;
}

$id = OCA\Mail\App::autoDetectAccount( OCP\User::getUser(), $email_address, $password);
if ($id == null) {
	OCP\JSON::error(array('data' => array('message' => 'Auto detect failed. Please use manual mode.' )));
} else {
	OCP\JSON::success(array('data' => array( 'id' => $id )));
}
