<?php
style('mail','mail');
style('mail','mobile');
script('mail','vendor/autosize/jquery.autosize');
script('mail', 'vendor/jQuery-Storage-API/jquery.storageapi');
script('mail', 'vendor/jquery-visibility/jquery-visibility');
script('mail', 'vendor/requirejs/require');
script('mail', 'require_config');
?>

<div id="app">
	<div id="app-navigation" class="icon-loading">
		<ul>
			<li id="mail-new-message-fixed">
			<input type="button" id="mail_new_message" class="icon-add"
				value="<?php p($l->t('New message')); ?>" style="display: none">
			</li>
			<li id="mail-new-message-list">
				<div id="folders"></div>
			</li>
		</ul>
		<div id="app-settings">
			<div id="app-settings-header">
				<button class="settings-button"
					data-apps-slide-toggle="#app-settings-content"></button>
			</div>
			<div id="app-settings-content"> </div>
		</div>
	</div>
	<div id="app-content">
		<div id="mail_messages" class="icon-loading">
		</div>
		<form id="mail-setup" class="hidden" method="post">
			<div class="hidden-visually">
				<!-- Hack for Safari and Chromium/Chrome which ignore autocomplete="off" -->
				<input type="text" id="fake_user" name="fake_user"
					autocomplete="off" tabindex="-1">
				<input type="password" id="fake_password" name="fake_password"
					autocomplete="off" tabindex="-1">
			</div>
			<fieldset>
				<div id="emptycontent">
					<div class="icon-mail"></div>
					<h2><?php p($l->t('Connect your mail account')) ?></h2>
				</div>
				<p class="grouptop">
					<input type="text" name="mail-account-name" id="mail-account-name"
						placeholder="<?php p($l->t('Name')); ?>"
						value="<?php p(\OCP\User::getDisplayName(\OCP\User::getUser())); ?>"
						autofocus />
					<label for="mail-address" class="infield"><?php p($l->t('Mail Address')); ?></label>
				</p>
				<p class="groupmiddle">
					<input type="email" name="mail-address" id="mail-address"
						placeholder="<?php p($l->t('Mail Address')); ?>"
						value="<?php p(\OCP\Config::getUserValue(\OCP\User::getUser(), 'settings', 'email', '')); ?>"
						required />
					<label for="mail-address" class="infield"><?php p($l->t('Mail Address')); ?></label>
				</p>
				<p class="groupbottom">
					<input type="password" name="mail-password" id="mail-password"
						placeholder="<?php p($l->t('IMAP Password')); ?>" value=""
						required />
					<label for="mail-password" class="infield"><?php p($l->t('IMAP Password')); ?></label>
				</p>

				<a id="mail-setup-manual-toggle" class="icon-caret-dark"><?php p($l->t('Manual configuration')); ?></a>

				<div id="mail-setup-manual" style="display:none;">
					<p class="grouptop">
						<input type="text" name="mail-imap-host" id="mail-imap-host"
							placeholder="<?php p($l->t('IMAP Host')); ?>"
							value="" />
						<label for="mail-imap-host" class="infield"><?php p($l->t('IMAP Host')); ?></label>
					</p>
					<p class="groupmiddle" id="mail-imap-ssl">
							<label for="mail-imap-sslmode"><?php p($l->t('IMAP security')); ?></label>
							<select name="mail-imap-sslmode" id="mail-imap-sslmode" title="<?php p($l->t('IMAP security')); ?>">
								<option value="none"><?php p($l->t('None')); ?></option>
								<option value="ssl"><?php p($l->t('SSL/TLS')); ?></option>
								<option value="tls"><?php p($l->t('STARTTLS')); ?></option>
							</select>
					</p>
					<p class="groupmiddle">
						<input type="number" name="mail-imap-port" id="mail-imap-port"
							placeholder="<?php p($l->t('IMAP Port')); ?>"
							value="143" />
						<label for="mail-imap-port" class="infield"><?php p($l->t('IMAP Port')); ?></label>
					</p>
					<p class="groupmiddle">
						<input type="text" name="mail-imap-user" id="mail-imap-user"
							placeholder="<?php p($l->t('IMAP User')); ?>"
							value="" />
						<label for="mail-imap-user" class="infield"><?php p($l->t('IMAP User')); ?></label>
					</p>
					<p class="groupbottom">
						<input type="password" name="mail-imap-password" id="mail-imap-password"
							placeholder="<?php p($l->t('IMAP Password')); ?>" value=""
							required />
						<label for="mail-imap-password" class="infield"><?php p($l->t('IMAP Password')); ?></label>
					</p>
					<p class="grouptop">
						<input type="text" name="mail-smtp-host" id="mail-smtp-host"
							placeholder="<?php p($l->t('SMTP Host')); ?>"
							value="" />
						<label for="mail-smtp-host" class="infield"><?php p($l->t('SMTP Host')); ?></label>
					</p>
					<p class="groupmiddle" id="mail-smtp-ssl">
						<label for="mail-smtp-sslmode"><?php p($l->t('SMTP security')); ?></label>
						<select name="mail-smtp-sslmode" id="mail-smtp-sslmode" title="<?php p($l->t('SMTP security')); ?>">
							<option value="none"><?php p($l->t('None')); ?></option>
							<option value="ssl"><?php p($l->t('SSL/TLS')); ?></option>
							<option value="tls"><?php p($l->t('STARTTLS')); ?></option>
						</select>
					</p>
					<p class="groupmiddle">
						<input type="number" name="mail-smtp-port" id="mail-smtp-port"
							placeholder="<?php p($l->t('SMTP Port')); ?>"
							value="587" />
						<label for="mail-smtp-port" class="infield"><?php p($l->t('SMTP Port (default 25, ssl 465)')); ?></label>
					</p>
					<p class="groupmiddle">
						<input type="text" name="mail-smtp-user" id="mail-smtp-user"
							placeholder="<?php p($l->t('SMTP User')); ?>"
							value="" />
						<label for="mail-smtp-user" class="infield"><?php p($l->t('SMTP User')); ?></label>
					</p>
					<p class="groupbottom">
						<input type="password" name="mail-smtp-password" id="mail-smtp-password"
							placeholder="<?php p($l->t('SMTP Password')); ?>" value=""
							required />
						<label for="mail-smtp-password" class="infield"><?php p($l->t('SMTP Password')); ?></label>
					</p>
				</div>

				<img id="connect-loading" src="<?php print_unescaped(OCP\Util::imagePath('core', 'loading.gif')); ?>" style="display:none;" />
				<input type="submit" id="auto_detect_account" class="connect primary" value="<?php p($l->t('Connect')); ?>"/>
			</fieldset>
		</form>
		<div id="mail-message" class="icon-loading hidden-mobile"></div>
	</div>
</div>

<?php print_unescaped($this->inc('part.mail-account')); ?>
<?php print_unescaped($this->inc('part.mail-attachment')); ?>
<?php print_unescaped($this->inc('part.mail-attachments')); ?>
<?php print_unescaped($this->inc('part.mail-composer')); ?>
<?php print_unescaped($this->inc('part.mail-folder')); ?>
<?php print_unescaped($this->inc('part.mail-message')); ?>
<?php print_unescaped($this->inc('part.mail-messages')); ?>
<?php print_unescaped($this->inc('part.mail-settings')); ?>
<?php print_unescaped($this->inc('part.mail-settings-account')); ?>
<?php print_unescaped($this->inc('part.message-list')); ?>
<?php print_unescaped($this->inc('part.no-search-results-message-list')); ?>
