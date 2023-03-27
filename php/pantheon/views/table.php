<?php if(isset($fixed)): ?>
<?php $id = "database-table-" . rand(); ?>
<style>
	#<?php echo $id; ?>.tableFixHead          { overflow: auto; max-height: 600px; }
	#<?php echo $id; ?>.tableFixHead thead th { position: sticky; top: 0; z-index: 1; background-color: #E2E2E2; }
</style>
<div id="<?php echo $id; ?>" class="tableFixHead">
<?php endif; ?>

<table class='table table-condensed'>
	<thead>
		<tr><?php if(isset($headers)): ?><?php foreach ($headers as $header): ?><th><?php echo $header; ?></th><?php endforeach; ?><?php endif; ?></tr>
	</thead>
	<tbody>
	<?php if(isset($rows)): ?><?php foreach($rows as $row): ?>
		<tr class="<?php if(isset($row['class'])) { echo $row['class']; } ?>"><?php foreach($row['data'] as $values): ?><td><?php echo $values; ?></td><?php endforeach; ?></tr>
<?php endforeach; ?><?php endif; ?>
	</tbody>
</table>
<?php if(isset($fixed)): ?>
</div>
<?php endif; ?>
