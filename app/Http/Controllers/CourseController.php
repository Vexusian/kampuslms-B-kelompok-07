<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use Illuminate\Http\RedirectResponse;
use App\Models\User;

class CourseController extends Controller
{
    // -------------------------------------------------
    // STORE – menambah mata kuliah baru
    // -------------------------------------------------
    public function store(StoreCourseRequest $request): RedirectResponse
    {
        // $request->validated() hanya mengembalikan data yang telah lolos aturan.
        // Menggunakan all() berisiko menyertakan input yang tidak divalidasi
        // (mis. field _token, atau field yang tidak ada di rules).
        $course = Course::create($request->validated());

        return redirect()
            ->route('courses.index')
            ->with('success', "Mata kuliah '{$course->name}' berhasil ditambahkan.");
    }

    // -------------------------------------------------
    // UPDATE – mengubah data mata kuliah yang ada
    // -------------------------------------------------
    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        // validated() memberi array bersih yang hanya berisi field yang di‑rules.
        $course->update($request->validated());

        return redirect()
            ->route('courses.show', $course)
            ->with('success', "Mata kuliah '{$course->name}' berhasil diperbarui.");
    }
    

    // 2. CREATE: Menampilkan form untuk menambah mata kuliah
    public function create()
    {
        // Ambil daftar dosen untuk dropdown di form
        $lecturers = User::where('role', 'dosen')->get();
        return view('courses.create', compact('lecturers'));
    }

    // 4. SHOW: Menampilkan detail satu mata kuliah
    public function show(Course $course)
    {
        // Laravel otomatis mencari Course berdasarkan ID di URL (Route Model Binding)
        $course->load('lecturer', 'students', 'assignments');
        return view('courses.show', compact('course'));
    }

    // 5. EDIT: Menampilkan form edit untuk data yang sudah ada
    public function edit(Course $course)
    {
        $lecturers = User::where('role', 'dosen')->get();
        return view('courses.edit', compact('course', 'lecturers'));
    }



    // 7. DESTROY: Menghapus data dari database
    public function destroy(Course $course)
    {
        $course->delete();

        return redirect()->route('courses.index')
            ->with('success', 'Mata kuliah berhasil dihapus.');
    }
    /**
     * -------------------------------------------------------------------------
     *  Display a paginated list of courses with search & filter.
     *
     *  - Search      : `q`   (cari di kolom code atau name)
     *  - Filter      : `status`, `lecturer_id`
     *  - Pagination  : 15 item per halaman (configurable)
     *
     *  Semua parameter disimpan **di query string** sehingga
     *  ketika pengguna berpindah halaman (atau men‑refresh) filter tetap
     *  terlihat.
     * -------------------------------------------------------------------------
     */
    public function index(Request $request)
    {
        // 01 --------------------------------------------------------------
        // 1️⃣ Validasi ringan – hanya tipe data, tidak meng‑ubah query.
        $validated = $request->validate([
            'q'           => 'nullable|string|max:255',
            'status'      => 'nullable|in:draft,active,archived',
            'lecturer_id' => 'nullable|integer|exists:users,id',
            'page'        => 'nullable|integer|min:1',
        ]);

        // 02 --------------------------------------------------------------
        // 2️⃣ Mulai query builder – gunakan eager loading untuk
        //    menghindari N+1 (relasi `lecturer`).
        $query = Course::with('lecturer');        // <-- eager load

        // 03 --------------------------------------------------------------
        // 3️⃣ Pencarian (search) – gunakan bound parameters, tidak
        //    meng‑gabungkan string mentah → aman dari SQL‑Injection.
        if (!empty($validated['q'])) {
            $search = $validated['q'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                  ->orWhere('name', 'LIKE', "%{$search}%");
            });
        }

        // 04 --------------------------------------------------------------
        // 4️⃣ Filter status
        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        // 05 --------------------------------------------------------------
        // 5️⃣ Filter lecturer
        if (!empty($validated['lecturer_id'])) {
            $query->where('lecturer_id', $validated['lecturer_id']);
        }

        // 06 --------------------------------------------------------------
        // 6️⃣ Pagination – hasil otomatis menambahkan `page=` dan mempertahankan parameter pencarian/filter di query string.
        $courses = $query->orderBy('id', 'desc')
                         ->paginate(15)
                         ->withQueryString();

        // 07 --------------------------------------------------------------
        // 7️⃣ Return view (atau JSON API) dengan data + query‑string yang
        //    masih tersimpan, sehingga UI dapat men‑render filter aktif.
        return view('courses.index', compact('courses'));
    }

    
}