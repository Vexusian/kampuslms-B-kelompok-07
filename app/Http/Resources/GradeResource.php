<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GradeResource extends JsonResource
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
            'submission_id' => $this->submission_id,
            'graded_by' => $this->graded_by,
            'score' => $this->score,
            'feedback' => $this->feedback,
            'graded_at' => $this->graded_at?->toIso8601String() ?? $this->created_at?->toIso8601String(),
            'grader' => new UserResource($this->whenLoaded('grader')),
        ];
    }
}
