<?php

/**
 * @author Bernhard Scheirle <bernhard+git@scheirle.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Scheirle <bernhard+git@scheirle.de>
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

namespace OCA\Mail\Service\AutoConfig;

use Exception;
use OCA\Mail\Service\Logger;

class IspDb {

	/** @var Logger */
	private $logger;

	/** @var string[] */
	public function getUrls() {
		return [
			'https://autoconfig.{DOMAIN}/mail/config-v1.1.xml',
			'https://{DOMAIN}/.well-known/autoconfig/mail/config-v1.1.xml',
			'https://autoconfig.thunderbird.net/v1.1/{DOMAIN}',
		];
	}

	/**
	 * @param Logger $logger
	 * @param string[] $ispUrls
	 */
	public function __construct(Logger $logger) {
		$this->logger = $logger;
	}

	private function queryUrl($url) {
		try {
			$content = @file_get_contents($url, false, stream_context_create([
				'http' => [
					'timeout' => 7
				]
			]));
			if ($content !== false) {
				$xml = @simplexml_load_string($content);
			} else {
				$this->logger->debug("IsbDb: <$url> request timed out");
				return [];
			}

			if (libxml_get_last_error() !== false || !is_object($xml) || !$xml->emailProvider) {
				libxml_clear_errors();
				return [];
			}
			$provider = [
				'displayName' => (string) $xml->emailProvider->displayName,
			];
			foreach ($xml->emailProvider->children() as $tag => $server) {
				if (!in_array($tag, ['incomingServer', 'outgoingServer'])) {
					continue;
				}
				foreach ($server->attributes() as $name => $value) {
					if ($name == 'type') {
						$type = (string) $value;
					}
				}
				$data = [];
				foreach ($server as $name => $value) {
					foreach ($value->children() as $tag => $val) {
						$data[$name][$tag] = (string) $val;
					}
					if (!isset($data[$name])) {
						$data[$name] = (string) $value;
					}
				}
				$provider[$type][] = $data;
			}
		} catch (Exception $e) {
			// ignore own not-found exception or xml parsing exceptions
			unset($e);
			$provider = [];
		}
		return $provider;
	}

	/**
	 * @param string $domain
	 * @return array
	 */
	public function query($domain, $tryMx = true) {
		$this->logger->debug("IsbDb: querying <$domain>");
		if (strpos($domain, '@') !== false) {
			// TODO: use horde mail address parsing instead
			list(, $domain) = explode('@', $domain);
		}

		$provider = [];
		foreach ($this->getUrls() as $url) {
			$url = str_replace("{DOMAIN}", $domain, $url);
			$this->logger->debug("IsbDb: querying <$domain> via <$url>");

			$provider = $this->queryUrl($url);
			if (!empty($provider)) {
				return $provider;
			}
		}

		if ($tryMx && ($dns = dns_get_record($domain, DNS_MX))) {
			$domain = $dns[0]['target'];
			if (!($provider = $this->query($domain, false))) {
				list(, $domain) = explode('.', $domain, 2);
				$provider = $this->query($domain, false);
			}
		}
		return $provider;
	}

}
