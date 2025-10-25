<table style="border-collapse:collapse; table-layout:fixed; width:1130px; font-family:Arial, Helvetica, sans-serif; font-size:12px;">
  {{-- Lebar kolom dirancang agar nyaman untuk panjang kata riil + bisa wrap --}}
  <colgroup>
    <col style="width:60px;">    {{-- # --}}
    <col style="width:340px;">   {{-- Nama Mahasiswa --}}
    <col style="width:130px;">   {{-- NIM --}}
    <col style="width:400px;">   {{-- Kompetisi --}}
    <col style="width:180px;">   {{-- Pencapaian (Rank) --}}
    <col style="width:60px;">    {{-- Tahun --}}
  </colgroup>

  <thead>
    <tr>
      <th style="border:1px solid #000; padding:6px; text-align:center; background:#f2f2f2; font-weight:bold; vertical-align:middle;">#</th>
      <th style="border:1px solid #000; padding:6px; text-align:left;   background:#f2f2f2; font-weight:bold; vertical-align:middle;">Nama Mahasiswa</th>
      <th style="border:1px solid #000; padding:6px; text-align:center; background:#f2f2f2; font-weight:bold; vertical-align:middle;">NIM</th>
      <th style="border:1px solid #000; padding:6px; text-align:left;   background:#f2f2f2; font-weight:bold; vertical-align:middle;">Kompetisi</th>
      <th style="border:1px solid #000; padding:6px; text-align:left;   background:#f2f2f2; font-weight:bold; vertical-align:middle;">Pencapaian</th>
      <th style="border:1px solid #000; padding:6px; text-align:center; background:#f2f2f2; font-weight:bold; vertical-align:middle;">Tahun</th>
    </tr>
  </thead>

  <tbody>
    @forelse($data as $row)
      @php
        // Safe getters
        $name        = $row->student_name
                        ?? $row->name
                        ?? optional($row->student)->name
                        ?? '—';

        $nim         = $row->student_nim
                        ?? $row->nim
                        ?? optional($row->student)->nim
                        ?? '—';

        $competition = $row->competition ?? '—';

        // PAKAI RANK SAJA (bukan achievement)
        $rank        = $row->rank ?? '—';

        $year        = $row->year ?? '—';
      @endphp
      <tr>
        <td style="border:1px solid #000; padding:6px; text-align:center; vertical-align:top; white-space:nowrap;">{{ $loop->iteration }}</td>

        {{-- Biarkan wrap agar panjang nama/kompetisi tidak memecah layout --}}
        <td style="border:1px solid #000; padding:6px; text-align:left;   vertical-align:top; white-space:normal; word-break:break-word;">{{ $name }}</td>
        <td style="border:1px solid #000; padding:6px; text-align:center; vertical-align:top; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $nim }}</td>
        <td style="border:1px solid #000; padding:6px; text-align:left;   vertical-align:top; white-space:normal; word-break:break-word;">{{ $competition }}</td>
        <td style="border:1px solid #000; padding:6px; text-align:left;   vertical-align:top; white-space:normal; word-break:break-word;">{{ $rank }}</td>
        <td style="border:1px solid #000; padding:6px; text-align:center; vertical-align:top; white-space:nowrap;">{{ $year }}</td>
      </tr>
    @empty
      <tr>
        <td colspan="6" style="border:1px solid #000; padding:8px; text-align:center; color:#555;">Tidak ada data.</td>
      </tr>
    @endforelse
  </tbody>
</table>
