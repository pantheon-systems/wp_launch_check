<table class='table table-condensed'>
	<thead>
			<tr>
				<?php if(isset($headers)): ?>
					<?php foreach ($headers as $header): ?>
						<th><?php echo htmlspecialchars($header); ?></th>
					<?php endforeach; ?>
				<?php endif; ?>
			</tr>
	</thead>
	<tbody>
			<?php foreach($rows as $row): ?>
				<tr class="<?php if(isset($row['class'])) { echo htmlspecialchars($row['class']); } ?>">
					<?php foreach($row['data'] as $values): ?>
						<td><?php echo htmlspecialchars($values); ?></td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
	</tbody>
</table>
