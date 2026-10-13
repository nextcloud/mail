<?php

/*
 * SPDX-FileCopyrightText: None
 * SPDX-License-Identifier: CC0-1.0
 */

namespace OC\Hooks;

interface Emitter {
	/**
	 * @param string $scope
	 * @param string $method
	 * @param callable $callback
	 * @return void
	 */
	public function listen($scope, $method, callable $callback);

	/**
	 * @param string $scope optional
	 * @param string $method optional
	 * @param callable $callback optional
	 * @return void
	 */
	public function removeListener($scope = null, $method = null, ?callable $callback = null);
}
