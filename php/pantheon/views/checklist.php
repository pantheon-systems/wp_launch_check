<ul class="check-list">
<?php foreach($rows as $row): ?>
	<li class="severity-<?php require_once \WP_CLI::get_config('path') . '/wp-includes/formatting.php'; echo esc_attr($row['class']); ?>">
		<p class="result"><?php require_once \WP_CLI::get_config('path') . '/wp-includes/formatting.php'; echo wp_kses_post($row['message']); ?></p>
	</li>
<?php endforeach; ?>
</ul>
