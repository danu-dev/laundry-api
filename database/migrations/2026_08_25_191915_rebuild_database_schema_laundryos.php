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
        // 1. Businesses
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('timezone')->default('Asia/Jakarta');
            $table->string('logo_path')->nullable();
            $table->text('opening_hours')->nullable();
            $table->text('receipt_footer_message')->nullable(); // PRD requirement
            $table->timestamps();
        });

        // Add business_id to users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->constrained('businesses')->nullOnDelete()->after('id');
        });

        // 2. Customers
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Allow searching by phone per business
            $table->index(['business_id', 'phone']);
        });

        // 3. Services
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('name');
            $table->string('pricing_type')->default('PER_KG'); // PER_KG
            $table->integer('price'); // Storing money as integer (Rupiah)
            $table->integer('estimated_duration_minutes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['business_id', 'name']);
        });

        // 3.5 Extras (Predefined extras as resolved in decision #1)
        Schema::create('extras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('name');
            $table->integer('price'); // Integer Rupiah
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['business_id', 'name']);
        });

        // 4. Orders
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->string('order_number');
            $table->string('status')->default('NEW'); // NEW, WASHING, IRONING, READY, COMPLETED

            // Financial fields - Integers for IDR
            $table->integer('subtotal')->default(0);
            $table->integer('extras_total')->default(0);
            $table->integer('total')->default(0);
            $table->string('payment_status')->default('UNPAID'); // UNPAID, PARTIAL, PAID

            $table->timestamp('estimated_completion_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->string('tracking_token_hash')->unique()->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'order_number']);
            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'payment_status']);
            $table->index(['business_id', 'estimated_completion_at']);
        });

        // 5. Order Items
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->string('service_name_snapshot');
            $table->integer('unit_price'); // Snapshot
            $table->decimal('quantity', 10, 2); // Decimal for weight (4.5 kg)
            $table->string('unit')->default('kg');
            $table->integer('subtotal');
            $table->timestamps();
        });

        // 5.5 Order Extras
        Schema::create('order_extras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('extra_id')->nullable()->constrained('extras')->nullOnDelete();
            $table->string('extra_name_snapshot');
            $table->integer('price'); // Snapshot
            $table->timestamps();
        });

        // 6. Payments
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->integer('amount');
            $table->string('method'); // CASH, QRIS, TRANSFER
            $table->string('status')->default('PAID');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        // 7. Order Status History
        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 8. Inventory Items
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('name');
            $table->string('unit');
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('minimum_quantity', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // 9. Expenses
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('category');
            $table->text('description')->nullable();
            $table->integer('amount');
            $table->date('expense_date')->nullable();
            $table->timestamps();
        });

        // 10. Automation Settings
        Schema::create('automation_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->boolean('tracking_enabled')->default(true);
            $table->boolean('ready_notification_enabled')->default(true);
            $table->boolean('pickup_reminder_enabled')->default(true);
            $table->boolean('unpaid_reminder_enabled')->default(true);
            $table->boolean('daily_summary_enabled')->default(true);
            $table->boolean('weekly_summary_enabled')->default(true);
            $table->boolean('overdue_alert_enabled')->default(true);
            $table->integer('pickup_reminder_delay_hours')->default(24);
            $table->timestamps();
        });

        // 11. Notifications
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->cascadeOnDelete();
            $table->string('type'); // e.g., ORDER_READY, PICKUP_REMINDER
            $table->string('channel'); // IN_APP, WHATSAPP, EMAIL
            $table->string('status')->default('PENDING'); // PENDING, SENT, FAILED, CANCELLED
            $table->text('payload')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            // To ensure we don't send duplicates, e.g., only one READY notification per order
            $table->string('idempotency_key')->nullable();
            $table->unique(['business_id', 'idempotency_key']);
        });

        // 12. Automation Logs
        Schema::create('automation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->cascadeOnDelete();
            $table->string('event');
            $table->string('action');
            $table->string('status'); // SUCCESS, FAILED, SKIPPED
            $table->text('metadata')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('automation_settings');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_extras');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('extras');
        Schema::dropIfExists('services');
        Schema::dropIfExists('customers');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropColumn('business_id');
        });

        Schema::dropIfExists('businesses');
    }
};
