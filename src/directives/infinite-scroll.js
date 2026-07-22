/**
 * SPDX-FileCopyrightText: 2021-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015-2017 vue-infinite-scroll authors
 * SPDX-License-Identifier: MIT
 *
 * Vendored and fixed version of the abandoned https://github.com/ElemeFE/vue-infinite-scroll lib
 * Refactored for Vue 2.7 / Vue 3 dual compatibility.
 */

import { nextTick } from 'vue'

const ctx = '@@InfiniteScroll'

function throttle(fn, delay) {
	let now, lastExec, timer, context, args

	const execute = function() {
		fn.apply(context, args)
		lastExec = now
	}

	return function() {
		context = this
		args = arguments

		now = Date.now()

		if (timer) {
			clearTimeout(timer)
			timer = null
		}

		if (lastExec) {
			const diff = delay - (now - lastExec)
			if (diff < 0) {
				execute()
			} else {
				timer = setTimeout(() => {
					execute()
				}, diff)
			}
		} else {
			execute()
		}
	}
}

function getScrollTop(element) {
	if (element === window) {
		return Math.max(window.pageYOffset || 0, document.documentElement.scrollTop)
	}

	return element.scrollTop
}

const getComputedStyle = document.defaultView.getComputedStyle

function getScrollEventTarget(element) {
	let currentNode = element
	while (currentNode && currentNode.tagName !== 'HTML' && currentNode.tagName !== 'BODY' && currentNode.nodeType === 1) {
		const overflowY = getComputedStyle(currentNode).overflowY
		if (overflowY === 'scroll' || overflowY === 'auto') {
			return currentNode
		}
		currentNode = currentNode.parentNode
	}
	return window
}

function getVisibleHeight(element) {
	if (element === window) {
		return document.documentElement.clientHeight
	}

	return element.clientHeight
}

function getElementTop(element) {
	if (element === window) {
		return getScrollTop(window)
	}
	return element.getBoundingClientRect().top + getScrollTop(window)
}

function isAttached(element) {
	let currentNode = element.parentNode
	while (currentNode) {
		if (currentNode.tagName === 'HTML') {
			return true
		}
		if (currentNode.nodeType === 11) {
			return false
		}
		currentNode = currentNode.parentNode
	}
	return false
}

function doBind() {
	if (this.binded) {
		return
	}
	this.binded = true

	const directive = this
	const element = directive.el

	const throttleDelayExpr = element.getAttribute('infinite-scroll-throttle-delay')
	let throttleDelay = 200
	if (throttleDelayExpr) {
		throttleDelay = Number(directive.vm[throttleDelayExpr] || throttleDelayExpr)
		if (isNaN(throttleDelay) || throttleDelay < 0) {
			throttleDelay = 200
		}
	}
	directive.throttleDelay = throttleDelay

	directive.scrollEventTarget = getScrollEventTarget(element)
	directive.scrollListener = throttle(doCheck.bind(directive), directive.throttleDelay)
	directive.scrollEventTarget.addEventListener('scroll', directive.scrollListener)

	const disabledExpr = element.getAttribute('infinite-scroll-disabled')
	let disabled = false

	if (disabledExpr) {
		disabled = Boolean(directive.vm[disabledExpr])
	}
	directive.disabled = disabled

	const distanceExpr = element.getAttribute('infinite-scroll-distance')
	let distance = 0
	if (distanceExpr) {
		distance = Number(directive.vm[distanceExpr] || distanceExpr)
		if (isNaN(distance)) {
			distance = 0
		}
	}
	directive.distance = distance

	const immediateCheckExpr = element.getAttribute('infinite-scroll-immediate-check')
	let immediateCheck = true
	if (immediateCheckExpr) {
		immediateCheck = Boolean(directive.vm[immediateCheckExpr])
	}
	directive.immediateCheck = immediateCheck

	if (immediateCheck) {
		doCheck.call(directive)
	}
}

function doCheck(force) {
	const scrollEventTarget = this.scrollEventTarget
	const element = this.el
	const distance = this.distance

	if (force !== true && this.disabled) {
		return
	}
	const viewportScrollTop = getScrollTop(scrollEventTarget)
	const viewportBottom = viewportScrollTop + getVisibleHeight(scrollEventTarget)

	let shouldTrigger = false

	if (scrollEventTarget === element) {
		shouldTrigger = scrollEventTarget.scrollHeight - viewportBottom <= distance
	} else {
		const elementBottom = getElementTop(element) - getElementTop(scrollEventTarget) + element.offsetHeight + viewportScrollTop

		shouldTrigger = viewportBottom + distance >= elementBottom
	}

	if (shouldTrigger && this.expression) {
		this.expression()
	}
}

function onBind(el, binding, vnode) {
	el[ctx] = {
		el,
		vm: binding.instance ?? vnode.context,
		expression: binding.value,
	}

	nextTick(function() {
		if (isAttached(el)) {
			doBind.call(el[ctx])
		}

		el[ctx].bindTryCount = 0

		const tryBind = function() {
			if (el[ctx].bindTryCount > 10) {
				return
			}
			el[ctx].bindTryCount++
			if (isAttached(el)) {
				doBind.call(el[ctx])
			} else {
				setTimeout(tryBind, 50)
			}
		}

		tryBind()
	})
}

function onUnbind(el) {
	if (el && el[ctx] && el[ctx].scrollEventTarget) {
		el[ctx].scrollEventTarget.removeEventListener('scroll', el[ctx].scrollListener)
	}
}

export default {
	// Vue 2
	bind: onBind,
	unbind: onUnbind,
	// Vue 3
	mounted: onBind,
	unmounted: onUnbind,
}
