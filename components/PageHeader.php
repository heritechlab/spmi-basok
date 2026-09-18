<?php

$title=$title ?? '';

$subtitle=$subtitle ?? '';

$actions=$actions ?? [];

?>

<div class="page-header">

    <div>

        <h2>

            <?= $title; ?>

        </h2>

        <p>

            <?= $subtitle; ?>

        </p>

    </div>

    <div class="page-action">

        <?php foreach($actions as $button): ?>

            <a

                href="<?= $button['url']; ?>"

                class="<?= $button['class']; ?>">

                <i class="<?= $button['icon']; ?>"></i>

                <?= $button['text']; ?>

            </a>

        <?php endforeach; ?>

    </div>

</div>