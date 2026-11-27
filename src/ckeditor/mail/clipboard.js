/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { plainTextToHtml, viewToPlainText } from 'ckeditor5'

const COPIED_LINE_ELEMENTS = ['p', 'div', 'li']
const LIST_INDENT = '  '

/**
 * Convert plain text to HTML with one paragraph per line, the way the
 * composer creates them when typing
 *
 * @param {string} text the plain text
 * @return {string}
 */
export function plainTextToLines(text) {
	return text.split(/\r?\n/)
		.map((line) => `<p>${plainTextToHtml(line)}</p>`)
		.join('')
}

/**
 * @param {import('ckeditor5').ViewNode|import('ckeditor5').ViewDocumentFragment} node the view node
 * @return {string} the text, with a line break for each <br>
 */
function getText(node) {
	if (node.is('$text')) {
		return node.data
	}
	if (node.is('element', 'br')) {
		return '\n'
	}
	return [...node.getChildren()].map(getText).join('')
}

/**
 * @param {import('ckeditor5').ViewNode} node the view node
 * @return {boolean} whether the node has no visible content
 */
function isBlank(node) {
	if (node.is('$text')) {
		return node.data.trim() === ''
	}
	return !node.is('element', 'img') && [...node.getChildren()].every(isBlank)
}

/**
 * @param {import('ckeditor5').ViewNode} node the view node
 * @param {string[]} blockElements names of block elements
 * @return {boolean} whether the node is a block or contains one
 */
function isBlockLevel(node, blockElements) {
	return node.is('element')
		&& (blockElements.includes(node.name) || [...node.getChildren()].some((child) => isBlockLevel(child, blockElements)))
}

/**
 * @param {import('ckeditor5').ViewNode} node the view node
 * @return {import('ckeditor5').ViewNode[]}
 */
function getLeaves(node) {
	if (!node.is('element') || node.isEmpty) {
		return [node]
	}
	return [...node.getChildren()].flatMap(getLeaves)
}

/**
 * Only preformatted text (<pre>, white-space: pre) still has line breaks
 * at this point. Turn them into <br>, as paragraphs collapse them.
 *
 * @param {import('ckeditor5').ViewElement|import('ckeditor5').ViewDocumentFragment} parent the element to process
 * @param {import('ckeditor5').UpcastWriter} writer the writer
 */
function splitPreformattedText(parent, writer) {
	for (const child of [...parent.getChildren()]) {
		if (child.is('element')) {
			splitPreformattedText(child, writer)
		} else if (child.is('$text') && child.data.includes('\n')) {
			const nodes = child.data.split('\n').flatMap((line, index) => [
				...(index > 0 ? [writer.createElement('br')] : []),
				...(line ? [writer.createText(line)] : []),
			])
			const index = parent.getChildIndex(child)
			writer.remove(child)
			writer.insertChild(index, nodes, parent)
		}
	}
}

/**
 * Browsers show no line for a <br> at the end of a block, the composer
 * does. Drop that <br>, and turn <br>s that stand alone between blocks into
 * empty paragraphs.
 *
 * @param {import('ckeditor5').ViewNode[]} run consecutive inline nodes
 * @param {boolean} isBetweenBlocks whether the run has blocks next to it
 * @param {import('ckeditor5').UpcastWriter} writer the writer
 */
function normalizeRun(run, isBetweenBlocks, writer) {
	const leaves = run.flatMap(getLeaves)
	if (isBetweenBlocks && leaves.every((leaf) => leaf.is('element', 'br'))) {
		const parent = run[0].parent
		const index = parent.getChildIndex(run[0])
		run.forEach((node) => writer.remove(node))
		writer.insertChild(index, leaves.map(() => writer.createElement('p')), parent)
	} else if (leaves.at(-1).is('element', 'br')) {
		writer.remove(leaves.at(-1))
	}
}

/**
 * @param {import('ckeditor5').ViewElement|import('ckeditor5').ViewDocumentFragment} parent the element to process
 * @param {import('ckeditor5').UpcastWriter} writer the writer
 * @param {string[]} blockElements names of block elements
 */
