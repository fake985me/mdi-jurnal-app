<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MSAProject extends Model
{
    use HasFactory;

    protected $table = 'msa_projects';

    protected $fillable = [
        'msa_code',
        'project_investment_id',
        'product_id',
        'quantity',
        'issue_type',
        'issue_description',
        'status',
        'reported_date',
        'resolved_date',
        'resolution',
        'condition',
        'user_id',
    ];

    protected $casts = [
        'reported_date' => 'date',
        'resolved_date' => 'date',
        'quantity' => 'integer',
    ];

    public function project()
    {
        return $this->belongsTo(ProjectInvestment::class, 'project_investment_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
