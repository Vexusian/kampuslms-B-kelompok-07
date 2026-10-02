<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'assignment_id' => $this->assignment_id,
            'user_id' => $this->user_id,
            'content' => $this->content,
            'file_path' => $this->file_path,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'student' => new UserResource($this->whenLoaded('student')),
            'assignment' => new AssignmentResource($this->whenLoaded('assignment')),
            'grade' => new GradeResource($this->whenLoaded('grade')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
