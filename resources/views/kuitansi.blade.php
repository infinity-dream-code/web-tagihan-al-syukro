@php
    $bulanIndo = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    $formatPeriodeBulan = function ($periode) use ($bulanIndo) {
        $raw = trim((string) $periode);
        if (!preg_match('/^\d{6}$/', $raw)) {
            return '';
        }
        $bulan = (int) substr($raw, 4, 2);
        if (!isset($bulanIndo[$bulan])) {
            return '';
        }
        return $bulanIndo[$bulan] . ' ' . substr($raw, 0, 4);
    };

    $deskripsiPembayaran = function ($item) use ($formatPeriodeBulan) {
        $nama = trim((string) ($item['nama_tagihan'] ?? '-'));
        $periode = $formatPeriodeBulan($item['periode'] ?? $item['BILLAC'] ?? '');
        if ($periode === '') {
            return $nama;
        }
        return $nama . (stripos($nama, 'bulan') !== false ? ' ' : ' Bulan ') . $periode;
    };

    $padFaktur = function ($value) {
        $digits = preg_replace('/\D/', '', (string) $value);
        if ($digits === '') {
            return '-';
        }
        return str_pad($digits, 8, '0', STR_PAD_LEFT);
    };

    $formatMoney = function ($amount) {
        return number_format((int) $amount, 2, '.', ',');
    };

    $terbilang = function ($nilai) use (&$terbilang) {
        $nilai = (int) abs($nilai);
        $satuan = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
        if ($nilai < 12) return $satuan[$nilai];
        if ($nilai < 20) return $satuan[$nilai - 10] . ' Belas';
        if ($nilai < 100) return $satuan[intval($nilai / 10)] . ' Puluh' . ($nilai % 10 ? ' ' . $terbilang($nilai % 10) : '');
        if ($nilai < 200) return 'Seratus' . ($nilai % 100 ? ' ' . $terbilang($nilai % 100) : '');
        if ($nilai < 1000) return $satuan[intval($nilai / 100)] . ' Ratus' . ($nilai % 100 ? ' ' . $terbilang($nilai % 100) : '');
        if ($nilai < 2000) return 'Seribu' . ($nilai % 1000 ? ' ' . $terbilang($nilai % 1000) : '');
        if ($nilai < 1000000) return $terbilang(intval($nilai / 1000)) . ' Ribu' . ($nilai % 1000 ? ' ' . $terbilang($nilai % 1000) : '');
        if ($nilai < 1000000000) return $terbilang(intval($nilai / 1000000)) . ' Juta' . ($nilai % 1000000 ? ' ' . $terbilang($nilai % 1000000) : '');
        return (string) $nilai;
    };

    $total = $items->sum(function ($i) {
        $paid = (int) ($i['billpaid'] ?? $i['sudah_dibayar'] ?? 0);
        $nominal = (int) ($i['total_tagihan'] ?? 0);
        return $paid > 0 ? $paid : $nominal;
    });
    $tanggal = $items->pluck('PAIDDT')->filter()->first() ?? now();
    $nomor = $items->pluck('TRANSNO')->filter()->first()
        ?? $items->pluck('BILLCD')->filter()->first()
        ?? '-';

    $logoSchool = asset(config('app.logo', 'icon.png'));
    $logoEdunesia = asset(config('app.logo_great_edunesia', 'great-edunesia.png'));
    $logoDompet = asset(config('app.logo_dompet_dhuafa', 'logo-dompet-dhuafa.png'));
    $ttdFile = public_path(config('app.ttd_kuitansi', 'ttd.jpeg'));
    $ttdUrl = is_file($ttdFile) ? asset(config('app.ttd_kuitansi', 'ttd.jpeg')) : null;
    $namaTtd = config('app.nama_ttd_kuitansi', 'Cut Tia Andani, S.Ak.');
    $kelas = trim(($siswa['kelas'] ?? '') . ' ' . ($siswa['jurusan'] ?? '')) ?: '-';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bukti Pembayaran {{ $siswa['nama'] ?? '' }} | Al-Syukro</title>
