<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipment extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'activo';

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_DISABLED = 'disabled';

    protected $table = 'equipment';

    protected $fillable = [
        'asset_code',
        'name',
        'description',
        'location',
        'equipment_type',
        'manufacturer',
        'model',
        'serial_number',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'equipment_id');
    }
}
