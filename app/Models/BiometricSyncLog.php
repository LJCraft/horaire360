<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BiometricSyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'biometric_device_id',
        'sync_session_id',
        'sync_type',
        'status',
        'records_processed',
        'records_inserted',
        'records_updated',
        'records_ignored',
        'records_errors',
        'started_at',
        'completed_at',
        'duration_seconds',
        'summary',
        'error_details',
        'sync_metadata',
        'initiated_by',
        'client_ip',
    ];

    protected $casts = [
        'records_processed' => 'integer',
        'records_inserted' => 'integer',
        'records_updated' => 'integer',
        'records_ignored' => 'integer',
        'records_errors' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_seconds' => 'integer',
        'error_details' => 'array',
        'sync_metadata' => 'array',
    ];

    /**
     * Relation avec l'appareil biométrique
     */
    public function biometricDevice()
    {
        return $this->belongsTo(BiometricDevice::class);
    }

    /**
     * Marquer la synchronisation comme terminée avec succès
     */
    public function markAsSuccessful($summary = null, $metadata = null)
    {
        $this->update([
            'status' => 'success',
            'completed_at' => now(),
            'duration_seconds' => $this->started_at->diffInSeconds(now()),
            'summary' => $summary,
            'sync_metadata' => $metadata
        ]);
    }

    /**
     * Marquer la synchronisation comme échouée
     */
    public function markAsFailed($errorDetails, $summary = null)
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'duration_seconds' => $this->started_at->diffInSeconds(now()),
            'error_details' => $errorDetails,
            'summary' => $summary
        ]);
    }
} 