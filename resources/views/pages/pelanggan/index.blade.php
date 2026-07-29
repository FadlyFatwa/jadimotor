@extends('layouts.main')
@section('title', 'Data Pelanggan')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar Pelanggan</h3>
        <a href="{{ route('pelanggan.create') }}" class="btn btn-sm btn-primary float-right">
            <i class="fas fa-plus"></i> Tambah Pelanggan
        </a>
    </div>
    <div class="card-body">

        <table class="table table-bordered table-sm">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Telepon</th>
                    <th>Alamat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pelanggans as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->nama }}</td>
                    <td>{{ $item->email ?? '-' }}</td>
                    <td>{{ $item->telepon ?? '-' }}</td>
                    <td>{{ $item->alamat ?? '-' }}</td>
                    <td>
                        <a href="{{ route('pelanggan.edit', $item->id) }}" class="btn btn-sm btn-warning">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('pelanggan.destroy', $item->id) }}" method="POST" class="d-inline"
                            data-confirm="Yakin ingin menghapus pelanggan ini?">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-2">
            {{ $pelanggans->links() }}
        </div>
    </div>
</div>
@endsection
