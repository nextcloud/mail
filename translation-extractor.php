<?php

/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

class TranslationExtractor {
	private $directory;
	
	public function __construct($directory) {
		$this->directory = $directory;
	}
	
	private function extractTranslationString($line) {
		$regex = '/\{\{\s*t\s*[\'\"]([\w\d\s,.]*)[\'\"]\s*\}\}/';
		$matches = [];
		preg_match_all($regex, $line, $matches);
		$strings = [];
		foreach ($matches[1] as $match) {
			$strings[] = $match;
		}
		return $strings;
	}
	
	private function readFile($file) {
		$strings = [];
		$content = file_get_contents($file);
		$lines = explode(PHP_EOL, $content);
		foreach ($lines as $line) {
			$strings = array_merge($strings, $this->extractTranslationString($line));
		}
		return $strings;
	}
	
	private function extract($directory) {
		$strings = [];
		$iterator = new DirectoryIterator($directory);
		foreach ($iterator as $node) {
			if ($node->isDot()) {
				continue;
			}
			if ($node->isDir()) {
				$this->extract($node);
			} else {
				$s = $this->readFile($node->getPathname());
				$strings = array_merge($strings, $s);
			}
		}
		return array_unique(array_values($strings));
	}
	
	public function run() {
		$strings = $this->extract($this->directory);
		$export = '';
		foreach ($strings as $s) {
			$export .= 't(\'' . $s . '\');' . PHP_EOL;
		}
		file_put_contents('translations.js', $export);
	}
}

$te = new TranslationExtractor(dirname(__FILE__) . '/js/templates');
$te->run();