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

namespace OCA\Mail\Tests\Unit\Settings;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\AppInfo\Application;
use OCA\Mail\Settings\AdminSettings;
use OCP\AppFramework\Http\TemplateResponse;

class AdminSettingsTest extends TestCase {
	/** @var ServiceMockObject */
	private $serviceMock;

	/** @var AdminSettings */
	private $settings;

	protected function setUp(): void {
		parent::setUp();

		$this->serviceMock = $this->createServiceMock(AdminSettings::class);

		$this->settings = $this->serviceMock->getService();
	}

	public function testGetSection() {
		$section = $this->settings->getSection();

		$this->assertSame('groupware', $section);
	}

	public function testGetForm() {
		$this->serviceMock->getParameter('initialStateService')->expects($this->exactly(5))
			->method('provideInitialState')
			->withConsecutive(
				[
					Application::APP_ID,
					'provisioning_settings',
					$this->anything()
				],
				[
					Application::APP_ID,
					'antispam_setting',
					$this->anything()
				],
				[
					Application::APP_ID,
					'allow_new_mail_accounts',
					$this->anything()
				],
				[
					Application::APP_ID,
					'google_oauth_client_id',
					$this->anything()
				],
				[
					Application::APP_ID,
					'google_oauth_redirect_url',
					$this->anything()
				],
			);
		$expected = new TemplateResponse(Application::APP_ID, 'settings-admin');

		$form = $this->settings->getForm();

		$this->assertEquals($expected, $form);
	}

	public function testGetPriority() {
		$priority = $this->settings->getPriority();

		$this->assertIsInt($priority);
	}
}
