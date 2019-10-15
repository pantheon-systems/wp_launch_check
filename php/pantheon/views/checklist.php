<ul class="check-list">
<?php foreach($rows as $row): ?>
	<li class="severity-<?php echo esc_attr($row['class']); ?>">
		<p class="result"><?php echo wp_kses_post($row['message']); ?></p>
	</li>
<?php endforeach; ?>
</ul>
