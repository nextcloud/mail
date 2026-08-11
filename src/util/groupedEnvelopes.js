/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export function groupEnvelopesByDate(envelopes, syncTimestamp, sortOrder = 'newest') {
	const now = new Date(syncTimestamp)

	const oneHourAgo = new Date(now.getTime() - 60 * 60 * 1000)
	const startOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate())
	const startOfYesterday = new Date(startOfToday)
	startOfYesterday.setDate(startOfYesterday.getDate() - 1)
	const startOfLastWeek = new Date(now)
	startOfLastWeek.setDate(startOfLastWeek.getDate() - 7)
	const startOfLastMonth = new Date(now)
	startOfLastMonth.setMonth(startOfLastMonth.getMonth() - 1)

	const groups = {
		lastHour: [],
		today: [],
		yesterday: [],
		lastWeek: [],
		lastMonth: [],
	}

	const monthsMap = {}
	const yearsMap = {}

	for (const envelope of envelopes) {
		const date = new Date(envelope.dateInt * 1000)

		if (date >= oneHourAgo) {
			groups.lastHour.push(envelope)
		} else if (date >= startOfToday) {
			groups.today.push(envelope)
		} else if (date >= startOfYesterday && date < startOfToday) {
			groups.yesterday.push(envelope)
		} else if (date >= startOfLastWeek) {
			groups.lastWeek.push(envelope)
		} else if (date >= startOfLastMonth) {
			groups.lastMonth.push(envelope)
		} else if (date.getFullYear() === now.getFullYear()) {
			const m = date.getMonth()
			monthsMap[m] ??= []
			monthsMap[m].push(envelope)
		} else {
			const y = date.getFullYear()
			yearsMap[y] ??= []
			yearsMap[y].push(envelope)
		}
	}

	const orderByDate = (a, b) => sortOrder === 'newest' ? b.dateInt - a.dateInt : a.dateInt - b.dateInt

	Object.values(groups).forEach((list) => list.sort(orderByDate))
	Object.values(monthsMap).forEach((list) => list.sort(orderByDate))
	Object.values(yearsMap).forEach((list) => list.sort(orderByDate))

	const groupOrder = []

	const fixedGroups = ['lastHour', 'today', 'yesterday', 'lastWeek', 'lastMonth']
	groupOrder.push(...(sortOrder === 'newest' ? fixedGroups : fixedGroups.toReversed()))

	const monthOrder = Object.keys(monthsMap).map(Number)
	monthOrder.sort((a, b) => (sortOrder === 'newest' ? b - a : a - b))
	for (const m of monthOrder) {
		const monthName = new Date(now.getFullYear(), m, 1)
			.toLocaleString('default', { month: 'long' })
		groups[monthName] = monthsMap[m]
		sortOrder === 'newest' ? groupOrder.push(monthName) : groupOrder.unshift(monthName)
	}

	const yearKeys = Object.keys(yearsMap).map(Number)
	yearKeys.sort((a, b) => (sortOrder === 'newest' ? b - a : a - b))
	for (const y of yearKeys) {
		groups[String(y)] = yearsMap[y]
		sortOrder === 'newest' ? groupOrder.push(String(y)) : groupOrder.unshift(String(y))
	}

	return groupOrder
		.filter((label) => groups[label] && groups[label].length > 0)
		.map((label) => [label, groups[label]])
}
