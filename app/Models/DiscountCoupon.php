<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscountCoupon extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'valid_from',
        'valid_to',
        'max_redemptions',
        'applies_all_properties',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'valid_from' => 'date',
            'valid_to' => 'date',
            'applies_all_properties' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'discount_coupon_properties', 'coupon_id', 'property_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'coupon_id');
    }
}
