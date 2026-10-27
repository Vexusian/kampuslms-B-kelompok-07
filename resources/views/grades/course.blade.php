<x-layout title="Rekap Nilai - {{ $course->name }}">
    <h1>Rekap Nilai: {{ $course->name }}</h1>
    <p class="mb-6">Mata kuliah {{ $course->code }}</p>

    @forelse ($assignments as $assignment)
        <section class="mb-6">
            <h2>{{ $assignment->title }}</h2>

            @if ($assignment->submissions->isEmpty())
                <p>Belum ada pengumpulan untuk tugas ini.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            @if ($user->role !== 'mahasiswa')
                                <th>Mahasiswa</th>
                            @endif
                            <th>Nilai</th>
                            <th>Feedback</th>
                            <th>Tanggal dinilai</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($assignment->submissions as $submission)
                            <tr>
                                @if ($user->role !== 'mahasiswa')
                                    <td>{{ $submission->student?->name ?? 'Mahasiswa' }}</td>
                                @endif

                                <td>
                                    {{ $submission->grade
                                        ? $submission->grade->score . ' / ' . ($assignment->max_score ?? 100)
                                        : 'Belum dinilai' }}
                                </td>
                                <td>{{ $submission->grade?->feedback ?? '—' }}</td>
                                <td>
                                    {{ $submission->grade?->graded_at?->format('d M Y, H:i') ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    @empty
        <p>Belum ada tugas di mata kuliah ini.</p>
    @endforelse

    <a href="{{ route('courses.show', $course) }}" class="btn btn-secondary">
        Kembali ke mata kuliah
    </a>
</x-layout>