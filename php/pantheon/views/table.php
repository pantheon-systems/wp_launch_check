<table class='table table-condensed'>
	<thead>
			<tr>
				<?php if(isset($headers)): ?>
					<?php foreach ($headers as $header): ?>
						<th><?php require_once \WP_CLI::get_config('path') . '/wp-includes/formatting.php'; echo wp_kses_post($header); ?></th>
					<?php endforeach; ?>
				<?php endif; ?>
			</tr>
	</thead>
	<tbody>
			<?php foreach($rows as $row): ?>
				<tr class="<?php require_once \WP_CLI::get_config('path') . '/wp-includes/formatting.php'; if(isset($row['class'])) { echo esc_attr($row['class']); } ?>">
					<?php foreach($row['data'] as $values): ?>
						<td><?php require_once \WP_CLI::get_config('path') . '/wp-includes/formatting.php'; echo wp_kses_post($values); ?></td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
	</tbody>
</table>