function normalizeLineBreaks(parent, writer, blockElements) {
	const children = [...parent.getChildren()]
	const hasBlocks = children.some((child) => isBlockLevel(child, blockElements))
	if (!hasBlocks && !(parent.is('element') && blockElements.includes(parent.name))) {
		return
	}

	let run = []
	for (const child of [...children, null]) {
		if (child && !isBlockLevel(child, blockElements)) {
			run.push(child)
			continue
		}

		if (run.length > 0) {
			normalizeRun(run, hasBlocks, writer)
			run = []
		}
		if (child) {
			normalizeLineBreaks(child, writer, blockElements)
		}
		// The <br> of a <div><br></div> line was dropped as a block filler
		if (child?.is('element', 'div') && child.isEmpty) {
			writer.rename('p', child)
		}
	}
}

/**
 * @param {import('ckeditor5').ViewElement} element a paragraph or div
 * @param {'top'|'bottom'} side the side of the margin
 * @return {boolean} whether the element has a margin on that side
 */
function hasMargin(element, side) {
	let margin = element.getStyle(`margin-${side}`)
	if (margin === undefined && element.hasStyle('margin')) {
		const values = element.getStyle('margin').trim().split(/\s+/)
		margin = side === 'top' ? values[0] : (values[2] ?? values[0])
	}

	const size = parseFloat(margin)
	return Number.isNaN(size) ? element.is('element', 'p') : size > 0
}

/**
 * @param {import('ckeditor5').ViewNode|null} node the view node
 * @param {string[]} blockElements names of block elements
 * @return {boolean} whether the node is a paragraph, or a div used as one
 */
function isParagraph(node, blockElements) {
	return Boolean(node?.is('element', 'p') || node?.is('element', 'div'))
		&& ![...node.getChildren()].some((child) => isBlockLevel(child, blockElements))
}

/**
 * Insert an empty paragraph between paragraphs that are apart because of
 * their margins. Apps like LibreOffice keep the margins in a stylesheet that
 * is not pasted, so lines that are adjacent in the plain text stay adjacent.
 *
 * @param {import('ckeditor5').ViewElement|import('ckeditor5').ViewDocumentFragment} parent the element to process
 * @param {import('ckeditor5').UpcastWriter} writer the writer
 * @param {string[]} blockElements names of block elements
 * @param {Set<string>} adjacentLines pairs of adjacent lines in the plain text
 */
function insertBlankLines(parent, writer, blockElements, adjacentLines) {
	let previous = null
	for (const child of [...parent.getChildren()]) {
		if (!isParagraph(child, blockElements)) {
			if (child.is('element')) {
				insertBlankLines(child, writer, blockElements, adjacentLines)
			}
		} else if (isParagraph(previous, blockElements) && !isBlank(previous) && !isBlank(child)
			&& (hasMargin(previous, 'bottom') || hasMargin(child, 'top'))
			&& !adjacentLines.has(linePair(getText(previous).split('\n').at(-1), getText(child).split('\n')[0]))) {
			writer.insertChild(parent.getChildIndex(child), writer.createElement('p'), parent)
		}
		previous = child
	}
}

/**
 * @param {string} first a line
 * @param {string} second the line after it
 * @return {string}
 */
function linePair(first, second) {
	return `${first.replace(/\s+/g, ' ').trim()}\n${second.replace(/\s+/g, ' ').trim()}`
}

/**
 * Change pasted HTML so that it shows the same lines in the composer, where
 * one paragraph is one line, as it did where it was copied from
 *
 * @param {import('ckeditor5').ViewDocumentFragment} fragment the pasted content
 * @param {import('ckeditor5').UpcastWriter} writer the writer
 * @param {string[]} blockElements names of block elements
 * @param {string} plainText the plain text version of the pasted content
 */
export function normalizePastedLines(fragment, writer, blockElements, plainText) {
	const lines = plainText.split(/\r?\n/)
	const adjacentLines = new Set()
	for (let i = 1; i < lines.length; i++) {
		if (lines[i - 1].trim() && lines[i].trim()) {
			adjacentLines.add(linePair(lines[i - 1], lines[i]))
		}
	}

	splitPreformattedText(fragment, writer)
	normalizeLineBreaks(fragment, writer, blockElements)
	insertBlankLines(fragment, writer, blockElements, adjacentLines)
}

