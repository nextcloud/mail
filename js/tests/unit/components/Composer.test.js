import {shallowMount} from '@vue/test-utils'

import Composer from '../../../components/Composer.vue';

const renderer = require('vue-server-renderer').createRenderer();

describe('Compose', () => {
	it('calls draft and send callback', () => {
		const send = jest.fn();
		const draft = jest.fn();
		const composer = shallowMount(Composer, {
			methods: {
				t: (app, str) => str
			},
			propsData: {
				send,
				draft,
			}
		});
		renderer.renderToString(composer, (err, str) => {
			const submitBtn = composer.find('.submit-message');
			expect(submitBtn).toBeDefined();
			submitBtn.trigger('click');
			expect(send).toHaveBeenCalled();
		});
	});
});
