<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MsaContract extends Model
{
    use HasFactory;

    protected $table = 'msa_contracts';

    protected $fillable = [
        'project_investment_id',
        'msa_code',
        'start_date',
        'end_date',
        'sharing_profit_rate',
        'status',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'sharing_profit_rate' => 'decimal:2',
    ];

    public function project()
    {
        return $this->belongsTo(ProjectInvestment::class, 'project_investment_id');
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public static function generateMsaCode(): string
    {
        $today = now()->format('Ymd');
        $prefix = "MSA-{$today}-";
        $last = static::where('msa_code', 'like', "{$prefix}%")
            ->orderBy('msa_code', 'desc')
            ->first();
        $next = $last ? ((int) substr($last->msa_code, -4)) + 1 : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
