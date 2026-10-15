@php
  $nis = $siswa['no_cust'] ?? '-';
  $total = $items->sum(fn ($i) => (int) ($i['total_tagihan'] ?? 0));
  $paidDates = $items->pluck('PAIDDT')->filter()->map(fn ($d) => \Carbon\Carbon::parse($d));
  $tglBayar = $paidDates->isNotEmpty() ? $paidDates->max()->translatedFormat('d F Y') : '-';
  $noKuitansi = 'KW-'.$nis.'-'.now()->format('YmdHis');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kuitansi {{ $siswa['nama'] ?? '' }} | Al-Syukro</title>
<style>
*{box-sizing:border-box}
body{font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#111;margin:0;background:#f3f4f6}
.sheet{max-width:800px;margin:20px auto;background:#fff;padding:28px 32px;box-shadow:0 4px 18px rgba(0,0,0,.08)}
.kop{display:flex;align-items:center;gap:16px;border-bottom:3px double #14532d;padding-bottom:12px;margin-bottom:16px}
.kop img{height:64px;width:auto}
.kop h2{margin:0;font-size:18px;color:#14532d}
.kop p{margin:2px 0 0;font-size:12px;color:#444}
h3{text-align:center;margin:0 0 4px;font-size:16px;letter-spacing:.06em}
.no{text-align:center;font-size:12px;color:#555;margin-bottom:16px}
table{width:100%;border-collapse:collapse}
.info td{padding:4px 6px;vertical-align:top}
.info td.l{width:16%;color:#444}
.items{margin-top:14px}
.items th,.items td{border:1px solid #999;padding:7px 8px}
.items th{background:#ededed;text-align:left}
.items td.r,.items th.r{text-align:right}
.items tfoot td{font-weight:700}
.ttd{display:flex;justify-content:flex-end;margin-top:36px}
.ttd div{text-align:center;width:220px}
.ttd .sp{height:64px}
.note{margin-top:18px;font-size:11px;color:#666}
.actions{max-width:800px;margin:16px auto 0;display:flex;gap:8px;justify-content:flex-end}
.actions button{padding:9px 16px;border-radius:8px;border:1px solid #14532d;background:#15803d;color:#fff;font-weight:600;cursor:pointer}
.actions button.ghost{background:#fff;color:#14532d}
@media print{
  body{background:#fff}
  .sheet{box-shadow:none;margin:0;max-width:none;padding:0}
  .actions{display:none}
  @page{size:A4;margin:14mm}
}
</style>
</head>
<body>
<div class="actions">
  <button type="button" class="ghost" onclick="window.close()">Tutup</button>
  <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
</div>
<div class="sheet">
  <div class="kop">
    <img src="{{ asset('icon.png') }}" alt="Al-Syukro">
    <div>
      <h2>Al-Syukro Universal</h2>
      <p>Bukti pembayaran tagihan siswa</p>
    </div>
  </div>

  <h3>KUITANSI PEMBAYARAN</h3>
  <div class="no">No. {{ $noKuitansi }}</div>

  <table class="info">
    <tr>
      <td class="l">NIS</td><td>: <b>{{ $nis }}</b></td>
      <td class="l">Unit</td><td>: <b>{{ $siswa['jenjang'] ?? '-' }}</b></td>
    </tr>
    <tr>
      <td class="l">No. VA</td><td>: <b>{{ $siswa['va_number'] ?? '-' }}</b></td>
      <td class="l">Kelas</td><td>: <b>{{ trim(($siswa['kelas'] ?? '').' '.($siswa['jurusan'] ?? '')) ?: '-' }}</b></td>
    </tr>
    <tr>
      <td class="l">Nama Siswa</td><td>: <b>{{ $siswa['nama'] ?? '-' }}</b></td>
      <td class="l">Orang Tua</td><td>: <b>{{ $siswa['orangtua'] ?? '-' }}</b></td>
    </tr>
  </table>

  <table class="items">
    <thead>
      <tr>
        <th style="width:40px">#</th>
        <th>Nama Tagihan</th>
        <th>Periode</th>
        <th>Tgl Bayar</th>
        <th class="r">Nominal</th>
      </tr>
    </thead>
    <tbody>
      @foreach($items as $item)
      <tr>
        <td>{{ $loop->iteration }}</td>
        <td>{{ ucwords(str_replace('_', ' ', strtolower($item['nama_tagihan'] ?? '-'))) }}</td>
        <td>{{ ($item['periode'] ?? '') ?: '-' }}</td>
        <td>{{ !empty($item['PAIDDT']) ? \Carbon\Carbon::parse($item['PAIDDT'])->format('d-m-Y') : '-' }}</td>
        <td class="r">Rp {{ number_format((int) ($item['total_tagihan'] ?? 0), 0, ',', '.') }}</td>
      </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr>
        <td colspan="4" class="r">Total</td>
        <td class="r">Rp {{ number_format($total, 0, ',', '.') }}</td>
      </tr>
    </tfoot>
  </table>

  <div class="ttd">
    <div>
      <div>{{ $tglBayar }}</div>
      <div>Bagian Keuangan</div>
      <div class="sp"></div>
      <div>(______________________)</div>
    </div>
  </div>

  <p class="note">Kuitansi ini dicetak dari web tagihan Al-Syukro pada {{ now()->format('d-m-Y H:i') }} dan merupakan bukti pembayaran yang sah.</p>
</div>
<script>
if (new URLSearchParams(location.search).get('print') === '1') {
  window.addEventListener('load', () => setTimeout(() => window.print(), 300));
}
</script>
</body>
</html>
