<?php 

use Pantheon\Utils; ?>

<table class='table table-condensed'>
	<thead>
			<tr>
				<?php if(isset($headers)): ?>
					<?php foreach ($headers as $header): ?>
						<th><?php echo Utils::sanitize_data( $header ); ?></th>
					<?php endforeach; ?>
				<?php endif; ?>
			</tr>
	</thead>
	<tbody>
			<?php foreach($rows as $row): ?>
				<tr class="<?php if(isset($row['class'])) { echo $row['class']; } ?>">
					<?php foreach($row['data'] as $values): ?>
						<td><?php echo Utils::sanitize_data( $values ); ?></td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
	</tbody>
</table>