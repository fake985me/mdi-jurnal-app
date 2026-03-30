<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_investment_id',
        'contract_number',
        'contract_date',
        'start_date',
        'end_date',
        'contract_value',
        'status',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'contract_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'contract_value' => 'decimal:2',
    ];

    public function project()
    {
        return $this->belongsTo(ProjectInvestment::class, 'project_investment_id');
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public static function generateContractNumber(): string
    {
        $today = now()->format('Ymd');
        $prefix = "CTR-{$today}-";
        $last = static::where('contract_number', 'like', "{$prefix}%")
            ->orderBy('contract_number', 'desc')
            ->first();
        $next = $last ? ((int) substr($last->contract_number, -4)) + 1 : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
