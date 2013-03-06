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

$host = isset( $_GET['host'] ) ? $_GET['host'] : null;
$user = isset( $_GET['user'] ) ? $_GET['user'] : null;
$email = isset( $_GET['email'] ) ? $_GET['email'] : null;
$password = isset( $_GET['password'] ) ? $_GET['password'] : null;
$port = isset( $_GET['port'] ) ? $_GET['port'] : null;
$ssl_mode = isset( $_GET['ssl_mode'] ) ? $_GET['ssl_mode'] : null;

$id = OCA\Mail\App::addAccount( OCP\User::getUser(), $email, $host, $port, $user, $password, $ssl_mode );

OCP\JSON::success(array('data' => array( 'id' => $id )));
