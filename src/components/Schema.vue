<!--
 -
 - @copyright Copyright (c) 2023, Gerke Frölje (gerke@audriga.com)
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
	<div class="schema">
		<div v-html="html"></div>
		<div class="schema-action-bar">
			<SchemaActionBar v-on:update-from-live-uri="updateData" />
		</div>
	</div>
</template>

<script>
import Jsonld2html from 'jsonld2html'
import SchemaActionBar from './SchemaActionBar'

export default {
	name: 'Schema',
	components: {
    	SchemaActionBar
	},
	props: {
		json: {
			type: Object,
			required: false,
		},
		messageId: {
			type: String,
			required: false,
		},
	},
	data: function() {
		return {
			html: ""
		};
	},
	created () {
		this.getRenderedSchema() 
	},
	methods: {
		getRenderedSchema() {
			const rendered = Jsonld2html.render(this.json)

			this.html = rendered

			return rendered
		},
		async updateData(updatedValues) {
			this.json["name"] = "Hollerallee 99, 28215 Bremen"

			for (const key in updatedValues) {
				if (this.data.hasOwnProperty(key)) {
					this.data[key] = updatedValues[key]
				}
			}

			this.getRenderedSchema()
		}
	}
}

</script>
<style scoped>
.schema {

	/* Box surrounding the displayed card and posible actions. */
	display: flex;
	width: fit-content;
	flex-direction: column;
	margin: 50px;
	border: 2px none var(--color-border);
	border-radius: 16px;
	padding: 10px;
	align-items: left;

	box-shadow: 0px 0px 10px 0px var(--color-box-shadow);
}

.full-schema {

	/* Useful for debugging the component. */
	display: none;
	font-size: x-small;
	opacity: 0.4;
	font-weight: lighter;

}

.schema >>> .smlCard {

	max-width: 600px;

	display: flex;
	flex-direction: column;

	gap: 5px;

	/* round corners*/
	border: 2px solid var(--color-border);
	border-radius: 6px;

	/* padding in the card*/
	padding: 20px;

	background: var(--color-main-background);


}

.schema >>> .smlCard .header {

	/* create a bottom border from left to right */
	border-block-end: 2px solid var(--color-border);
	margin: -10px -20px 0px -20px;

	padding-bottom: 10px;

	/* add spacing before text */
	text-indent: 20px;

	font-size: 20px;
	font-weight: bold;

	color: var(--color-main-text);

}

.schema >>> .smlCardRow {

	/*Layout Settings*/

	/* declaring the card class to a flex-contatiner */
	display: flex;

	/* setting the alignment of the childs to vertical row layout*/
	flex-direction: row;

	/* the items in the container are able to wrap, works like a line break */
	flex-wrap: nowrap;

	/* align the items horizontally in the cointainer to left side (flex-start) */
	justify-content: flex-start;

}

.schema >>> .smlCardRow .text_column {

	display: flex;

	/* setting the alignment of the childs to vertical row layout*/
	flex-direction: column;

	/* the items in the container are able to wrap, works like a line break */
	flex-wrap: nowrap;

	/* align the items horizontally in the cointainer to left side (flex-start) */
	justify-content: flex-start;

	/* align the items vertically in the center */
	align-items: flex-start;

	/* minimum height , same as the picture box*/
	min-height: 100px;
	max-height: 150px;

	flex-basis: 90%;

	/* this property is needed to make the truncating working for the child elements*/
	min-width: 0;

	margin-left: 20px

}

.schema >>> .smlCardRow .card_title {

	margin: 4px 0px;


	font-size: 20px;
	font-weight: bold;
	min-height: 20%;

	color: var(--color-main-text);

	/* settings for truncating single line text */
    max-width: 95%;
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow-x: auto;
	
}

.schema >>> .smlCardRow .card_content {

	margin-top: 4px;
	margin-bottom: 4px;

	font-size: 16px;

	color: var(--color-main-text);

	/* this is for truncating multiline texts*/
	display: -webkit-box;
	-webkit-box-orient: vertical;
	-webkit-line-clamp: 4;
	overflow: auto;

}

.schema >>> .smlCardRow .image_column {

	/* declaring the card class to a flex-contatiner */
	display: flex;

	/* align the items vertically in the center */
	align-items: center;

	/* align the items horizontally in the cointainer to center */
	justify-content: center;

	min-height: 100px;
	min-width: 100px;
	max-width: 100px;

	/* in case of bigger elements in the box, cut off the sides*/
	overflow: hidden;

}

.schema >>> .smlCardRow img {

	display: block;
	max-width: 100px;
	max-height: 100px;
	min-width: 100px;

}

.schema >>> br {
	display: none;
}

</style>