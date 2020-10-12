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

 class SieveTestSubject extends SieveSyntaxItem {

	/** @var $parameters */
 	public $parameters;

 	public function __construct(String $name, String $extension = '', String $parameters) {
 		parent::__construct($name, $extension);
 		$this->parameters = $parameters;
 	}

 	public function __toString() {
 		return $this->name . "(" . $this->extension . ")";
 	}
 }
