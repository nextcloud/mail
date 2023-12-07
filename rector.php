<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
	$rectorConfig->paths([
		__DIR__ . '/lib',
		__DIR__ . '/tests',
	]);
	$rectorConfig->skip([
		__DIR__ . '/lib/Vendor',
	]);
};
