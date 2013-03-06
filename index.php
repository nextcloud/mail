<?php

/**
* ownCloud - App Template Example
*
* @author Jakob Sack
* @copyright 2012 Jakob Sack mail@jakobsack.de
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
* You should have received a copy of the GNU Lesser General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

// Check if we are a user
if( !OCP\User::isLoggedIn()) {
	header( "Location: ".OCP\Util::linkTo( '', 'index.php' ));
	exit();
}

// Add JavaScript and CSS files
OCP\Util::addScript('mail','mail');
OCP\Util::addScript('mail','jquery.endless-scroll');
OCP\Util::addStyle('mail','mail');

OCP\App::setActiveNavigationEntry( 'mail');
$tmpl = new OCP\Template( 'mail', 'index', 'user' );
$tmpl->printPage();
