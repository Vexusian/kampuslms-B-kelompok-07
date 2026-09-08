{{-- courses/index.blade.php --}}
<x-layout title="Daftar Mata Kuliah">
    <h1>Daftar Mata Kuliah</h1>
    @foreach ($courses as $course)
        <a href="{{ route('courses.show', $course) }}">{{ $course->name }}</a>
    @endforeach
</x-layout>