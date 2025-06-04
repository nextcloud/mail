<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
		$this->serviceMock->getParameter('initialStateService')->expects($this->exactly(14))
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
					'layout_message_view',
					$this->anything()
				],
				[
					Application::APP_ID,
					'llm_processing',
					$this->anything()
				],
				[
					Application::APP_ID,
					'enabled_llm_free_prompt_backend',
					$this->anything()
				],
				[
					Application::APP_ID,
					'enabled_llm_summary_backend',
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
				[
					Application::APP_ID,
					'microsoft_oauth_tenant_id',
					$this->anything()
				],
				[
					Application::APP_ID,
					'microsoft_oauth_client_id',
					$this->anything()
				],
				[
					Application::APP_ID,
					'microsoft_oauth_redirect_url',
					$this->anything()
				],
				[
					Application::APP_ID,
					'microsoft_oauth_docs',
					$this->anything()
				],
				[
					Application::APP_ID,
					'importance_classification_default',
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
