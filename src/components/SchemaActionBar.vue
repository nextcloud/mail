<!--
 -
 - @copyright Copyright (c) 2023, Gerke Frölje <gerke@audriga.com>
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
 - along with this program.  If not, see <https://www.gnu.org/licenses/>.
 -
 -->

<template>
    <div class="action-bar">
        <NcActions
        :force-name="true"
        :secondary="true"
        :inline="1" >
            <NcActionButton v-if="schemaType === 'Recipe'"
            :aria-label="t('mail', 'Add Recipe To Cookbook')"
            @click.prevent="sendRecipeToCookbook()" >     
                <template #icon>
                    <IconLoading v-if="recipeSendLoading"
					    :size="20" />
                    <SilverwareForkKnifeIcon v-else-if="recipeSendSuccess === null"
                        :size="20" />
                    <CheckIcon v-else-if="recipeSendSuccess === true"
                        :size="20" />
                    <CloseIcon v-else-if="recipeSendSuccess === false"
                        :size="20" />
                </template>
                Add Recipe To Cookbook
            </NcActionButton>
            <NcActionButton v-if="schemaType === 'Place' && hasLiveUri"
            :aria-label="t('mail', 'Refresh Live Location')"
            @click.prevent="refreshLiveUri()" >
                <template #icon>
                    <IconLoading v-if="refreshLocationLoading"
					    :size="20" />
                    <MapMarkerIcon v-else
                        :size="20" />
                </template>
                Refresh Live Location
            </NcActionButton>
            <NcActionButton v-if="schemaType === 'Place'"
            :aria-label="t('mail', 'Open in Google Maps')"
            @click.prevent="openLocationInGoogleMaps()" >
                <template #icon>
                    <MapSearchOutlineIcon
                        :size="20" />
                </template>
                Open in Google Maps
            </NcActionButton>
            <NcActionButton v-if="hasUrlValue"
                :aria-label="t('mail','Open Source URL')"
                @click.prevent="openUrlInNewWindow()" >
                <template #icon>
                    <OpenInNewIcon
                        :title="t('mail', 'Open Source URL')"
                        :size="20" />
                </template>
                Open Source URL
            </NcActionButton>
        </NcActions>
    </div>
</template>

<script>

import { NcActionButton } from '@nextcloud/vue'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import SilverwareForkKnifeIcon from 'vue-material-design-icons/SilverwareForkKnife'
import CheckIcon from 'vue-material-design-icons/Check'
import CloseIcon from 'vue-material-design-icons/Close'
import OpenInNewIcon from 'vue-material-design-icons/OpenInNew'
import MapMarkerIcon from 'vue-material-design-icons/MapMarker'
import MapSearchOutlineIcon from 'vue-material-design-icons/MapSearchOutline'
import IconLoading from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'

export default {
	name: 'SchemaActionBar',
	components: {
        NcActions,
	    NcActionButton,
	    SilverwareForkKnifeIcon,
        CheckIcon,
        CloseIcon,
        OpenInNewIcon,
        MapMarkerIcon,
        MapSearchOutlineIcon,
        IconLoading,
    },
    data: function() {
        return {
            recipeSendSuccess: null,
            recipeSendLoading: false,
            refreshLocationLoading: false,
        }
    },
    computed: {
        schemaType() {
            return this.$parent.json['@type']
        },
        hasUrlValue() {
            return this.$parent.json.hasOwnProperty('url')
        },
        hasLiveUri() {
            return this.$parent.json.hasOwnProperty('liveUri')
        }
    },
    methods: {
        async sendRecipeToCookbook () {
            try {
                this.recipeSendLoading = true;

				const success = this.$store.dispatch('sendRecipeToCookbook', {
						recipe: this.$parent.json,
				})

				success.then((value) => {
                    this.recipeSendSuccess = value
                    this.recipeSendLoading = false
                })

			} catch (e) {
                this.recipeSendLoading = false
				throw e
			}
		},
        refreshLiveUri() {
            
            try {
                this.refreshLocationLoading = true

                const result = this.$store.dispatch('callLiveUri', {
                    liveUri: this.$parent.json["liveUri"],
				})
                
				result.then((updatedValues) => { 
                    this.refreshLocationLoading = false
                    this.$emit('update-from-live-uri', updatedValues)
                })
			} catch (e) {
                this.refreshLocationLoading = false
				throw e
			}

        },
        openLocationInGoogleMaps() {
            const lat = this.$parent.json["geo"]["latitude"]
            const lon = this.$parent.json["geo"]["longitude"]

            const url = 'https://www.google.com/maps/search/?api=1&query=' + lat + ',' + lon

            window.open(url, '_blank').focus()
        },
        openUrlInNewWindow() {
            window.open(this.json["url"], '_blank').focus()
        },
    }
}
</script>

<style scoped>

.action-bar {
    display: flex;
    justify-content: flex-end;

    padding-top: 10px;
}

.action-bar:empty {
    display: none;
}

</style>