<style>
*{box-sizing:border-box}
body{font-family:Verdana,Arial,Helvetica,sans-serif;font-size:13px;color:#111;margin:0;background:#f3f4f6}
.actions{max-width:800px;margin:16px auto 0;display:flex;gap:8px;justify-content:flex-end}
.actions button{padding:9px 16px;border-radius:8px;border:1px solid #14532d;background:#15803d;color:#fff;font-weight:600;cursor:pointer}
.actions button.ghost{background:#fff;color:#14532d}
.sheet{max-width:800px;margin:12px auto 24px;background:#fff;padding:28px 32px;box-shadow:0 4px 18px rgba(0,0,0,.08)}
.kop{width:100%;border-collapse:collapse;margin-bottom:6px}
.kop td{vertical-align:middle}
.kop .brand{text-align:center}
.kop h2{margin:0;font-size:18px;letter-spacing:.02em}
.kop .alamat{margin-top:4px;font-size:12px}
.kop img.school{max-height:72px;display:block;margin:auto}
.kop img.partner{max-height:38px;max-width:84px}
.partners{width:100%;border-collapse:collapse}
.partners td{text-align:center;padding:0 4px}
.double-hr{margin:8px 0 12px}
.double-hr .bold{height:3px;background:#000}
.double-hr .thin{height:1px;background:#666;margin-top:2px}
h3.title{text-align:center;margin:10px 0 14px;font-size:16px;letter-spacing:.04em;text-decoration:underline}
table.info,table.items,table.foot,table.totals{width:100%;border-collapse:collapse}
table.info td{padding:3px 4px;vertical-align:top;font-size:13px}
table.info td.lbl{width:22%}
table.info td.val{width:33%}
table.info td.lbl2{width:15%}
table.info td.val2{width:30%}
table.items{margin-top:8px}
table.items th,table.items td{padding:8px 6px;border-top:1px solid #000;border-bottom:1px solid #000;font-size:13px}
table.items th{text-align:left;font-weight:700;border-bottom:1px solid #999}
table.items td.r,table.items th.r{text-align:right}
table.foot{margin-top:14px}
table.foot td{vertical-align:top}
.terbilang{font-style:italic;margin-bottom:14px}
.ttd img{max-height:70px;margin:8px 0}
.ttd .nama{text-decoration:underline}
table.totals td{padding:3px 2px;font-size:13px}
table.totals td.r{text-align:right}
table.totals tr.grand td{border-top:1px solid #000;padding-top:6px;font-weight:700}
@media print{
  body{background:#fff}
  .sheet{box-shadow:none;margin:0;max-width:none;padding:0}
  .actions{display:none}
  @page{size:A4;margin:12mm}
}
</style>
</head>
<body>
<div class="actions">
  <button type="button" class="ghost" onclick="window.close()">Tutup</button>
  <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
</div>
<div class="sheet">
  <table class="kop">
    <tr>
      <td width="18%" align="center">
        <img class="school" src="{{ $logoSchool }}" alt="Al Syukro Universal">
      </td>
      <td width="54%" class="brand">
        <h2>{{ strtoupper(config('app.nama_instansi', 'AL SYUKRO UNIVERSAL')) }}</h2>
        <div class="alamat">{{ config('app.alamat') }}</div>
      </td>
      <td width="28%">
        <table class="partners">
          <tr>
            <td><img class="partner" src="{{ $logoEdunesia }}" alt="Great Edunesia"></td>
            <td><img class="partner" src="{{ $logoDompet }}" alt="Dompet Dhuafa"></td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
  <div class="double-hr">
    <div class="bold"></div>
    <div class="thin"></div>
  </div>

  <h3 class="title">BUKTI PEMBAYARAN</h3>

  <table class="info">
    <tr>
      <td class="lbl">Telah Diterima Dari</td>
      <td class="val">: <strong>{{ $siswa['nama'] ?? '-' }}</strong></td>
      <td class="lbl2">Nomor</td>
      <td class="val2">: <strong>{{ $nomor }}</strong></td>
    </tr>
    <tr>
      <td class="lbl">Kelas</td>
      <td class="val">: <strong>{{ $kelas }}</strong></td>
      <td class="lbl2">Tanggal</td>
      <td class="val2">: <strong>{{ \Carbon\Carbon::parse($tanggal)->format('d M Y') }}</strong></td>
    </tr>
    <tr>
      <td class="lbl">Unit</td>
      <td class="val">: <strong>{{ $siswa['jenjang'] ?? '-' }}</strong></td>
      <td class="lbl2"></td>
      <td class="val2"></td>
    </tr>
  </table>

  <table class="items">
    <thead>
      <tr>
        <th style="width:18%">Faktur No.</th>
        <th>Untuk Pembayaran</th>
        <th class="r" style="width:18%">Discount</th>
        <th class="r" style="width:22%">Nilai</th>
      </tr>
    </thead>
    <tbody>
      @foreach($items as $item)
        @php
          $paid = (int) ($item['billpaid'] ?? $item['sudah_dibayar'] ?? 0);
          $nominal = (int) ($item['total_tagihan'] ?? 0);
          $nilai = $paid > 0 ? $paid : $nominal;
        @endphp
        <tr>
          <td>{{ $padFaktur($item['AA'] ?? $item['BILLCD'] ?? '') }}</td>
          <td>{{ $deskripsiPembayaran($item) }}</td>
          <td class="r">{{ $formatMoney(0) }}</td>
          <td class="r">{{ $formatMoney($nilai) }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <table class="foot">
    <tr>
      <td width="55%">
        <div class="terbilang">{{ $total > 0 ? $terbilang($total) . ' Rupiah' : 'Nol Rupiah' }}</div>
        <div class="ttd">
          @if($ttdUrl)
            <img src="{{ $ttdUrl }}" alt="ttd">
          @else
            <br><br><br>
          @endif
          <div class="nama">{{ $namaTtd }}</div>
        </div>
      </td>
      <td width="45%">
        <table class="totals">
          <tr>
            <td>Sub Total</td>
            <td class="r">Rp {{ $formatMoney($total) }}</td>
          </tr>
          <tr>
            <td>Biaya Lainnya</td>
            <td class="r">{{ $formatMoney(0) }}</td>
          </tr>
          <tr class="grand">
            <td>Total</td>
            <td class="r">{{ $formatMoney($total) }}</td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</div>
<script>
if (new URLSearchParams(location.search).get('print') === '1') {
  window.addEventListener('load', () => setTimeout(() => window.print(), 300));
}
</script>
</body>
</html>
