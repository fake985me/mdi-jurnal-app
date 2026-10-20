<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['attachments'];
    protected $appends = ['files'];
    protected $casts = ['expense_date' => 'date', 'due_date' => 'date', 'attachments' => 'array',
        'subtotal' => 'decimal:2', 'tax_amount' => 'decimal:2', 'total_amount' => 'decimal:2'];

    public function items() { return $this->hasMany(ExpenseItem::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function payment() { return $this->morphOne(Payment::class, 'payable'); }
    public function getFilesAttribute(): array
    {
        return collect($this->attachments ?? [])->map(fn ($file) => collect($file)->except('path')->all())->all();
    }
}
