
<table >
    <thead >
        <tr>
        <th >#</th>
        <th >Jenis</th>
        <th >Judul</th>
        <th >Url</th>
        <th >Dosen</th>
        <th >Mahasiswa</th>
        <th >NIM</th>
        <th >pengeluar</th>
        <th >Volume</th>
        <th >Nomer</th>
        </tr>
    </thead>
    <tbody>
    @foreach($data_article as $data)
        <tr>
        <td>{{$loop->iteration}}</td>
        <td>{{$data->type_journal}}</td>
        <td>{{$data->title}} </td>
        <td>{{$data->url}} </td>
        <td>
         @for($i = 1; $i <= $data['count_dosen']; $i++)
            {{$data['dosen'.$i]}},
         @endfor
        </td>
        <td>
         @for($j = 1; $j <= $data['count_mahasiswa']; $j++)
            {{$data['name'.$j]}},
         @endfor
        </td>
        <td>
         @for($k = 1; $k <= $data['count_mahasiswa']; $k++)
            {{$data['nim'.$k]}},
         @endfor
        </td>
        <td>{{$data->publisher}} </td>
        <td>{{$data->volume}} </td>
        <td>{{$data->number}} </td>

        </tr>
      @endforeach
    </tbody>
</table>
    