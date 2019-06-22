<template>
	<section>
		<h3>{{ t('mail', 'Sieve Settings') }}</h3>
		<label for="man-sieve-host">Sieve Host</label>
		<input
			id="man-sieve-host"
			v-model="config.host"
			type="text"
			:placeholder="t('mail', 'Sieve Host')"
			:disabled="loading"
			required
		/>

		<h4>{{ t('mail', 'Sieve Security') }}</h4>
		<div class="flex-row">
			<input id="man-sieve-sec-none" v-model="config.sslMode" type="radio" :disabled="loading" value="none" />
			<label class="button" for="man-sieve-sec-none" :class="{primary: config.sslMode === 'none'}">
				{{ t('mail', 'None') }}
			</label>
			<input id="man-sieve-sec-ssl" v-model="config.sslMode" type="radio" :disabled="loading" value="ssl" />
			<label class="button" for="man-sieve-sec-ssl" :class="{primary: config.sslMode === 'ssl'}">
				{{ t('mail', 'SSL/TLS') }}
			</label>
			<input id="man-sieve-sec-tls" v-model="config.sslMode" type="radio" :disabled="loading" value="tls" />
			<label class="button" for="man-sieve-sec-tls" :class="{primary: config.sslMode === 'tls'}">
				{{ t('mail', 'STARTTLS') }}
			</label>
		</div>

		<label for="man-sieve-port">{{ t('mail', 'Sieve Port') }}</label>
		<input
			id="man-sieve-port"
			v-model="config.port"
			type="number"
			:placeholder="t('mail', 'Sieve Port')"
			:disabled="loading"
			required
		/>

		<label for="man-sieve-user">{{ t('mail', 'Sieve User') }}</label>
		<input
			id="man-sieve-user"
			v-model="config.user"
			type="text"
			:placeholder="t('mail', 'Sieve User')"
			:disabled="loading"
			required
		/>

		<label for="man-sieve-password">{{ t('mail', 'Sieve Password') }}</label>
		<input
			id="man-sieve-password"
			v-model="config.password"
			type="password"
			:placeholder="t('mail', 'Sieve Password')"
			:disabled="loading"
			required
		/>
	</section>
</template>

<script>
export default {
	name: 'SieveForm',
	props: {
		config: {
			type: Object,
			required: true,
		},
		loading: {
			type: Boolean,
			required: true,
		},
	},
}
</script>
