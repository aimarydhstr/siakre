<table style="border-collapse:collapse; table-layout:fixed; width:700px; font-family:Arial, Helvetica, sans-serif; font-size:12px;">
  <colgroup>
    <col style="width:50px;">   {{-- # --}}
    <col style="width:120px;">  {{-- Tahun --}}
    <col style="width:120px;">  {{-- Regional --}}
    <col style="width:120px;">  {{-- Nasional --}}
    <col style="width:120px;">  {{-- Internasional --}}
    <col style="width:170px;">  {{-- Total --}}
  </colgroup>

  <thead>
    <tr>
      <th style="border:1px solid #000; padding:6px; text-align:center; background:#f2f2f2; font-weight:bold;">#</th>
      <th style="border:1px solid #000; padding:6px; text-align:center; background:#f2f2f2; font-weight:bold;">Tahun</th>
      <th style="border:1px solid #000; padding:6px; text-align:center; background:#f2f2f2; font-weight:bold;">Regional</th>
      <th style="border:1px solid #000; padding:6px; text-align:center; background:#f2f2f2; font-weight:bold;">Nasional</th>
      <th style="border:1px solid #000; padding:6px; text-align:center; background:#f2f2f2; font-weight:bold;">Internasional</th>
      <th style="border:1px solid #000; padding:6px; text-align:center; background:#f2f2f2; font-weight:bold;">Total</th>
    </tr>
  </thead>

  <tbody>
    @php
      $sum_reg = 0; $sum_nat = 0; $sum_int = 0; $sum_all = 0;
    @endphp

    @forelse($year_array as $idx => $year)
      @php
        $reg = (int)($region_array[$idx] ?? 0);
        $nat = (int)($national_array[$idx] ?? 0);
        $int = (int)($international_array[$idx] ?? 0);
        $rowTotal = $reg + $nat + $int;

        $sum_reg += $reg;
        $sum_nat += $nat;
        $sum_int += $int;
        $sum_all += $rowTotal;
      @endphp
      <tr>
        <td style="border:1px solid #000; padding:6px; text-align:center; white-space:nowrap;">{{ $loop->iteration }}</td>
        <td style="border:1px solid #000; padding:6px; text-align:center; white-space:nowrap;">{{ $year }}</td>
        <td style="border:1px solid #000; padding:6px; text-align:center;">{{ $reg }}</td>
        <td style="border:1px solid #000; padding:6px; text-align:center;">{{ $nat }}</td>
        <td style="border:1px solid #000; padding:6px; text-align:center;">{{ $int }}</td>
        <td style="border:1px solid #000; padding:6px; text-align:center; font-weight:bold;">{{ $rowTotal }}</td>
      </tr>
    @empty
      <tr>
        <td colspan="6" style="border:1px solid #000; padding:8px; text-align:center; color:#555;">Tidak ada data.</td>
      </tr>
    @endforelse
  </tbody>

  @if(!empty($year_array))
    <tfoot>
      <tr>
        <td colspan="2" style="border:1px solid #000; padding:6px; text-align:right; font-weight:bold;">Total</td>
        <td style="border:1px solid #000; padding:6px; text-align:center; font-weight:bold;">{{ $sum_reg }}</td>
        <td style="border:1px solid #000; padding:6px; text-align:center; font-weight:bold;">{{ $sum_nat }}</td>
        <td style="border:1px solid #000; padding:6px; text-align:center; font-weight:bold;">{{ $sum_int }}</td>
        <td style="border:1px solid #000; padding:6px; text-align:center; font-weight:bold;">{{ $sum_all }}</td>
      </tr>
    </tfoot>
  @endif
</table>
