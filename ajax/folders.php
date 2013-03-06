<?php
/**
 * ownCloud - Addressbook
 *
 * @author Thomas Tanghus
 * @copyright 2012 Jakob Sack <mail@jakobsack.de>
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

$accounts = OCA\Mail\App::getFolders( OCP\User::getUser());

foreach($accounts as $account) {
	if( isset($account['error']) ) {
		OCP\JSON::error(array('data' => array('message' => $account['error'] )));
		exit();
	}
}

$tmpl = new OCP\Template('mail','part.folders');
$tmpl->assign('accounts', $accounts);
$page = $tmpl->fetchPage();

OCP\JSON::success(array('data' => $page ));
