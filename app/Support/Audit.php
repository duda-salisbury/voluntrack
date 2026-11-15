<?php

namespace App\Support;

use App\Models\AuditEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class Audit
{
    public static function record(
        string $type,
        string $category,
        string $description,
        ?int $actorId = null,
        ?int $organizationId = null,
        ?int $subjectUserId = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?array $metadata = null,
        ?string $ipAddress = null,
    ): void {
        $data = [
            'event_type' => $type,
            'category' => $category,
            'description' => $description,
            'actor_id' => $actorId,
            'organization_id' => $organizationId,
            'subject_user_id' => $subjectUserId,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'metadata' => $metadata,
            'ip_address' => $ipAddress,
            'created_at' => now(),
        ];

        DB::afterCommit(function () use ($data) {
            try {
                AuditEvent::create($data);
            } catch (Throwable $exception) {
                try {
                    Log::error('Audit event could not be recorded.', [
                        'event_type' => $data['event_type'],
                        'exception_type' => $exception::class,
                    ]);
                } catch (Throwable $loggingException) {
                    error_log('Audit event could not be recorded; application logging also failed.');
                }
            }
        });
    }
}
