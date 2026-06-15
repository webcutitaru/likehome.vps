<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    protected $fillable = [
        'property_id',
        'guest_name',
        'guest_phone',
        'guest_email',
        'check_in',
        'check_out',
        'guests',
        'total_price',
        'coupon_id',
        'coupon_code',
        'coupon_discount_amount',
        'status',
        'locale',
        'payment_method',
        'payment_status',
        'online_discount_percent',
        'online_discount_amount',
        'payment_due_amount',
        'payment_amount',
        'maib_checkout_id',
        'payment_checkout_url',
        'maib_payment_id',
        'maib_refund_id',
        'paid_at',
        'payment_expires_at',
        'payment_reminder_sent_at',
        'refunded_amount',
        'checkin_reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'total_price' => 'decimal:2',
            'coupon_discount_amount' => 'decimal:2',
            'online_discount_percent' => 'decimal:2',
            'online_discount_amount' => 'decimal:2',
            'payment_due_amount' => 'decimal:2',
            'payment_amount' => 'decimal:2',
            'refunded_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'payment_expires_at' => 'datetime',
            'payment_reminder_sent_at' => 'datetime',
            'checkin_reminder_sent_at' => 'datetime',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(DiscountCoupon::class, 'coupon_id');
    }

    public function blockedDates(): HasMany
    {
        return $this->hasMany(BlockedDate::class);
    }
}
