<div id="firstrun">
    <h1><?php p($l->t("You don't have any email account configured yet.")) ?></h1>
    <div id="selections">
	    <fieldset id="addaccount_dialog_firstrun">
	        <legend style="margin-left:10px;"><img src="<?php print_unescaped(OCP\Util::imagePath('mail','mail.png')); ?>"> <?php p($l->t('Add email account')) ?></legend>
            <input type="email" id="email_address" placeholder="<?php p($l->t('E-Mail Address')); ?>" />
            <input type="password" id="password" placeholder="<?php p($l->t('IMAP Password')); ?>" />
	        <input type="submit" value="<?php p($l->t('Auto Detect')); ?>" id="auto_detect_account" />
	    </fieldset>
    </div>
    <div>
        <small><?php p($l->t('You can manage your email accounts here:')); ?></small>
	    <a class="button"><?php p($l->t('Settings')); ?></a>
	</div>
</div>
