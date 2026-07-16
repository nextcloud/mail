<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// Psalm stubs for the optional user_oidc app (https://github.com/nextcloud/user_oidc).
// Only the surface used by OCA\Mail\Integration\OidcIntegration is declared.

namespace OCA\UserOIDC\Model {
	class Token {
		public function getAccessToken(): string {
		}

		public function getRefreshToken(): ?string {
		}

		public function getExpiresInFromNow(): int {
		}

		public function isExpired(): bool {
		}
	}
}

namespace OCA\UserOIDC\Event {
	use OCA\UserOIDC\Model\Token;
	use OCP\EventDispatcher\Event;

	class ExternalTokenRequestedEvent extends Event {
		public function getToken(): ?Token {
		}

		public function setToken(?Token $token): void {
		}
	}
}

namespace OCA\UserOIDC\Db {
	class Provider {
		public function getId(): int {
		}

		public function getClientId(): string {
		}

		public function getClientSecret(): string {
		}

		public function getDiscoveryEndpoint(): ?string {
		}
	}

	class ProviderMapper {
		/**
		 * @return Provider[]
		 */
		public function getProviders(): array {
		}
	}
}