/**
 * @param {import('ckeditor5').ViewElement} item a list item
 * @param {import('ckeditor5').UpcastWriter} writer the writer
 * @param {string[]} blockElements names of block elements
 * @param {string} marker the text to put in front of the item
 */
function insertListMarker(item, writer, blockElements, marker) {
	let target = item
	let first = target.getChild(0)
	while (first?.is('element') && blockElements.includes(first.name) && !first.is('element', 'ul') && !first.is('element', 'ol')) {
		target = first
		first = target.getChild(0)
	}
	writer.insertChild(0, writer.createText(marker), target)
}

/**
 * Write the markers of pasted lists as text, for editors without list
 * support. Unordered items get "- ", ordered items "1. " from the start
 * attribute on, and nested lists are indented.
 *
 * @param {import('ckeditor5').ViewElement|import('ckeditor5').ViewDocumentFragment} parent the element to process
 * @param {import('ckeditor5').UpcastWriter} writer the writer
 * @param {string[]} blockElements names of block elements
 * @param {number} depth the number of lists around the element
 */
export function addListMarkers(parent, writer, blockElements, depth = 0) {
	for (const child of [...parent.getChildren()]) {
		if (!child.is('element')) {
			continue
		}
		if (!child.is('element', 'ul') && !child.is('element', 'ol')) {
			addListMarkers(child, writer, blockElements, depth)
			continue
		}

		const start = parseInt(child.getAttribute('start'), 10)
		let number = Number.isNaN(start) ? 1 : start
		for (const item of [...child.getChildren()]) {
			if (!item.is('element', 'li')) {
				continue
			}
			const marker = child.is('element', 'ol') ? `${number++}. ` : '- '
			insertListMarker(item, writer, blockElements, LIST_INDENT.repeat(depth) + marker)
			addListMarkers(item, writer, blockElements, depth + 1)
		}
	}
}

/**
 * Get the blank lines CKEditor's viewToPlainText puts before a node
 * (copied from @ckeditor/ckeditor5-clipboard), except that paragraphs and
 * list items are on consecutive lines, as the composer shows them
 *
 * @param {import('ckeditor5').ViewNode} node the node
 * @param {import('ckeditor5').ViewNode|null} previous the node before it
 * @return {string}
 */
function newLinePadding(node, previous) {
	if (!previous) {
		return ''
	}
	if (COPIED_LINE_ELEMENTS.includes(node.name) && COPIED_LINE_ELEMENTS.includes(previous.name)) {
		return '\n'
	}
	const lists = ['ol', 'ul']
	if (lists.includes(node.name) && lists.includes(previous.name)) {
		return '\n\n'
	}
	if (!node.is('containerElement') && !previous.is('containerElement')) {
		return ''
	}
	if (node.name === 'figcaption' || previous.name === 'figcaption') {
		return '\n'
	}
	if ((node.is('element') && node.getCustomProperty('dataPipeline:transparentRendering'))
		|| (previous.is('element') && previous.getCustomProperty('dataPipeline:transparentRendering'))) {
		return ''
	}
	return '\n\n'
}

/**
 * Convert copied content to plain text with the lines the composer shows:
 * like CKEditor's viewToPlainText, but without a blank line between every
 * two paragraphs
 *
 * @param {import('ckeditor5').ViewDomConverter} converter the DOM converter, for raw elements
 * @param {import('ckeditor5').ViewNode|import('ckeditor5').ViewDocumentFragment} item the content to convert
 * @return {string}
 */
export function toPlainText(converter, item) {
	const hasOwnChildren = item.is('documentFragment')
		|| (item.is('element') && !item.is('rawElement') && !item.is('element', 'br') && !item.is('element', 'img'))
	if (!hasOwnChildren) {
		return viewToPlainText(converter, item)
	}

	let text = ''
	let previous = null
	for (const child of item.getChildren()) {
		text += newLinePadding(child, previous) + toPlainText(converter, child)
		previous = child
	}
	return text
}
