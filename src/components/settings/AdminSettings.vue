<!--
  - @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
	<div id="mail-admin-settings" class="section">
		<h2>{{ t('mail', 'Mail app') }}</h2>
		<h3>{{ t('mail', 'The mail app allows users to read mails on their IMAP accounts.') }}</h3>
		<h3>
			{{
				t(
					'mail',
					'Here you can find instance-wide settings. User specific settings are found in the app itself (bottom-left corner).'
				)
			}}
		</h3>
		<ProvisioningSettings :settings="provisioningSettings" />
	</div>
</template>

<script>
import ProvisioningSettings from './ProvisioningSettings'

export default {
	name: 'AdminSettings',
	components: {
		ProvisioningSettings,
	},
	props: {
		provisioningSettings: {
			type: Object,
			required: true,
		},
	},
}
</script>
