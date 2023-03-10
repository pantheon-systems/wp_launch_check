<?php $type = (isset($type) && in_array($type, ['ol', 'ul'])) ? $type : 'ul' ?>
<?php if (isset($rows)): ?>
<<?php echo $type; ?> class="generic-list">
	<?php foreach($rows as $row): ?>
		<li><?php echo $row; ?></li>
	<?php endforeach; ?>
</<?php echo $type; ?>>
<?php endif; ?>
