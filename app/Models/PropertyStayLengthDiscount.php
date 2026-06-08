<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyStayLengthDiscount extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'property_id',
        'pricing_period_id',
        'min_nights',
        'value',
        'unit',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function pricingPeriod(): BelongsTo
    {
        return $this->belongsTo(PropertyPricingPeriod::class, 'pricing_period_id');
    }
}
