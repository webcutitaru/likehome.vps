<?php

namespace App\Services;

use App\Legacy\LegacyBridge;
use App\Models\DiscountCoupon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

final class DiscountCouponService
{
    public function normalizeCode(string $code): string
    {
        LegacyBridge::boot();

        return lh_coupon_normalize_code($code);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(array $data, ?DiscountCoupon $coupon = null): DiscountCoupon
    {
        $code = $this->normalizeCode((string) ($data['code'] ?? ''));
        $discountType = in_array($data['discount_type'] ?? '', ['percent', 'fixed'], true)
            ? (string) $data['discount_type']
            : 'percent';
        $discountValue = (float) str_replace(',', '.', (string) ($data['discount_value'] ?? '0'));
        $validFrom = $this->nullableDate($data['valid_from'] ?? null);
        $validTo = $this->nullableDate($data['valid_to'] ?? null);
        $maxRedemptions = $this->nullablePositiveInt($data['max_redemptions'] ?? null);
        $appliesAll = (bool) ($data['applies_all_properties'] ?? false);
        $isActive = (bool) ($data['is_active'] ?? true);
        $propertyIds = array_values(array_filter(
            array_map('intval', (array) ($data['property_ids'] ?? [])),
            fn (int $id): bool => $id > 0,
        ));

        if (strlen($code) < 2) {
            throw new InvalidArgumentException('Codul cuponului trebuie să aibă cel puțin 2 caractere.');
        }

        if (! $appliesAll && $propertyIds === []) {
            throw new InvalidArgumentException('Selectează cel puțin o proprietate sau bifează „Toate proprietățile”.');
        }

        if ($discountType === 'percent' && ($discountValue <= 0 || $discountValue > 100)) {
            throw new InvalidArgumentException('Procentul trebuie să fie între 1 și 100.');
        }

        if ($discountType === 'fixed' && $discountValue <= 0) {
            throw new InvalidArgumentException('Suma fixă trebuie să fie mai mare decât 0.');
        }

        if ($validFrom !== null && $validTo !== null && $validTo < $validFrom) {
            throw new InvalidArgumentException('„Valid până la” nu poate fi înaintea „Valid de la”.');
        }

        $couponId = $coupon?->id ?? 0;

        if (DiscountCoupon::query()->where('code', $code)->where('id', '!=', $couponId)->exists()) {
            throw new InvalidArgumentException('Există deja un cupon cu acest cod.');
        }

        try {
            return DB::transaction(function () use (
                $coupon,
                $code,
                $discountType,
                $discountValue,
                $validFrom,
                $validTo,
                $maxRedemptions,
                $appliesAll,
                $isActive,
                $propertyIds,
            ): DiscountCoupon {
                $record = $coupon ?? new DiscountCoupon;

                $record->fill([
                    'code' => $code,
                    'discount_type' => $discountType,
                    'discount_value' => $discountValue,
                    'valid_from' => $validFrom,
                    'valid_to' => $validTo,
                    'max_redemptions' => $maxRedemptions,
                    'applies_all_properties' => $appliesAll,
                    'is_active' => $isActive,
                ]);
                $record->save();

                $record->properties()->sync($appliesAll ? [] : $propertyIds);

                return $record->refresh();
            });
        } catch (Throwable $e) {
            throw new InvalidArgumentException('Nu s-a putut salva cuponul (cod duplicat sau eroare DB).', 0, $e);
        }
    }

    public function toggleActive(DiscountCoupon $coupon): DiscountCoupon
    {
        $coupon->is_active = ! $coupon->is_active;
        $coupon->save();

        return $coupon;
    }

    private function nullableDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = lh_calendar_ymd($value);
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        if (! $date || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException('Data nu este validă.');
        }

        return $value;
    }

    private function nullablePositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $int = filter_var($value, FILTER_VALIDATE_INT);

        if ($int === false || $int < 1) {
            return null;
        }

        return $int;
    }
}
