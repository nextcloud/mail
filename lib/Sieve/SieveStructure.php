<?php

declare(strict_types=1);

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

namespace OCA\Mail\Sieve;

class SieveStructure {

	/** @var $installedExtensions */
	public $installedExtensions = [];

	/** @var $sieveActions */
	public $sieveActions = [];

	/** @var $sieveTestSubjects */
	public $sieveTestSubjects = [];

	/** @var $sieveOperators  */
	public $sieveListOperators = ['allof', 'anyof'];

	/** @var $sieveAddressPart  */
	public $sieveAddressParts = [];

	/** @var $sieveControls  */
	public $sieveControls = [
		'require', 'if', 'else', 'elseif'
	];

	/** @var $envelopePart  */
	public $sieveEnvelopeParts = [
		'from', 'to'
	];

	/** @var $sieveMatchTypes */
	public $sieveMatchTypes = [];

	/** @var $headers */
	public $headers = [
		'From',
		'To',
		'CC',
		'BCC',
		'Envelope-to',
		'Date',
		'Reply-To',
		'List-ID',
		'Subject',
	];

	/** @var $parameterTypes */
	public $parameterTypes = [
		'headers' => 'String',
		'keylist' => 'String',
		'envelopepart' => 'String',
		'mailbox' => 'String',
		'address' => 'String',
	];


	public function __construct() {
		$this->createSieveTestSubjects();
		$this->createSieveActions();
		$this->createSieveMatchTypes();
		$this->createSieveAddressParts();
	}

	/**
	 *
	 * @param array $sieveExtensions
	 *
	 * @return array;
	 */
	public function getSupportedStructure(array $sieveExtensions) : array {
		$this->installedExtensions = $sieveExtensions;
		$supportedStructure = [];
		$supportedActions = [];
		$supportedActions = array_filter($this->sieveActions, [ $this, "filterByExtension"]);
		$supportedAddressParts = array_filter($this->sieveAddressParts, [$this, "filterByExtension"]);
		$supportedMatchTypes = array_filter($this->sieveMatchTypes, [$this, "filterByExtension"]);
		$supportedTestSubjects = array_filter($this->sieveTestSubjects, [$this, "filterByExtension"]);
		$supportedStructure['sieveListOperators'] = $this->sieveListOperators;
		$supportedStructure['supportedAction'] = $supportedActions;
		$supportedStructure['supportedAddressParts'] = $supportedAddressParts;
		$supportedStructure['supportedMatchTypes'] = $supportedMatchTypes;
		$supportedStructure['supportedTestSubjects'] = $supportedTestSubjects;
		$supportedStructure['envelopeParts'] = $this->sieveEnvelopeParts;
		$supportedStructure['headers'] = $this->headers;
		return $supportedStructure;
	}

	/**
	 *
	 * @param object $sieveVerbObject
	 *
	 */
	public function filterByExtension($var) {
		return $var->extension === "" || in_array(strtoupper($var->extension),  $this->installedExtensions);
	}

	private function createSieveTestSubjects() {
		$this->sieveTestSubjects = [
			'address' => new SieveTestSubject('address', '', '%?comparator %?addresspart %matchtype %*headers %*keylist'),
			'envelope' => new SieveTestSubject('envelope', 'envelope', '%?comparator %?addresspart %matchtype %*envelopepart %*keylist'),
			'exists' => new SieveTestSubject('exists', '', '%*headers'),
			'header' => new SieveTestSubject('header', '', '%?comparator %matchtype %*headers %*keylist'),
			'size' => new SieveTestSubject('size', '', '%matchtype %size'),
		];
	}

	private function createSieveAddressParts() {
		$this->sieveAddressParts = [
			':localpart' => new SieveAddressPart(':localpart', '', ['address', 'envelope']),
			':domain' => new SieveAddressPart(':domain', '', ['address', 'envelope']),
			':all' => new SieveAddressPart(':all', '', ['address', 'envelope']),
		];
	}

	private function createSieveMatchTypes() {
		$this->sieveMatchTypes = [
			':contains' => new SieveMatchType(':contains', '', ['address', 'envelope', 'header']),
			':is' => new SieveMatchType(':is', 'envelope', ['address', 'envelope', 'header']),
			':matches' => new SieveMatchType(':matches', '', ['address', 'envelope', 'header']),
			':over' => new SieveMatchType(':over', '', ['size']),
			':under' => new SieveMatchType(':under', '', ['size']),
		];
	}

	private function createSieveActions() {
		$this->sieveActions =[
			'keep' => new SieveAction('keep'),
			'fileinto' => new SieveAction('fileinto', 'fileinto', '%mailbox'),
			'redirect' => new SieveAction('redirect', '', '%address'),
			'discard' =>  new SieveAction('discard', '', ''),
			'stop' =>  new SieveAction('stop', '', ''),
			//'notify' => new SieveAction('notify', 'notify', '%folder'),
			//'addheader' => new SieveAction('addheader', 'editheader', '%header'),
			//'deleteheader' => new SieveAction('deleteheader', 'editheader', '%header'),
			//'setflag' => new SieveAction('setflag', '', '%folder'),
			//'deleteflag' => new SieveAction('deleteflag', '', '%folder'),
			//'removeflag' => new SieveAction('removeflag', '', '%folder')
		];
	}
}
