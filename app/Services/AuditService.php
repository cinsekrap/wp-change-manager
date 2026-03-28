<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    /**
     * Log an audit event.
     *
     * @param  string       $action       e.g. 'created', 'updated', 'deleted', 'status_changed', 'login'
     * @param  Model|null   $model        Optional polymorphic model
     * @param  string       $description  Human-readable description
     * @param  array|null   $oldValues    What changed from
     * @param  array|null   $newValues    What changed to
     */
    public static function log(
        string $action,
        ?Model $model = null,
        string $description = '',
        ?array $oldValues = null,
        ?array $newValues = null,
    ): AuditLog {
        $request = request();

        return AuditLog::create([
            'user_id'        => auth()->id(),
            'action'         => $action,
            'auditable_type' => $model ? get_class($model) : null,
            'auditable_id'   => $model?->getKey(),
            'description'    => $description,
            'old_values'     => $oldValues,
            'new_values'     => $newValues,
            'ip_address'     => $request?->ip(),
            'user_agent'     => $request?->userAgent() ? substr($request->userAgent(), 0, 500) : null,
        ]);
    }
}
