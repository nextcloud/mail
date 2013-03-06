<div id="mail_editor" title="<?php p($l->t('New Message')); ?>">
    <form>
        <input type="text" name="to" id="to" placeholder="<?php p($l->t('To')); ?>"/>
        <input type="text" name="subject" id="subject" placeholder="<?php p($l->t('Subject')); ?>"/>
<!--        <img style="display: none;" id="wait" src="--><?php //print_unescaped(OCP\Util::imagePath('core', 'loading.gif');= ?><!--" />-->
        <textarea name="body" id="body"></textarea>
    </form>
</div>

<script type="text/javascript" src="<?php print_unescaped(OC_Helper::linkToRoute('mail_editor'));?>"></script>