<div class="row">

<?php foreach($standards as $row): ?>

<div class="col-lg-6 mb-4">

<div class="card border-0 shadow-sm h-100">

<div class="card-body">

<h5>

<?= $row['code']; ?>

</h5>

<h6 class="fw-bold">

<?= $row['name']; ?>

</h6>

<p class="text-muted">

<?= $row['indicator']; ?> indikator

</p>

<div class="progress mb-3"
     style="height:10px">

<div class="progress-bar bg-success"

style="width:<?= $row['progress']; ?>%">

</div>

</div>

<div class="d-flex
justify-content-between
align-items-center">

<span>

<?= $row['progress']; ?>%

</span>

<a href="audit.php?assignment=<?= $assignment['id']; ?>&standard=<?= $row['id']; ?>"

class="btn btn-primary btn-sm">

Audit

</a>

</div>

</div>

</div>

</div>

<?php endforeach; ?>

</div>