<tr id="mail_message_header">
	<td>
        <img src="<?php print_unescaped($_['message']['sender_image']); ?>" width="32px" height="32px" />
	</td>
    <td>
		<?php p($_['message']['from']); ?>
        <br/>
		<?php p($_['message']['subject']); ?>
	    <br/>
	    <?php
	    foreach( $_['message']['attachments'] as $a) {
			p($a['filename']." (".OCP\Util::humanFileSize($a['size']).")");
		}
	    ?>
    </td>
    <td>
        <img src="<?php print_unescaped(OCP\Util::imagePath('mail', 'reply.png')); ?>" />
        <img src="<?php print_unescaped(OCP\Util::imagePath('mail', 'reply-all.png')); ?>" />
        <img src="<?php print_unescaped(OCP\Util::imagePath('mail', 'forward.png')); ?>" />
        <br/>
	    <?php p(OCP\Util::formatDate($_['message']['date'])); ?>
	</td>
</tr>
<tr id="mail_message">
	<td colspan="3" class="mail_message_body">
		<?php print_unescaped($_['message']['body']); ?>
	</td>
</tr>
