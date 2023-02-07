<?= $this->escape($greeting ?? '') ?>

<?php foreach ($contents ?? [] as $content): ?>
<?= $this->escape($content ?? '') ?>
<?php endforeach; ?>

<?= $this->escape($footer ?? '') ?>

<?= $this->escape($signature ?? '') ?>