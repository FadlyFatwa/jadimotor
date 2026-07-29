@extends('layouts.main')

@section('title', 'Needlist Menunggu Persetujuan')

@section('content')
<div class="container">
    <h4>📥 Needlist Menunggu Persetujuan</h4>

    <table class="table table-bordered table-sm">
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Waktu Diajukan</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($needlists as $needlist)
                <tr>
                    <td>#{{ $needlist->id }}</td>
                    <td>{{ $needlist->user->name ?? '-' }}</td>
                    <td>{{ $needlist->updated_at }}</td>
                    <td><span class="badge badge-warning">{{ ucfirst($needlist->status) }}</span></td>
                    <td>
                        <a href="{{ route('needlist.review', $needlist->id) }}" class="btn btn-sm btn-info">Review</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
