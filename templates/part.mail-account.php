<script id="mail-account-template" type="text/x-handlebars-template">
	{{#if email}}
	<div class="mail-account-color" style="background-color: {{accountColor email}}"></div>
	{{/if}}
	<h2 class="mail-account-email" title="{{email}}">{{email}}</h2>
	<ul id="mail_folders" class="mail_folders with-icon" data-account_id="{{id}}">
	</ul>
</script>