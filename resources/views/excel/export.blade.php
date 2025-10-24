
<table class="table table-striped table-range text-center">
    <thead class="bg-primary text-white">
        <tr>
        <th rowspan="2">#</th>
        <th rowspan="2">Tahun</th>
        <th colspan="3">Tingkat</th>
        <th rowspan="2">Total</th>
        </tr>
        <tr>
        <th>Regional</th>
        <th>Nasional</th>
        <th>Internasional</th>
        </tr>
    </thead>
    <tbody>
        @foreach($year_array as $year)
        <tr>
        <td>{{$loop->iteration}}</td>
        <td>{{$year}}</td>
        <td>{{$region_array[$loop->iteration-1]}}</td>
        <td>{{$national_array[$loop->iteration-1]}}</td>
        <td>{{$international_array[$loop->iteration-1]}}</td>
        <td>{{$region_array[$loop->iteration-1] +
                $national_array[$loop->iteration-1] +
                $international_array[$loop->iteration-1]}}</td>
        </tr>
        @endforeach
    </tbody>
    </table>