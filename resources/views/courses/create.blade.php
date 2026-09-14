<x-layout title="Tambah Mata Kuliah">

    <style>
        .form-page {
            max-width: 700px;
            margin: 0 auto;
            padding: 40px 24px;
        }

        .form-header {
            margin-bottom: 28px;
        }

        .form-header h1 {
            margin: 0;
            font-size: 28px;
            color: #1f2937;
        }

        .form-header p {
            margin: 6px 0 0;
            color: #6b7280;
        }

        .form-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 28px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
            color: #374151;
        }

        .form-control {
            width: 100%;
            box-sizing: border-box;
            padding: 11px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
        }

        .form-control:focus {
            outline: none;
            border-color: #2563eb;
        }

        textarea.form-control {
            min-height: 110px;
            resize: vertical;
        }

        .error {
            margin-top: 5px;
            color: #dc2626;
            font-size: 13px;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 28px;
        }

        .btn {
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }
    </style>

    <div class="form-page">

        <div class="form-header">
            <h1>Tambah Mata Kuliah</h1>
            <p>Tambahkan mata kuliah baru ke KampusLMS.</p>
        </div>

        <div class="form-card">

            <form action="{{ route('courses.store') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label for="code">Kode Mata Kuliah</label>

                    <input
                        type="text"
                        id="code"
                        name="code"
                        class="form-control"
                        value="{{ old('code') }}"
                        placeholder="Contoh: SI301"
                    >

                    @error('code')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="name">Nama Mata Kuliah</label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        class="form-control"
                        value="{{ old('name') }}"
                        placeholder="Contoh: Pemrograman Web"
                    >

                    @error('name')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="description">Deskripsi</label>

                    <textarea
                        id="description"
                        name="description"
                        class="form-control"
                        placeholder="Deskripsi mata kuliah..."
                    >{{ old('description') }}</textarea>

                    @error('description')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="sks">SKS</label>

                    <input
                        type="number"
                        id="sks"
                        name="sks"
                        class="form-control"
                        value="{{ old('sks') }}"
                        min="1"
                        max="6"
                    >

                    @error('sks')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="lecturer_id">Dosen</label>

                    <select
                        id="lecturer_id"
                        name="lecturer_id"
                        class="form-control"
                    >
                        <option value="">Pilih dosen</option>

                        @foreach ($lecturers as $lecturer)
                            <option
                                value="{{ $lecturer->id }}"
                                @selected(old('lecturer_id') == $lecturer->id)
                            >
                                {{ $lecturer->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('lecturer_id')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="status">Status</label>

                    <select
                        id="status"
                        name="status"
                        class="form-control"
                    >
                        <option value="draft" @selected(old('status') === 'draft')>
                            Draft
                        </option>

                        <option value="active" @selected(old('status') === 'active')>
                            Active
                        </option>

                        <option value="archived" @selected(old('status') === 'archived')>
                            Archived
                        </option>
                    </select>

                    @error('status')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-actions">
                    <a href="{{ route('courses.index') }}" class="btn btn-secondary">
                        Batal
                    </a>

                    <button type="submit" class="btn btn-primary">
                        Simpan Mata Kuliah
                    </button>
                </div>

            </form>

        </div>

    </div>

</x-layout>