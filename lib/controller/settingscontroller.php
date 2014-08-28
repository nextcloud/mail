<?php
/**
 * ownCloud - Mail app
 *
 * @author Steffen Lindner
 * @copyright 2014 Steffen Lindner mail@steffen-lindner.de
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

namespace OCA\Mail\Controller;


use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;

class SettingsController extends Controller {

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse renders the settings page
	 */
	public function index() {

        return new JSONResponse(array('test'));
	}

}