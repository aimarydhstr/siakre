
<table >
    <thead >
        <tr>
        <th rowspan="2">#</th>
        <th rowspan="2">Jenis Publikasi</th>
        <th colspan="3">Tahun</th>
        <th rowspan="2">Total</th>
        </tr>
        <tr>
        <th>TS-2</th>
        <th>TS-1</th>
        <th>TS</th>
        </tr>
    </thead>
    <tbody>
    @foreach($data_type_array as $type)
        <tr>
        <td>{{$loop->iteration}}</td>
        <td>{{$type}}</td>
        <td>{{$TS_2_array[$loop->iteration-1]}} </td>
        <td>{{$TS_1_array[$loop->iteration-1]}}</td>
        <td>{{$TS_array[$loop->iteration-1]}}</td>
        <td>{{$TS_2_array[$loop->iteration-1] + $TS_1_array[$loop->iteration-1] + $TS_array[$loop->iteration-1]}} </td>
        </tr>
        @endforeach
    </tbody>
</table>
    