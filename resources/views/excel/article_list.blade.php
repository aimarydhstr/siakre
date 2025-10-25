<table style="border-collapse:collapse; table-layout:fixed; width:1600px; font-family:Arial, Helvetica, sans-serif; font-size:12px;">
  <colgroup>
    <col style="width:40px;">    {{-- # --}}
    <col style="width:140px;">   {{-- Jenis --}}
    <col style="width:320px;">   {{-- Judul --}}
    <col style="width:240px;">   {{-- URL --}}
    <col style="width:160px;">   {{-- DOI --}}
    <col style="width:200px;">   {{-- Dosen --}}
    <col style="width:220px;">   {{-- Mahasiswa --}}
    <col style="width:160px;">   {{-- NIM --}}
    <col style="width:180px;">   {{-- Penerbit --}}
    <col style="width:70px;">    {{-- Volume --}}
    <col style="width:70px;">    {{-- Nomor --}}
  </colgroup>

  <thead>
    <tr>
      <th style="border:1px solid #000; padding:6px; text-align:center; background:#f2f2f2; font-weight:bold;">#</th>
      <th style="border:1px solid #000; padding:6px; text-align:left;   background:#f2f2f2; font-weight:bold;">Jenis</th>
      <th style="border:1px solid #000; padding:6px; text-align:left;   background:#f2f2f2; font-weight:bold;">Judul</th>
      <th style="border:1px solid #000; padding:6px; text-align:left;   background:#f2f2f2; font-weight:bold;">URL</th>
      <th style="border:1px solid #000; padding:6px; text-align:left;   background:#f2f2f2; font-weight:bold;">DOI</th>
      <th style="border:1px solid #000; padding:6px; text-align:left;   background:#f2f2f2; font-weight:bold;">Dosen</th>
      <th style="border:1px solid #000; padding:6px; text-align:left;   background:#f2f2f2; font-weight:bold;">Mahasiswa</th>
      <th style="border:1px solid #000; padding:6px; text-align:left;   background:#f2f2f2; font-weight:bold;">NIM</th>
      <th style="border:1px solid #000; padding:6px; text-align:left;   background:#f2f2f2; font-weight:bold;">Penerbit</th>
      <th style="border:1px solid #000; padding:6px; text-align:center; background:#f2f2f2; font-weight:bold;">Volume</th>
      <th style="border:1px solid #000; padding:6px; text-align:center; background:#f2f2f2; font-weight:bold;">Nomor</th>
    </tr>
  </thead>

  <tbody>
    @forelse($data_article as $data)
      @php
        // --- kumpulkan nama dosen ---
        $lecturerNames = [];
        if (isset($data->lecturers)) {
          foreach ($data->lecturers as $lec) {
            $lecturerNames[] = optional($lec->user)->name ?? '—';
          }
        } elseif (!empty($data['count_dosen'])) {
          for ($i = 1; $i <= (int)($data['count_dosen'] ?? 0); $i++) {
            $k = 'dosen'.$i;
            if (!empty($data[$k])) $lecturerNames[] = $data[$k];
          }
        }

        // --- kumpulkan mahasiswa & nim ---
        $studentNames = []; $studentNims = [];
        if (isset($data->students)) {
          foreach ($data->students as $stu) {
            $studentNames[] = $stu->name ?? '—';
            $studentNims[]  = $stu->nim  ?? '—';
          }
        } elseif (!empty($data['count_mahasiswa'])) {
          for ($j = 1; $j <= (int)($data['count_mahasiswa'] ?? 0); $j++) {
            $nKey = 'name'.$j; $mKey = 'nim'.$j;
            if (!empty($data[$nKey])) $studentNames[] = $data[$nKey];
            if (!empty($data[$mKey])) $studentNims[]  = $data[$mKey];
          }
        }

        // safe values
        $type      = $data->type_journal ?? ($data['type_journal'] ?? '—');
        $title     = $data->title        ?? ($data['title'] ?? '—');
        $url       = $data->url          ?? ($data['url'] ?? '');
        $doi       = $data->doi          ?? ($data['doi'] ?? '');
        $publisher = $data->publisher    ?? ($data['publisher'] ?? '—');
        $volume    = $data->volume       ?? ($data['volume'] ?? '—');
        $number    = $data->number       ?? ($data['number'] ?? '—');

        $lecturerText = implode('; ', array_filter($lecturerNames)) ?: '—';
        $studentText  = implode('; ', array_filter($studentNames))  ?: '—';
        $nimText      = implode('; ', array_filter($studentNims))   ?: '—';
      @endphp

      <tr>
        <td style="border:1px solid #000; padding:6px; text-align:center; white-space:nowrap;">{{ $loop->iteration }}</td>
        <td style="border:1px solid #000; padding:6px; text-align:left;   white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $type }}</td>
        <td style="border:1px solid #000; padding:6px; text-align:left;   overflow:hidden; text-overflow:ellipsis;">{{ $title }}</td>
        <td style="border:1px solid #000; padding:6px; text-align:left;   word-break:break-all;">{{ $url ?: '—' }}</td>
        <td style="border:1px solid #000; padding:6px; text-align:left;   word-break:break-all;">{{ $doi ?: '—' }}</td>
        <td style="border:1px solid #000; padding:6px; text-align:left;   overflow:hidden; text-overflow:ellipsis;">{{ $lecturerText }}</td>
        <td style="border:1px solid #000; padding:6px; text-align:left;   overflow:hidden; text-overflow:ellipsis;">{{ $studentText }}</td>
        <td style="border:1px solid #000; padding:6px; text-align:left;   white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $nimText }}</td>
        <td style="border:1px solid #000; padding:6px; text-align:left;   overflow:hidden; text-overflow:ellipsis;">{{ $publisher }}</td>
        <td style="border:1px solid #000; padding:6px; text-align:center; white-space:nowrap;">{{ $volume }}</td>
        <td style="border:1px solid #000; padding:6px; text-align:center; white-space:nowrap;">{{ $number }}</td>
      </tr>
    @empty
      <tr>
        <td colspan="11" style="border:1px solid #000; padding:8px; text-align:center; color:#555;">Tidak ada data.</td>
      </tr>
    @endforelse
  </tbody>
</table>
