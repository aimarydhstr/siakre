<table style="border-collapse:collapse; table-layout:fixed; width:780px; font-family:Arial, Helvetica, sans-serif; font-size:12px;">
  {{-- Lebar kolom konsisten --}}
  <colgroup>
    <col style="width:50px;">   {{-- # --}}
    <col style="width:300px;">  {{-- Jenis Publikasi --}}
    <col style="width:90px;">   {{-- TS-2 --}}
    <col style="width:90px;">   {{-- TS-1 --}}
    <col style="width:90px;">   {{-- TS --}}
    <col style="width:100px;">  {{-- Total --}}
  </colgroup>

  <thead>
    <tr>
      <th style="border:1px solid #000; padding:6px; text-align:center; background:#f2f2f2; font-weight:bold;">#</th>
      <th style="border:1px solid #000; padding:6px; text-align:left;   background:#f2f2f2; font-weight:bold;">Jenis Publikasi</th>
      <th style="border:1px solid #000; padding:6px; text-align:center; background:#f2f2f2; font-weight:bold;">TS-2</th>
      <th style="border:1px solid #000; padding:6px; text-align:center; background:#f2f2f2; font-weight:bold;">TS-1</th>
      <th style="border:1px solid #000; padding:6px; text-align:center; background:#f2f2f2; font-weight:bold;">TS</th>
      <th style="border:1px solid #000; padding:6px; text-align:center; background:#f2f2f2; font-weight:bold;">Total</th>
    </tr>
  </thead>

  <tbody>
    @php
      $sum_ts2 = 0; $sum_ts1 = 0; $sum_ts = 0; $sum_all = 0;
    @endphp

    @forelse($data_type_array as $idx => $type)
      @php
        $ts2 = (int)($TS_2_array[$idx] ?? 0);
        $ts1 = (int)($TS_1_array[$idx] ?? 0);
        $ts  = (int)($TS_array[$idx]    ?? 0);
        $rowTotal = $ts2 + $ts1 + $ts;

        $sum_ts2 += $ts2; $sum_ts1 += $ts1; $sum_ts += $ts; $sum_all += $rowTotal;
      @endphp
      <tr>
        <td style="border:1px solid #000; padding:6px; text-align:center; white-space:nowrap;">{{ $loop->iteration }}</td>
        <td style="border:1px solid #000; padding:6px; text-align:left;   overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $type }}</td>
        <td style="border:1px solid #000; padding:6px; text-align:center; white-space:nowrap;">{{ $ts2 }}</td>
        <td style="border:1px solid #000; padding:6px; text-align:center; white-space:nowrap;">{{ $ts1 }}</td>
        <td style="border:1px solid #000; padding:6px; text-align:center; white-space:nowrap;">{{ $ts }}</td>
        <td style="border:1px solid #000; padding:6px; text-align:center; white-space:nowrap; font-weight:bold;">{{ $rowTotal }}</td>
      </tr>
    @empty
      <tr>
        <td colspan="6" style="border:1px solid #000; padding:8px; text-align:center; color:#555;">Tidak ada data.</td>
      </tr>
    @endforelse
  </tbody>

  @if(!empty($data_type_array))
    <tfoot>
      <tr>
        <td colspan="2" style="border:1px solid #000; padding:6px; text-align:right; font-weight:bold;">Total</td>
        <td style="border:1px solid #000; padding:6px; text-align:center; font-weight:bold;">{{ $sum_ts2 }}</td>
        <td style="border:1px solid #000; padding:6px; text-align:center; font-weight:bold;">{{ $sum_ts1 }}</td>
        <td style="border:1px solid #000; padding:6px; text-align:center; font-weight:bold;">{{ $sum_ts }}</td>
        <td style="border:1px solid #000; padding:6px; text-align:center; font-weight:bold;">{{ $sum_all }}</td>
      </tr>
    </tfoot>
  @endif
</table>
