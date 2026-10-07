<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

/**
 * Oracle rejects SQL IN lists with more than 1000 expressions.
 * Chunk large ID sets at this size before building IN (...) clauses.
 *
 * TODO: prefer IQueryBuilder::MAX_IN_PARAMETERS once the mail app's
 * minimum Nextcloud Server version is 35+.
 */
final class OracleQueryLimits {
	public const MAX_IN_LIST_SIZE = 1000;
}
