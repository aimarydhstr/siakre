<table >
   <thead>
   <tr>
      <th scope="col">#</th>
      <th scope="col" class="text-left">Nama Mahasiswa</th>
      <th scope="col">NIM</th>
      <th scope="col" class="text-left">Kompetisi</th>
      <th scope="col" class="text-left">Pencapaian</th>
      <th scope="col">Tahun</th>
   </tr>
   </thead>
   <tbody>
   @foreach($data as $data_all)
   <tr>
      <th scope="row">{{  $loop->iteration }}</th>
      <td class="text-left">{{$data_all->name}}</td>
      <td>{{$data_all->nim}}</td>
      <td class="text-left">{{$data_all->competition}}</td>
      <td class="text-left">{{$data_all->achievement}}</td>
      <td>{{$data_all->year}}</td>
   </tr>
   @endforeach
   </tbody>
</table>