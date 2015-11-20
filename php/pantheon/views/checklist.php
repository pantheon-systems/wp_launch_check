<ul class="check-list">
<?php foreach($rows as $row): ?>
	<li class="severity-<?php echo $row['class']; ?>"><p class="result"><?php echo $row['message']; ?></p></li>
<?php endforeach; ?>
</ul>
