[1mdiff --git a/app/Policies/SubmissionPolicy.php b/app/Policies/SubmissionPolicy.php[m
[1mindex a144e5f..425cd2f 100644[m
[1m--- a/app/Policies/SubmissionPolicy.php[m
[1m+++ b/app/Policies/SubmissionPolicy.php[m
[36m@@ -48,9 +48,13 @@[m [mpublic function view(User $user, Submission $submission): bool[m
     }[m
 [m
     /**[m
[31m-     * Mengumpulkan tugas:[m
[31m-     * - Mahasiswa yang terdaftar pada mata kuliah penugasan tersebut.[m
[31m-     * - Hanya jika belum pernah mengumpulkan (pengumpulan bersifat final/1 kali).[m
[32m+[m[32m     * Mengumpulkan / mengumpulkan ulang tugas:[m
[32m+[m[32m     * - Hanya mahasiswa yang terdaftar pada mata kuliah penugasan tersebut.[m
[32m+[m[32m     *[m
[32m+[m[32m     * Catatan: Pengumpulan ulang (re-submit) kini diizinkan.[m
[32m+[m[32m     * Controller menggunakan updateOrCreate sehingga berkas lama ditimpa.[m
[32m+[m[32m     * Constraint unique composite (assignment_id, user_id) di database[m
[32m+[m[32m     * menjamin hanya ada satu submission per mahasiswa per assignment.[m
      */[m
     public function create(User $user, Assignment $assignment): bool[m
     {[m
[36m@@ -58,17 +62,32 @@[m [mpublic function create(User $user, Assignment $assignment): bool[m
             return false;[m
         }[m
 [m
[31m-        $isEnrolled = $assignment->course->students()->where('users.id', $user->id)->exists();[m
[31m-        if (!$isEnrolled) {[m
[32m+[m[32m        // Mahasiswa tidak diizinkan mengumpulkan ulang jika sudah mengumpulkan[m
[32m+[m[32m        if ($assignment->submissions()->where('user_id', $user->id)->exists()) {[m
             return false;[m
         }[m
 [m
[31m-        return !$assignment->submissions()->where('user_id', $user->id)->exists();[m
[32m+[m[32m        return $assignment->course->students()->where('users.id', $user->id)->exists();[m
[32m+[m[32m    }[m
[32m+[m
[32m+[m[32m    /**[m
[32m+[m[32m     * Mengunduh berkas submission:[m
[32m+[m[32m     * - Mahasiswa pemilik submission.[m
[32m+[m[32m     * - Dosen pengampu mata kuliah terkait.[m
[32m+[m[32m     * - Admin.[m
[32m+[m[32m     *[m
[32m+[m[32m     * Logic identik dengan view(), didedikasi agar Gate::authorize('download', ...)[m
[32m+[m[32m     * dapat dipanggil secara eksplisit di endpoint download.[m
[32m+[m[32m     */[m
[32m+[m[32m    public function download(User $user, Submission $submission): bool[m
[32m+[m[32m    {[m
[32m+[m[32m        return $this->view($user, $submission);[m
     }[m
 [m
     /**[m
      * Memperbarui submission:[m
[31m-     * - Pengumpulan tugas bersifat final; mahasiswa tidak diperbolehkan mengedit kembali.[m
[32m+[m[32m     * - Pengumpulan ulang ditangani lewat create + updateOrCreate di Controller.[m
[32m+[m[32m     * - Method update() tetap false untuk mencegah PATCH/PUT langsung ke submission.[m
      */[m
     public function update(User $user, Submission $submission): bool[m
     {[m
