import Vue from 'vue';
import jsdom from 'jsdom';
import Composer from '../../../components/Composer.vue';

const renderer = require('vue-server-renderer').createRenderer();

describe('Compose', () => {
	it('renders', () => {
		const Comp = Vue.extend(Composer);
		const composer = new Comp({
			methods: {
				t: (app, str) => str
			}
		}).$mount();
		renderer.renderToString(composer, (err, str) => {
			const dom = new jsdom.JSDOM(str);
			expect(dom).toBeDefined();
		});
	});
});
