<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertyPricingPeriod extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'property_id',
        'date_start',
        'date_end',
        'price',
        'price_weekend',
        'label',
        'min_stay',
    ];

    protected function casts(): array
    {
        return [
            'date_start' => 'date',
            'date_end' => 'date',
            'price' => 'decimal:2',
            'price_weekend' => 'decimal:2',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function stayLengthDiscounts(): HasMany
    {
        return $this->hasMany(PropertyStayLengthDiscount::class, 'pricing_period_id');
    }
}
