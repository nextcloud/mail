<?php /** @var array $_ */?>
<?php /** @var \OCP\IL10N $l */?>
<div class="error">
	<legend><strong><?php p($l->t('Error loading message'));?></strong></legend>
	<p><?php p($_['message']); ?></p>
</div>
