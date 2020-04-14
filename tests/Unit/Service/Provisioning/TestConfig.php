<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\mail\tests\Unit\Service\Provisioning;

use OCA\Mail\Service\Provisioning\Config;

class TestConfig extends Config {
	public function __construct() {
		parent::__construct([
			'email' => '%USERID%@domain.com',
			'imapUser' => '%USERID%@domain.com',
			'imapHost' => 'mx.domain.com',
			'imapPort' => 993,
			'imapSslMode' => 'ssl',
			'smtpUser' => '%USERID%@domain.com',
			'smtpHost' => 'mx.domain.com',
			'smtpPort' => 567,
			'smtpSslMode' => 'tls',
		]);
	}
}
