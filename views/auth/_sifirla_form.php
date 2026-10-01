<?php use App\Core\Csrf; ?>
<form method="post" action="/demo/sifirla" onsubmit="if (!confirm('Tüm veriler silinip demo verisi yeniden kurulacak. Emin misiniz?')) return false; this.querySelector('button').disabled = true; this.querySelector('button').innerText = 'Sıfırlanıyor...';">
    <?= Csrf::field() ?>
    <button type="submit" class="btn btn-outline-danger btn-sm w-100"><i class="mdi mdi-database-refresh-outline me-1"></i>Demo verisini sıfırla</button>
</form>
