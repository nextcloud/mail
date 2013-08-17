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

$account_id = isset( $_POST['account_id'] ) ? $_POST['account_id'] : null;
$subject = isset( $_POST['subject'] ) ? $_POST['subject'] : null;
$body = isset( $_POST['body'] ) ? $_POST['body'] : null;
$to = isset( $_POST['to'] ) ? $_POST['to'] : null;

$account = OCA\Mail\App::getAccount( OCP\User::getUser(), $account_id);
if (!$account) {
	// TODO: i10n
	OCP\JSON::error(array('data' => array('message' => 'Unknown account' )));
	exit();
}

// get sender data
$headers = array();
$headers['From']= $account->getEMailAddress();
$headers['Subject'] = $subject;

// create transport and send
$transport = $account->createTransport();
$transport->send($to, $headers, $body);

//
// TODO: save message to 'Sent' folder
// TODO: remove from drafts folder as well
//

OCP\JSON::success();
