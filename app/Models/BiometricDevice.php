<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class BiometricDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'brand',
        'model',
        'connection_type',
        'active',
        'ip_address',
        'port',
        'username',
        'password',
        'api_url',
        'api_method',
        'auth_type',
        'auth_token',
        'api_key_header',
        'sync_interval',
        'is_push_mode',
        'device_config',
        'mapping_config',
        'connection_status',
        'last_sync_at',
        'last_connection_test_at',
        'last_error',
        'total_records_synced',
        'sync_success_count',
        'sync_error_count',
    ];

    protected $casts = [
        'active' => 'boolean',
        'port' => 'integer',
        'sync_interval' => 'integer',
        'is_push_mode' => 'boolean',
        'device_config' => 'array',
        'mapping_config' => 'array',
        'last_sync_at' => 'datetime',
        'last_connection_test_at' => 'datetime',
        'total_records_synced' => 'integer',
        'sync_success_count' => 'integer',
        'sync_error_count' => 'integer',
    ];

    /**
     * Champs sensibles à chiffrer
     */
    protected $encrypted = ['password', 'auth_token'];

    /**
     * Relation avec les logs de synchronisation
     */
    public function syncLogs()
    {
        return $this->hasMany(BiometricSyncLog::class);
    }

    /**
     * Derniers logs de synchronisation
     */
    public function recentSyncLogs()
    {
        return $this->syncLogs()->orderBy('started_at', 'desc')->limit(10);
    }

    /**
     * Dernier log de synchronisation réussi
     */
    public function lastSuccessfulSync()
    {
        return $this->syncLogs()->where('status', 'success')->latest('completed_at')->first();
    }

    /**
     * Chiffrer automatiquement les champs sensibles
     */
    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->encrypted) && !empty($value)) {
            $value = Crypt::encryptString($value);
        }
        
        return parent::setAttribute($key, $value);
    }

    /**
     * Déchiffrer automatiquement les champs sensibles
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);
        
        if (in_array($key, $this->encrypted) && !empty($value)) {
            try {
                $value = Crypt::decryptString($value);
            } catch (\Exception $e) {
                // Si le déchiffrement échoue, retourner null
                $value = null;
            }
        }
        
        return $value;
    }

    /**
     * Scope pour les appareils actifs
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope pour les appareils par type de connexion
     */
    public function scopeByConnectionType($query, $type)
    {
        return $query->where('connection_type', $type);
    }

    /**
     * Scope pour les appareils par marque
     */
    public function scopeByBrand($query, $brand)
    {
        return $query->where('brand', $brand);
    }

    /**
     * Vérifier si l'appareil est connecté
     */
    public function isConnected()
    {
        return $this->connection_status === 'connected';
    }

    /**
     * Obtenir la configuration par défaut pour une marque
     */
    public static function getDefaultConfigForBrand($brand)
    {
        $configs = [
            'zkteco' => [
                'port' => 4370,
                'timeout' => 30,
                'sdk_path' => '/path/to/zkteco/sdk'
            ],
            'suprema' => [
                'port' => 443,
                'timeout' => 30,
                'sdk_path' => '/path/to/suprema/sdk'
            ],
            'hikvision' => [
                'port' => 80,
                'timeout' => 30,
                'sdk_path' => '/path/to/hikvision/sdk'
            ],
            'anviz' => [
                'port' => 8080,
                'timeout' => 30,
                'sdk_path' => '/path/to/anviz/sdk'
            ]
        ];

        return $configs[$brand] ?? [];
    }

    /**
     * Obtenir les champs de mapping par défaut
     */
    public static function getDefaultMappingForBrand($brand)
    {
        return [
            'employee_id_field' => 'employee_id',
            'datetime_field' => 'datetime',
            'punch_type_field' => 'punch_type',
            'device_id_field' => 'device_id',
            'datetime_format' => 'Y-m-d H:i:s'
        ];
    }

    /**
     * Obtenir l'icône pour la marque
     */
    public function getBrandIconAttribute()
    {
        $icons = [
            'zkteco' => 'bi-fingerprint',
            'suprema' => 'bi-shield-check',
            'hikvision' => 'bi-camera-video',
            'anviz' => 'bi-person-badge'
        ];

        return $icons[$this->brand] ?? 'bi-device-hdd';
    }

    /**
     * Obtenir le statut formaté
     */
    public function getFormattedStatusAttribute()
    {
        $statuses = [
            'connected' => ['text' => 'Connecté', 'class' => 'success', 'icon' => 'bi-check-circle'],
            'disconnected' => ['text' => 'Déconnecté', 'class' => 'warning', 'icon' => 'bi-exclamation-circle'],
            'error' => ['text' => 'Erreur', 'class' => 'danger', 'icon' => 'bi-x-circle'],
            'unknown' => ['text' => 'Inconnu', 'class' => 'secondary', 'icon' => 'bi-question-circle']
        ];

        return $statuses[$this->connection_status] ?? $statuses['unknown'];
    }

    /**
     * Marquer l'appareil comme connecté
     */
    public function markAsConnected()
    {
        $this->update([
            'connection_status' => 'connected',
            'last_connection_test_at' => now(),
            'last_error' => null
        ]);
    }

    /**
     * Marquer l'appareil comme déconnecté
     */
    public function markAsDisconnected($error = null)
    {
        $this->update([
            'connection_status' => 'disconnected',
            'last_connection_test_at' => now(),
            'last_error' => $error
        ]);
    }

    /**
     * Marquer l'appareil en erreur
     */
    public function markAsError($error)
    {
        $this->update([
            'connection_status' => 'error',
            'last_connection_test_at' => now(),
            'last_error' => $error
        ]);
    }

    /**
     * Incrémenter les statistiques de synchronisation
     */
    public function incrementSyncStats($inserted, $updated, $errors)
    {
        $this->increment('total_records_synced', $inserted + $updated);
        $this->increment('sync_success_count', $inserted + $updated);
        $this->increment('sync_error_count', $errors);
        $this->update(['last_sync_at' => now()]);
    }
} 