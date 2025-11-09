<!-- Sidebar -->
<div class="bg-light shadow sticky" id="sidebar-wrapper">
  <div class="sidebar-heading">
    <img src="https://uhb.ac.id/wp-content/uploads/2024/03/logo_UHB_r-1.png" width="140" height="90" class="center-image">
    @if(Auth::user()->role == 'lecturer')
      <p class="text-center mt-1 title-prodi">{{ Auth::user()->lecturer->department->name }}</p>
    @elseif(Auth::user()->role == 'department_head')
      <p class="text-center mt-1 title-prodi">{{ Auth::user()->department_head->department->name }}</p>
    @endif
  </div>

  <div class="list-group list-group-flush">
    <a href="{{ route('home') }}"
       class="list-group-item list-group-item-action {{ request()->routeIs('home') ? 'active' : '' }}">
      <i class="fas fa-compass"></i> Beranda
    </a>

    @if(Auth::user()->role == 'admin')
      <a href="{{ route('expertises.index') }}"
         class="list-group-item list-group-item-action {{ request()->routeIs('expertises.*') ? 'active' : '' }}">
        <i class="fas fa-folder"></i> Bidang Keahlian
      </a>

      <a href="{{ route('faculties.index') }}"
         class="list-group-item list-group-item-action {{ request()->routeIs('faculties.*') ? 'active' : '' }}">
        <i class="fas fa-folder"></i> Fakultas
      </a>

      <a href="{{ route('departments.index') }}"
         class="list-group-item list-group-item-action {{ request()->routeIs('departments.*') ? 'active' : '' }}">
        <i class="fas fa-folder"></i> Program Studi
      </a>

      <a href="{{ route('users.index') }}"
         class="list-group-item list-group-item-action {{ request()->routeIs('users.*') ? 'active' : '' }}">
        <i class="fas fa-user"></i> Pengguna
      </a>

      <a href="{{ route('cooperations.index') }}"
         class="list-group-item list-group-item-action {{ request()->routeIs('cooperations.*') || request()->routeIs('ias.*') ? 'active' : '' }}">
        <i class="fas fa-folder"></i> Kerja Sama
      </a>

  @endif

  
    <a href="{{ route('lecturers.index') }}"
      class="list-group-item list-group-item-action {{ request()->routeIs('lecturers.*') ? 'active' : '' }}">
      <i class="fas fa-user"></i> Dosen
    </a>

    <a href="{{ route('output.index') }}"
        class="list-group-item list-group-item-action {{ (request()->routeIs('output') || request()->routeIs('output.*')) ? 'active' : '' }}">
      <i class="fas fa-folder-open"></i> Luaran Dosen
    </a>

    <a href="#pageSubmenu1"
       data-toggle="collapse"
       aria-expanded="{{ (request()->routeIs('akademik') || request()->routeIs('nonAkademik')) ? 'true' : 'false' }}"
       class="dropdown-toggle list-group-item list-group-item-action {{ (request()->routeIs('akademik') || request()->routeIs('nonAkademik')) ? 'active' : '' }}">
      <i class="fas fa-trophy"></i> Prestasi
    </a>
    <div class="collapse {{ (request()->routeIs('akademik') || request()->routeIs('nonAkademik')) ? 'show' : '' }}" id="pageSubmenu1">
      <a class="dropdown-item sidebar-dropdown {{ request()->routeIs('akademik') ? 'active' : '' }}"
         href="{{ route('akademik') }}">Akademik</a>
      <a class="dropdown-item sidebar-dropdown {{ request()->routeIs('nonAkademik') ? 'active' : '' }}"
         href="{{ route('nonAkademik') }}">Non-Akademik</a>
    </div>

    <a href="{{ route('hki.index') }}"
       class="list-group-item list-group-item-action {{ (request()->routeIs('hki') || request()->routeIs('hki.*')) ? 'active' : '' }}">
      <i class="fas fa-book"></i> HKI
    </a>

    <a href="{{ route('books.index') }}"
       class="list-group-item list-group-item-action {{ (request()->routeIs('books') || request()->routeIs('books.*')) ? 'active' : '' }}">
      <i class="fas fa-book"></i> Buku
    </a>

    <a href="{{ route('article') }}"
       class="list-group-item list-group-item-action {{ (request()->routeIs('article') || request()->routeIs('article.*')) ? 'active' : '' }}">
      <i class="fas fa-book-open"></i> Artikel
    </a>

    <button type="button" class="list-group-item list-group-item-action" data-toggle="modal" data-target="#SelectAdd">
      <i class="fas fa-plus-circle"></i> Tambah Data
    </button>

    <a href="{{ route('user-edit') }}"
       class="list-group-item list-group-item-action {{ request()->routeIs('user-edit') ? 'active' : '' }}">
      <i class="fas fa-users-cog"></i> Sunting Akun
    </a>
  </div>
</div>

<!-- Modal SelectAdd (unchanged) -->
<div class="modal fade" id="SelectAdd" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-body p-5">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="position:absolute;top:1rem;right:1rem;">
          <span aria-hidden="true">&times;</span>
        </button>
        <div class="row">
          <div class="col-12 my-3">
            <a class="btn rounded-pill d-flex align-items-center justify-content-center py-3 px-4 btn-outline-info"
               href="{{ route('add') }}">
              <h5 class="mb-0"><i class="fas fa-trophy"></i> Tambah Prestasi</h5>
            </a>
          </div>
          <div class="col-12 my-3">
            <a class="btn rounded-pill d-flex align-items-center justify-content-center py-3 px-4 btn-outline-success"
               href="{{ route('article-add') }}">
              <h5 class="mb-0"><i class="fas fa-book-open"></i> Tambah Artikel</h5>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
