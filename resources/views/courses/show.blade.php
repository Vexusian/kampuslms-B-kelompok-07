<x-layout title="{{ $course->name }}">

    <style>
        .detail-page {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 24px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 24px;
            color: #6b7280;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link:hover {
            color: #2563eb;
        }

        .detail-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
        }

        .detail-header {
            padding: 30px;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
        }

        .course-code {
            display: inline-block;
            margin-bottom: 10px;
            color: #2563eb;
            font-weight: 700;
            font-size: 14px;
        }

        .detail-header h1 {
            margin: 0;
            color: #111827;
            font-size: 30px;
        }

        .detail-body {
            padding: 30px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 28px;
        }

        .info-item {
            padding: 16px;
            background: #f9fafb;
            border-radius: 8px;
        }

        .info-label {
            display: block;
            margin-bottom: 5px;
            color: #6b7280;
            font-size: 13px;
        }

        .info-value {
            color: #111827;
            font-weight: 600;
        }

        .description {
            margin-top: 10px;
            color: #4b5563;
            line-height: 1.7;
        }

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 999px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 12px;
            font-weight: 600;
        }

        .detail-actions {
            display: flex;
            gap: 10px;
            margin-top: 28px;
        }

        .btn {
            display: inline-block;
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
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

    <div class="detail-page">

        <a href="{{ route('courses.index') }}" class="back-link">
            ← Kembali ke Daftar Mata Kuliah
        </a>

        <div class="detail-card">

            <div class="detail-header">
                <span class="course-code">
                    {{ $course->code }}
                </span>

                <h1>{{ $course->name }}</h1>
            </div>

            <div class="detail-body">

                <div class="info-grid">

                    <div class="info-item">
                        <span class="info-label">SKS</span>
                        <span class="info-value">
                            {{ $course->sks }} SKS
                        </span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Dosen Pengampu</span>
                        <span class="info-value">
                            {{ $course->lecturer->name }}
                        </span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Status</span>
                        <span class="status">
                            {{ ucfirst($course->status) }}
                        </span>
                    </div>

                </div>

                <div>
                    <span class="info-label">Deskripsi</span>

                    <p class="description">
                        {{ $course->description }}
                    </p>
                </div>

                <div class="detail-actions">

                    <a
                        href="{{ route('courses.edit', $course) }}"
                        class="btn btn-primary"
                    >
                        Edit Mata Kuliah
                    </a>

                    <a
                        href="{{ route('courses.index') }}"
                        class="btn btn-secondary"
                    >
                        Kembali
                    </a>

                </div>

            </div>

        </div>

    </div>

</x-layout>