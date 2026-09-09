<x-layout>
    <x-slot:title>
        Daftar Mata Kuliah - LMS Kampus
    </x-slot:title>

    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Daftar Mata Kuliah</h2>
            <p class="text-gray-600">Pilih mata kuliah untuk melihat detail dan materi pembelajaran.</p>
        </div>

        <a href="{{ route('courses.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-4 py-2 rounded-lg shadow-sm">
            + Tambah Mata Kuliah
        </a>
    </div>

    <!-- Grid Daftar Mata Kuliah -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($courses as $course)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex flex-col justify-between">
                <div class="p-5">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider bg-indigo-100 text-indigo-800 px-2 py-1 rounded">
                            {{ $course['code'] }}
                        </span>
                        <span class="text-sm text-gray-500 font-medium">
                            {{ $course['sks'] }} SKS
                        </span>
                    </div>

                    <h3 class="text-lg font-bold text-gray-900 mb-1">
                        {{ $course['title'] }}
                    </h3>

                    <p class="text-sm text-gray-600 mb-3">
                        Dosen: <span class="font-medium text-gray-800">{{ $course['lecturer'] }}</span>
                    </p>

                    <p class="text-sm text-gray-500 line-clamp-2">
                        {{ $course['description'] }}
                    </p>
                </div>

                <div class="bg-gray-50 px-5 py-3 border-t border-gray-100 text-right">
                    <a href="{{ route('courses.show', ['course' => $course['id']]) }}" class="text-indigo-600 hover:text-indigo-800 font-semibold text-sm">
                        Lihat Detail &rarr;
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white p-6 text-center text-gray-500 rounded-lg border border-gray-200">
                Belum ada data mata kuliah yang tersedia.
            </div>
        @endforelse
    </div>
</x-layout>