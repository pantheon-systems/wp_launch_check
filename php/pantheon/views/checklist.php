<ul class="check-list">
<?php foreach($rows as $row): ?>
	<li class="severity-<?php echo htmlspecialchars($row['class']); ?>"><p class="result"><?php echo htmlspecialchars($row['message']); ?></p></li>
<?php endforeach; ?>
</ul>
