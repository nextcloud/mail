<form id="mail-setup">
	<fieldset>
		<h2><?php p($l->t('Connect your mail account')) ?></h2>

		<p class="grouptop">
			<input type="text" name="mail-account-name" id="mail-account-name"
				   placeholder="<?php p($l->t('Name')); ?>"
				   value="<?php p(\OCP\User::getDisplayName(\OCP\User::getUser())); ?>"
				   autofocus autocomplete="off" required/>
			<label for="mail-account-name" class="infield"><?php p($l->t('Name')); ?></label>
		</p>
		<p class="groupmiddle">
			<input type="email" name="mail-address" id="mail-address"
				   placeholder="<?php p($l->t('Mail Address')); ?>"
				   value="<?php p(\OCP\Config::getUserValue(\OCP\User::getUser(), 'settings', 'email', '')); ?>"
				   autofocus autocomplete="off" required/>
			<label for="mail-address" class="infield"><?php p($l->t('Mail Address')); ?></label>
		</p>
		<p class="groupbottom">
			<input type="password" name="mail-password" id="mail-password"
				placeholder="<?php p($l->t('IMAP Password')); ?>" value="" />
			<label for="mail-password" class="infield"><?php p($l->t('IMAP Password')); ?></label>
		</p>
		<img id="connect-loading" src="<?php print_unescaped(OCP\Util::imagePath('core', 'loading.gif')); ?>" style="display:none;" />
		<input type="submit" id="auto_detect_account" class="connect primary" value="<?php p($l->t('Connect')); ?>"/>
	</fieldset>
</form>
