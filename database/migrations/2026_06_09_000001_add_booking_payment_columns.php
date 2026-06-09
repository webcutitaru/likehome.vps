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
            if (! Schema::hasColumn('bookings', 'locale')) {
                $table->string('locale', 5)->default('ro')->after('status');
            }
            if (! Schema::hasColumn('bookings', 'payment_method')) {
                $table->string('payment_method', 16)->nullable()->after('locale');
            }
            if (! Schema::hasColumn('bookings', 'payment_status')) {
                $table->string('payment_status', 32)->nullable()->after('payment_method');
            }
            if (! Schema::hasColumn('bookings', 'online_discount_percent')) {
                $table->decimal('online_discount_percent', 5, 2)->nullable()->after('payment_status');
            }
            if (! Schema::hasColumn('bookings', 'online_discount_amount')) {
                $table->decimal('online_discount_amount', 10, 2)->nullable()->after('online_discount_percent');
            }
            if (! Schema::hasColumn('bookings', 'payment_due_amount')) {
                $table->decimal('payment_due_amount', 10, 2)->nullable()->after('online_discount_amount');
            }
            if (! Schema::hasColumn('bookings', 'payment_amount')) {
                $table->decimal('payment_amount', 10, 2)->nullable()->after('payment_due_amount');
            }
            if (! Schema::hasColumn('bookings', 'maib_checkout_id')) {
                $table->string('maib_checkout_id', 128)->nullable()->after('payment_amount');
            }
            if (! Schema::hasColumn('bookings', 'maib_payment_id')) {
                $table->string('maib_payment_id', 128)->nullable()->after('maib_checkout_id');
            }
            if (! Schema::hasColumn('bookings', 'maib_refund_id')) {
                $table->string('maib_refund_id', 128)->nullable()->after('maib_payment_id');
            }
            if (! Schema::hasColumn('bookings', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('maib_refund_id');
            }
            if (! Schema::hasColumn('bookings', 'payment_expires_at')) {
                $table->timestamp('payment_expires_at')->nullable()->after('paid_at');
            }
            if (! Schema::hasColumn('bookings', 'refunded_amount')) {
                $table->decimal('refunded_amount', 10, 2)->default(0)->after('payment_expires_at');
            }
        });

        if (Schema::hasColumn('bookings', 'payment_status') && ! Schema::hasIndex('bookings', 'bookings_payment_status_index')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->index('payment_status');
            });
        }
        if (Schema::hasColumn('bookings', 'maib_checkout_id') && ! Schema::hasIndex('bookings', 'bookings_maib_checkout_id_index')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->index('maib_checkout_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            $columns = [
                'locale',
                'payment_method',
                'payment_status',
                'online_discount_percent',
                'online_discount_amount',
                'payment_due_amount',
                'payment_amount',
                'maib_checkout_id',
                'maib_payment_id',
                'maib_refund_id',
                'paid_at',
                'payment_expires_at',
                'refunded_amount',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
