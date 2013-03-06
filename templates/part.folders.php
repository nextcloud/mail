<?php if (count($_['accounts']) > 0) { ?>
<?php foreach ($_['accounts'] as $account): ?>
    <h2 class="mail_account"><?php p($account['name']); ?></h2>
    <ul class="mail_folders" data-account_id="<?php p($account['id']); ?>">
        <!--	<li>--><?php //p($account['error']); ?><!--</li>-->
		<?php foreach ($account['folders'] as $folder): ?>
		<?php $unseen = $folder['unseen'] ?>
		<?php $total = $folder['total'] ?>
        <li data-folder_id="<?php p($folder['id']); ?>">
			<?php p($folder['name']); ?>
			<?php if ($total > 0) {
			p(" ($unseen/$total)");
		}?>
        </li>
		<?php endforeach; ?>
    </ul>
	<?php endforeach; ?>
<?php } ?>
