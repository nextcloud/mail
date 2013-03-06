<?php
/**
 * ownCloud - Mail
 *
 * @author Thomas Müller
 * @copyright 2012 Thomas Müller <thomas.mueller@tmit.eu>
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

$term = isset( $_GET['term'] ) ? $_GET['term'] : null;

$receivers = OCA\Mail\App::getMatchingRecipient( $term );

if( isset($receivers['error']) ) {
	OCP\JSON::error(array('data' => array('message' => $receivers['error'] )));
	exit();
}

OCP\JSON::encodedPrint($receivers);
