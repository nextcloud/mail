<?php if (count($_['accounts']) > 0) { ?>
<?php foreach ($_['accounts'] as $account): ?>
	<h2 class="mail_account"><?php p($account['email']); ?></h2>
	<ul class="mail_folders" data-account_id="<?php p($account['id']); ?>">
		<?php foreach ($account['folders'] as $folder): ?>
		<?php $unseen = $folder['unseen'] ?>
		<li data-folder_id="<?php p($folder['id']); ?>"
			<?php if ($unseen > 0) {
			print_unescaped('class="unread"');
		}?>
				>
			<?php p($folder['name']); ?>
			<span class="unread-count">
			<?php if ($unseen > 0) {
				p($unseen);
			}?>
			</span>
		</li>
		<?php endforeach; ?>
	</ul>
	<?php endforeach; ?>
<?php } ?>
