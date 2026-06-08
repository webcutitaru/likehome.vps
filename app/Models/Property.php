<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    protected $fillable = [
        'title', 'lot_id', 'slug', 'price', 'price_weekend', 'guests_included',
        'extra_guest_price', 'extra_guest_unit', 'location', 'description',
        'city', 'district', 'address', 'description_long', 'pre_checkin_email_message',
        'property_type', 'rooms', 'sleep_capacity', 'area_sqm', 'floor', 'min_stay',
        'check_in_start', 'check_in_end', 'check_out_start', 'check_out_end',
        'amenities', 'ical_import_link', 'ical_export_token', 'image_name', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'price_weekend' => 'decimal:2',
            'extra_guest_price' => 'decimal:2',
            'is_active' => 'boolean',
            'rooms' => 'integer',
            'sleep_capacity' => 'integer',
            'area_sqm' => 'integer',
            'floor' => 'integer',
            'min_stay' => 'integer',
            'guests_included' => 'integer',
        ];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PropertyTranslation::class);
    }

    /** @return array<string, mixed> */
    public function toLegacyArray(): array
    {
        return $this->attributesToArray();
    }
}
