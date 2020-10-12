<?php

declare(strict_types=1);

namespace OCA\Mail\Sieve;

/**
 * @author Holger Dehnhardt <holger@dehnhardt.org>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

 class SieveSyntaxItem {
 	/** @var $name */
 	public $name;

 	/** @var $extension */
 	public $extension;

 	/**
 	 * @param String $name
 	 * @param String $extension
 	 */

 	public function __construct(String $name, String $extension = '') {
 		$this->name = $name;
 		$this->extension = $extension;
 	}

 	public function __toString() {
 		return $this->name . "(" . $this->extension . ")";
 	}
 }
