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

$account_id = isset( $_GET['account_id'] ) ? $_GET['account_id'] : null;
$folder_id = isset( $_GET['folder_id'] ) ? $_GET['folder_id'] : null;
$message_id = isset( $_GET['message_id'] ) ? $_GET['message_id'] : null;
$body = isset( $_GET['body'] ) ? $_GET['body'] : null;

$account = OCA\Mail\App::getAccount( OCP\User::getUser(), $account_id);
if (!$account) {
	// TODO: i10n
	OCP\JSON::error(array('data' => array('message' => 'Unknown account' )));
	exit();
}

// in reply to handling
$mailbox = $account->getMailbox($folder_id);
$message = $mailbox->getMessage($message_id);

// get sender data
$headers = array();
$headers['From']= $account->getEMailAddress();
$headers['Subject'] = "RE: " . $message->getSubject();
$headers['In-Reply-To'] = $message->getMessageId();

// create transport and send
$transport = $account->createTransport();
$transport->send($message->getToEmail(), $headers, $body);

//
// TODO: save message to 'Sent' folder
// TODO: remove from drafts folder as well
//

OCP\JSON::success();
