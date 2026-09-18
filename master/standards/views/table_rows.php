<?php if(empty($rows)): ?>

<tr>

    <td colspan="9" class="text-center">

        Tidak ada data.

    </td>

</tr>

<?php else: ?>

<?php foreach($rows as $row): ?>

<tr>

    <td><?= $row['no'] ?></td>

    <td><?= $row['code'] ?></td>

    <td><?= $row['category'] ?></td>

    <td><?= $row['name'] ?></td>

    <td><?= $row['published_at'] ?></td>

    <td><?= $row['revision'] ?></td>

    <td><?= $row['status'] ?></td>

    <td>

        <?= $row['document_count'] ?>

    </td>

    <td>

        ...

    </td>

</tr>

<?php endforeach; ?>

<?php endif; ?>