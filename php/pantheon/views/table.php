<table>
  <thead>
      <tr>
        <?php if(isset($headers)): ?>
          <?php foreach ($headers as $header): ?>
            <th><?php echo $header; ?></th>
          <?php endforeach; ?>
        <?php endif; ?>
      </tr>
  </thead>
  <tbody>
      <?php foreach($rows as $row): ?>
        <tr>
          <?php foreach($row as $values): ?>
            <td><?php echo $values; ?></td>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
  </tbody>
</table>
