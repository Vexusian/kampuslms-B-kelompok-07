<?php

namespace Tests\Feature;

use App\Events\AssignmentPublished;
use App\Events\GradeSubmitted;
use App\Listeners\SendAssignmentPublishedNotification;
use App\Listeners\SendGradeSubmittedNotification;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\GradeSubmittedNotification;
use App\Notifications\NewAssignmentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    // ─── PERISTIWA A: TUGAS BARU DIPUBLIKASIKAN ───────────────────────

    public function test_assignment_store_dispatches_assignment_published_event(): void
    {
        Event::fake([AssignmentPublished::class]);

        $dosen = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);

        $this->actingAs($dosen)
            ->post(route('dosen.courses.assignments.store', $course->id), [
                'title' => 'Tugas 1 Arsitektur Event',
                'description' => 'Kerjakan modul event and listener.',
                'instructions' => 'Kumpulkan sebelum batas waktu.',
                'due_at' => now()->addDays(7)->toDateTimeString(),
            ]);

        Event::assertDispatched(AssignmentPublished::class, function (AssignmentPublished $event) {
            return $event->assignment->title === 'Tugas 1 Arsitektur Event';
        });
    }

    public function test_send_assignment_published_notification_listener_notifies_enrolled_students(): void
    {
        Notification::fake();

        $dosen = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);

        $enrolledStudent1 = User::factory()->create(['role' => 'mahasiswa']);
        $enrolledStudent2 = User::factory()->create(['role' => 'mahasiswa']);
        $unenrolledStudent = User::factory()->create(['role' => 'mahasiswa']);

        $course->students()->attach([
            $enrolledStudent1->id => ['enrolled_at' => now()],
            $enrolledStudent2->id => ['enrolled_at' => now()],
        ]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $dosen->id,
            'title' => 'Tugas Implementasi Notifikasi',
        ]);

        $listener = new SendAssignmentPublishedNotification();
        $listener->handle(new AssignmentPublished($assignment));

        // Mahasiswa yang terdaftar menerima notifikasi
        Notification::assertSentTo(
            [$enrolledStudent1, $enrolledStudent2],
            NewAssignmentNotification::class,
            function (NewAssignmentNotification $notification) use ($assignment) {
                return $notification->assignment->id === $assignment->id;
            }
        );

        // Mahasiswa yang tidak terdaftar TIDAK menerima notifikasi
        Notification::assertNotSentTo(
            $unenrolledStudent,
            NewAssignmentNotification::class
        );
    }

    public function test_new_assignment_notification_is_stored_in_database(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $dosen->id,
            'title' => 'Tugas Basis Data Lanjut',
        ]);

        // Kirim via sistem notifikasi langsung
        $student->notify(new NewAssignmentNotification($assignment));

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $student->id,
            'type' => NewAssignmentNotification::class,
        ]);

        $databaseNotification = $student->notifications()->first();
        $this->assertNotNull($databaseNotification);
        $this->assertEquals('new_assignment', $databaseNotification->data['type']);
        $this->assertEquals($assignment->id, $databaseNotification->data['assignment_id']);
        $this->assertEquals($assignment->title, $databaseNotification->data['assignment_title']);
        $this->assertEquals($course->name, $databaseNotification->data['course_name']);
    }

    // ─── PERISTIWA B: NILAI DIBERIKAN ────────────────────────────────

    public function test_grading_submission_dispatches_grade_submitted_event(): void
    {
        Event::fake([GradeSubmitted::class]);

        $dosen = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id, 'created_by' => $dosen->id]);
        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
        ]);

        $this->actingAs($dosen)
            ->put(route('submissions.grade', $submission->id), [
                'score' => 95,
                'feedback' => 'Kerja bagus, implementasi event & listener sangat rapi.',
            ]);

        Event::assertDispatched(GradeSubmitted::class, function (GradeSubmitted $event) use ($submission) {
            return $event->grade->submission_id === $submission->id &&
                   $event->grade->score === 95;
        });
    }

    public function test_send_grade_submitted_notification_listener_notifies_submission_owner(): void
    {
        Notification::fake();

        $dosen = User::factory()->create(['role' => 'dosen']);
        $submissionOwner = User::factory()->create(['role' => 'mahasiswa']);
        $otherStudent = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id, 'created_by' => $dosen->id]);

        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $submissionOwner->id,
        ]);

        $grade = Grade::factory()->create([
            'submission_id' => $submission->id,
            'graded_by' => $dosen->id,
            'score' => 88,
            'feedback' => 'Penjelasan komprehensif.',
            'graded_at' => now(),
        ]);

        $listener = new SendGradeSubmittedNotification();
        $listener->handle(new GradeSubmitted($grade));

        // Pemilik submission menerima notifikasi
        Notification::assertSentTo(
            $submissionOwner,
            GradeSubmittedNotification::class,
            function (GradeSubmittedNotification $notification) use ($grade) {
                return $notification->grade->id === $grade->id;
            }
        );

        // Mahasiswa lain TIDAK menerima notifikasi
        Notification::assertNotSentTo(
            $otherStudent,
            GradeSubmittedNotification::class
        );
    }

    public function test_grade_submitted_notification_is_stored_in_database(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id, 'created_by' => $dosen->id]);
        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
        ]);

        $grade = Grade::factory()->create([
            'submission_id' => $submission->id,
            'graded_by' => $dosen->id,
            'score' => 90,
            'feedback' => 'Sangat memuaskan.',
            'graded_at' => now(),
        ]);

        $student->notify(new GradeSubmittedNotification($grade));

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $student->id,
            'type' => GradeSubmittedNotification::class,
        ]);

        $databaseNotification = $student->notifications()->first();
        $this->assertNotNull($databaseNotification);
        $this->assertEquals('grade_submitted', $databaseNotification->data['type']);
        $this->assertEquals($submission->id, $databaseNotification->data['submission_id']);
        $this->assertEquals($assignment->id, $databaseNotification->data['assignment_id']);
        $this->assertEquals($assignment->title, $databaseNotification->data['assignment_title']);
    }

    // ─── END-TO-END FLOW DENGAN NOTIFICATION CONTROLLER ─────────────

    public function test_student_can_view_notifications_and_mark_as_read(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $dosen = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id, 'created_by' => $dosen->id]);

        $student->notify(new NewAssignmentNotification($assignment));
        $notification = $student->notifications()->first();

        $this->assertTrue($notification->unread());

        // Halaman notifikasi render dengan baik
        $response = $this->actingAs($student)->get(route('notifications.index'));
        $response->assertStatus(200)
            ->assertSee('Notifikasi Saya')
            ->assertSee($assignment->title);

        // Tandai sudah dibaca me-redirect ke detail tugas
        $readResponse = $this->actingAs($student)->patch(route('notifications.read', $notification->id));
        $readResponse->assertRedirect(route('assignments.show', $assignment->id));

        $this->assertTrue($notification->fresh()->read());
    }
}
