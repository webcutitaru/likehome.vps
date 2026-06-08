<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('properties')) {
            Schema::create('properties', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('lot_id', 64)->default('');
                $table->string('slug')->unique();
                $table->decimal('price', 10, 2)->default(0);
                $table->decimal('price_weekend', 10, 2)->nullable();
                $table->unsignedSmallInteger('guests_included')->nullable();
                $table->decimal('extra_guest_price', 10, 2)->nullable();
                $table->string('extra_guest_unit', 32)->default('per_guest_per_night');
                $table->string('location')->default('');
                $table->string('description', 512)->default('');
                $table->string('city', 128)->default('Chișinău');
                $table->string('district', 128)->default('');
                $table->string('address')->default('');
                $table->text('description_long');
                $table->text('pre_checkin_email_message')->nullable();
                $table->string('property_type', 64)->default('Apartament');
                $table->unsignedTinyInteger('rooms')->default(0);
                $table->unsignedTinyInteger('sleep_capacity')->nullable();
                $table->unsignedSmallInteger('area_sqm')->default(0);
                $table->smallInteger('floor')->default(0);
                $table->unsignedTinyInteger('min_stay')->default(1);
                $table->string('check_in_start', 5)->default('14:00');
                $table->string('check_in_end', 5)->default('21:00');
                $table->string('check_out_start', 5)->default('08:00');
                $table->string('check_out_end', 5)->default('11:00');
                $table->text('amenities');
                $table->string('ical_import_link', 2048)->default('');
                $table->string('ical_export_token', 64)->default('');
                $table->text('image_name');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('is_active');
                $table->index('city');
                $table->index('district');
            });
        }

        if (! Schema::hasTable('property_translations')) {
            Schema::create('property_translations', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('property_id');
                $table->string('locale', 5);
                $table->string('title');
                $table->string('slug');
                $table->string('description', 512)->default('');
                $table->text('description_long');

                $table->unique(['property_id', 'locale']);
                $table->unique(['slug', 'locale']);
                $table->index('locale');
            });
        }

        if (! Schema::hasTable('property_pricing_periods')) {
            Schema::create('property_pricing_periods', function (Blueprint $table) {
                $table->id();
                $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
                $table->date('date_start');
                $table->date('date_end');
                $table->decimal('price', 10, 2);
                $table->decimal('price_weekend', 10, 2)->nullable();
                $table->string('label', 128)->nullable();
                $table->unsignedTinyInteger('min_stay')->nullable();

                $table->index(['property_id', 'date_start', 'date_end']);
            });
        }

        if (! Schema::hasTable('property_stay_length_discounts')) {
            Schema::create('property_stay_length_discounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
                $table->foreignId('pricing_period_id')->nullable()->constrained('property_pricing_periods')->nullOnDelete();
                $table->unsignedSmallInteger('min_nights');
                $table->decimal('value', 10, 2);
                $table->string('unit', 16);

                $table->index(['property_id', 'pricing_period_id'], 'prop_stay_disc_prop_period_idx');
            });
        }

        if (! Schema::hasTable('discount_coupons')) {
            Schema::create('discount_coupons', function (Blueprint $table) {
                $table->id();
                $table->string('code', 64)->unique();
                $table->string('discount_type', 16);
                $table->decimal('discount_value', 10, 2);
                $table->date('valid_from')->nullable();
                $table->date('valid_to')->nullable();
                $table->unsignedInteger('max_redemptions')->nullable();
                $table->boolean('applies_all_properties')->default(false);
                $table->boolean('is_active')->default(true);

                $table->index('is_active');
            });
        }

        if (! Schema::hasTable('discount_coupon_properties')) {
            Schema::create('discount_coupon_properties', function (Blueprint $table) {
                $table->id();
                $table->foreignId('coupon_id')->constrained('discount_coupons')->cascadeOnDelete();
                $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();

                $table->unique(['coupon_id', 'property_id']);
            });
        }

        if (! Schema::hasTable('bookings')) {
            Schema::create('bookings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('property_id')->constrained('properties')->restrictOnDelete();
                $table->string('guest_name');
                $table->string('guest_phone', 64);
                $table->string('guest_email');
                $table->date('check_in');
                $table->date('check_out');
                $table->unsignedTinyInteger('guests');
                $table->decimal('total_price', 10, 2);
                $table->foreignId('coupon_id')->nullable()->constrained('discount_coupons')->nullOnDelete();
                $table->string('coupon_code', 64)->nullable();
                $table->decimal('coupon_discount_amount', 10, 2)->default(0);
                $table->string('status', 16)->default('confirmed');
                $table->string('locale', 5)->default('ro');
                $table->string('payment_method', 16)->nullable();
                $table->string('payment_status', 32)->nullable();
                $table->decimal('online_discount_percent', 5, 2)->nullable();
                $table->decimal('online_discount_amount', 10, 2)->nullable();
                $table->decimal('payment_due_amount', 10, 2)->nullable();
                $table->decimal('payment_amount', 10, 2)->nullable();
                $table->string('maib_checkout_id', 128)->nullable();
                $table->string('maib_payment_id', 128)->nullable();
                $table->string('maib_refund_id', 128)->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamp('payment_expires_at')->nullable();
                $table->decimal('refunded_amount', 10, 2)->default(0);
                $table->timestamp('checkin_reminder_sent_at')->nullable();
                $table->timestamps();

                $table->index(['property_id', 'check_in', 'check_out']);
                $table->index('status');
                $table->index('payment_status');
                $table->index('maib_checkout_id');
                $table->index('coupon_id');
            });
        }

        if (! Schema::hasTable('blocked_dates')) {
            Schema::create('blocked_dates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
                $table->date('start_date');
                $table->date('end_date');
                $table->string('source', 32);
                $table->string('external_event_id', 128)->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();

                $table->index(['property_id', 'start_date', 'end_date']);
                $table->index(['property_id', 'source']);
                $table->index(['property_id', 'source', 'external_event_id']);
            });
        }

        if (! Schema::hasTable('admin_activity_log')) {
            Schema::create('admin_activity_log', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 64);
                $table->string('entity_type', 32)->nullable();
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->text('details')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index('action');
                $table->index(['entity_type', 'entity_id']);
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_activity_log');
        Schema::dropIfExists('blocked_dates');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('discount_coupon_properties');
        Schema::dropIfExists('discount_coupons');
        Schema::dropIfExists('property_stay_length_discounts');
        Schema::dropIfExists('property_pricing_periods');
        Schema::dropIfExists('property_translations');
        Schema::dropIfExists('properties');
    }
};
