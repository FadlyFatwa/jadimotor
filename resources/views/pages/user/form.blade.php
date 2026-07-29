@extends('layouts.main')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0">
                        Form {{ isset($user) ? 'Edit' : 'Tambah' }} User
                    </h3>
                </div>
                <div class="card-body p-4">
                    <form action="{{ isset($user) ? route('users.update', $user->id) : route('users.store') }}" method="POST">
                        @csrf
                        @if(isset($user))
                            @method('PUT')
                        @endif

                        <div class="form-group">
                            <label>Nama <span class="text-danger">*</span></label>
                            <input type="text" name="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $user->name ?? '') }}">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label>Username <span class="text-danger">*</span></label>
                            <input type="text" name="username"
                                class="form-control @error('username') is-invalid @enderror"
                                value="{{ old('username', $user->username ?? '') }}">
                            @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label>Email <span class="text-danger">*</span></label>
                            <input type="email" name="email"
                                class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email', $user->email ?? '') }}">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label>Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-control @error('role') is-invalid @enderror">
                                <option value="">-- Pilih Role --</option>
                                @foreach($roles as $r)
                                    <option value="{{ $r }}" {{ old('role', $user->role ?? '') == $r ? 'selected' : '' }}>
                                        {{ ucfirst($r) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label>Password {{ isset($user) ? '' : '<span class="text-danger">*</span>' }}</label>
                            <input type="password" name="password"
                                class="form-control @error('password') is-invalid @enderror"
                                placeholder="{{ isset($user) ? 'Kosongkan jika tidak ingin mengubah password' : 'Minimal 6 karakter' }}">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1"
                                    class="custom-control-input" id="is_active"
                                    {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }}
                                    {{ isset($user) && $user->id === auth()->id() ? 'onclick=return false;' : '' }}>
                                <label class="custom-control-label" for="is_active">User Aktif</label>
                            </div>
                            @if(isset($user) && $user->id === auth()->id())
                                <small class="text-muted">Anda tidak dapat menonaktifkan akun Anda sendiri.</small>
                            @endif
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> {{ isset($user) ? 'Simpan Perubahan' : 'Simpan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
