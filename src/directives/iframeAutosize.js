export const directive = {
	insterted(el) {
		window.onresize = resizeIframe(el)
		resizeIframe(el)
	},
}
function pageY(elem) {
	return elem.offsetParent ? elem.offsetTop + pageY(elem.offsetParent) : elem.offsetTop
}

function resizeIframe(el) {
	let height = document.documentElement.clientHeight
	height -= pageY(el)
	height = height < 0 ? 0 : height
	el.style.height = height + 'px'
}
export default directive
