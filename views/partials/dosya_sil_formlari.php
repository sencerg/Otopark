<?php foreach ($dosyalar as $d): ?>
    <form id="dosya-sil-<?= $d['id'] ?>" method="post" action="/dosya/sil/<?= $d['id'] ?>" data-confirm="Dosya silinsin mi?" class="d-none"><?= \App\Core\Csrf::field() ?></form>
<?php endforeach; ?>
