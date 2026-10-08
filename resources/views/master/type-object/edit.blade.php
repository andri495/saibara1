@extends('layouts.app')

@section('title', 'Edit Tipe Objek')

@section('content')
<div class="page-container">
    <div class="page-header">
        <div class="page-title-wrapper">
            <h1 class="page-title">Edit Tipe Objek</h1>
            <p class="page-subtitle">Edit data tipe objek</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('master.type-object.update', $data->id) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="form-group">
                    <label for="type" class="form-label">Nama Tipe <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('type') is-invalid @enderror" 
                           id="type" name="type" value="{{ old('type', $data->type) }}" required>
                    @error('type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Update</button>
                    <a href="{{ route('master.type-object.index') }}" class="btn btn-secondary">← Kembali</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
