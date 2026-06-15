<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'payment_checkout_url')) {
                $table->text('payment_checkout_url')->nullable()->after('maib_checkout_id');
            }
            if (! Schema::hasColumn('bookings', 'payment_reminder_sent_at')) {
                $table->timestamp('payment_reminder_sent_at')->nullable()->after('payment_expires_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            foreach (['payment_checkout_url', 'payment_reminder_sent_at'] as $column) {
                if (Schema::hasColumn('bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
