<?php /** @var array $_ */?>
<?php /** @var OC_L10N $l */?>
<div class="error">
	<legend><strong><?php p($l->t('Error loading mail message'));?></strong></legend>
	<p><?php p($_['message']); ?></p>
</div>
