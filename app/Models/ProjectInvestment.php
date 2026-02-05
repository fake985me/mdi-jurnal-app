<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectInvestment extends Model
{
    use HasFactory;

    const TYPE_INVEST = 'invest';
    const TYPE_DESIGN_BUILD = 'design_build';

    protected $fillable = [
        'project_code',
        'type',
        'warehouse_id',
        'po_number',
        'po_value',
        'po_date',
        'project_name',
        'client_name',
        'client_contact',
        'project_location',
        'pic_name',
        'pic_phone',
        'description',
        'scope_of_work',
        'start_date',
        'expected_end_date',
        'duration_days',
        'actual_end_date',
        'status',
        'delivery_progress',
        'installation_progress',
        'total_value',
        'user_id',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'expected_end_date' => 'date',
        'actual_end_date' => 'date',
        'po_date' => 'date',
        'approved_at' => 'datetime',
        'total_value' => 'decimal:2',
        'po_value' => 'decimal:2',
        'delivery_progress' => 'integer',
        'installation_progress' => 'integer',
        'duration_days' => 'integer',
    ];

    protected $appends = ['overall_progress', 'is_on_schedule', 'days_remaining'];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($project) {
            // Auto-create dedicated warehouse for this project
            $warehouse = Warehouse::create([
                'code' => 'WH-' . $project->project_code,
                'name' => $project->project_name,
                'description' => 'Gudang khusus untuk project: ' . $project->project_name,
                'is_active' => true,
                'is_default' => false,
            ]);
            
            // Link warehouse to project
            $project->warehouse_id = $warehouse->id;
            $project->saveQuietly();

            // Auto-create MSA for invest type projects
            if ($project->type === self::TYPE_INVEST) {
                MSAProject::create([
                    'msa_code' => 'MSA-' . $project->project_code,
                    'project_investment_id' => $project->id,
                    'status' => 'active',
                    'reported_date' => now(),
                    'user_id' => $project->user_id,
                ]);
            }
        });
    }

    /**
     * Get the dedicated warehouse for this project
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items()
    {
        return $this->hasMany(ProjectInvestmentItem::class);
    }

    public function materials()
    {
        return $this->hasMany(ProjectMaterial::class);
    }

    public function progressLogs()
    {
        return $this->hasMany(ProjectProgressLog::class)->orderBy('created_at', 'desc');
    }

    public function tasks()
    {
        return $this->hasMany(ProjectTask::class);
    }

    public function msaProjects()
    {
        return $this->hasMany(MSAProject::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function calculateTotalValue()
    {
        $itemsTotal = $this->items()->sum('total_price');
        $materialsTotal = $this->materials()->sum('total_price');
        $this->total_value = $itemsTotal + $materialsTotal;
        $this->save();
    }

    public function getOverallProgressAttribute()
    {
        return round(($this->delivery_progress + $this->installation_progress) / 2);
    }

    public function getIsOnScheduleAttribute()
    {
        if (!$this->expected_end_date) return true;
        if ($this->status === 'completed') return true;
        return now()->lte($this->expected_end_date);
    }

    public function getDaysRemainingAttribute()
    {
        if (!$this->expected_end_date) return null;
        if ($this->status === 'completed') return 0;
        return now()->diffInDays($this->expected_end_date, false);
    }

    public function getTaskProgressAttribute()
    {
        $tasks = $this->tasks;
        if ($tasks->isEmpty()) {
            return 0;
        }
        
        $completed = $tasks->where('status', ProjectTask::STATUS_COMPLETED)->count();
        return round(($completed / $tasks->count()) * 100);
    }

    public function getOverdueTasksCountAttribute()
    {
        return $this->tasks()
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->whereNotIn('status', [ProjectTask::STATUS_COMPLETED, ProjectTask::STATUS_CANCELLED])
            ->count();
    }

    public function getMaterialsProgressAttribute()
    {
        $materials = $this->materials;
        if ($materials->isEmpty()) return 0;
        
        $totalPlanned = $materials->sum('quantity_planned');
        if ($totalPlanned <= 0) return 0;
        
        $totalInstalled = $materials->sum('quantity_installed');
        return round(($totalInstalled / $totalPlanned) * 100);
    }

    public function updateProgress($field, $value, $notes = null)
    {
        $oldValue = $this->$field;
        $this->$field = $value;
        $this->save();

        // Log the change
        ProjectProgressLog::logChange(
            $this->id,
            $field === 'status' ? ProjectProgressLog::TYPE_STATUS : 
                ($field === 'delivery_progress' ? ProjectProgressLog::TYPE_DELIVERY : ProjectProgressLog::TYPE_INSTALLATION),
            $field,
            $oldValue,
            $value,
            $notes
        );
    }
}

