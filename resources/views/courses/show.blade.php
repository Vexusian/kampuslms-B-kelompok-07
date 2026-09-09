<x-layout>
    <x-slot:title>
        {{ $course['title'] }} - Detail Mata Kuliah
    </x-slot:title>

    <div class="mb-4">
        <a href="{{ route('courses.index') }}" class="text-indigo-600 hover:underline font-medium text-sm">
            &larr; Kembali ke Daftar Mata Kuliah
        </a>
    </div>

    <!-- Detail Card -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex flex-col md:flex-row md:justify-between md:items-start mb-4">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider bg-indigo-100 text-indigo-800 px-2.5 py-1 rounded">
                    {{ $course['code'] }}
                </span>
                <h2 class="text-3xl font-bold text-gray-900 mt-2">
                    {{ $course['title'] }}
                </h2>
                <p class="text-md text-gray-600 mt-1">
                    Dosen Pengampu: <strong class="text-gray-800">{{ $course['lecturer'] }}</strong>
                </p>
            </div>

            <div class="mt-4 md:mt-0 flex items-center gap-3">
                <span class="bg-gray-100 text-gray-700 text-sm font-semibold px-3 py-1.5 rounded-md border">
                    {{ $course['sks'] }} SKS
                </span>

                <a href="{{ route('courses.edit', ['course' => $course['id']]) }}" class="bg-amber-500 hover:bg-amber-600 text-white font-medium text-sm px-3 py-1.5 rounded-md">
                    Edit
                </a>
            </div>
        </div>

        <hr class="my-4 border-gray-100">

        <h3 class="text-lg font-semibold text-gray-800 mb-2">Deskripsi Mata Kuliah</h3>
        <p class="text-gray-700 leading-relaxed">
            {{ $course['description'] }}
        </p>
    </div>

    <!-- Placeholder Modul Berikutnya -->
    <div class="bg-gray-50 border border-dashed border-gray-300 rounded-lg p-6 text-center text-gray-500">
        <p class="font-medium">Modul Materi & Tugas akan ditampilkan di area ini pada tahap pengembangan selanjutnya.</p>
    </div>
</x-layout>