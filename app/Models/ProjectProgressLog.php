<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectProgressLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_investment_id',
        'type',
        'field_name',
        'old_value',
        'new_value',
        'notes',
        'updated_by',
    ];

    const TYPE_DELIVERY = 'delivery';
    const TYPE_INSTALLATION = 'installation';
    const TYPE_STATUS = 'status';
    const TYPE_MATERIAL = 'material';
    const TYPE_OTHER = 'other';

    public function project()
    {
        return $this->belongsTo(ProjectInvestment::class, 'project_investment_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function logChange($projectId, $type, $fieldName, $oldValue, $newValue, $notes = null)
    {
        return static::create([
            'project_investment_id' => $projectId,
            'type' => $type,
            'field_name' => $fieldName,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'notes' => $notes,
            'updated_by' => auth()->id(),
        ]);
    }
}
