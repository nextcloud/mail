/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Lets an image with width and height attributes shrink without distortion,
 * keeping the ratio of the attributes rather than the image's natural one.
 *
 * @param image the image to adjust
 */
export function keepAttributeAspectRatio(image: HTMLImageElement): void {
	const width = Number(image.getAttribute('width'))
	const height = Number(image.getAttribute('height'))
	if (!(width > 0 && height > 0)) {
		return
	}

	// A height set by the message's own styles must win over the attribute.
	if (image.ownerDocument.defaultView?.getComputedStyle(image).height !== `${height}px`) {
		return
	}

	image.style.aspectRatio = `${width} / ${height}`
	image.style.height = 'auto'
}
