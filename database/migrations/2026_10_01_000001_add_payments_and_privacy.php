<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('stripe_account_id')->nullable()->unique();
        });
        Schema::table('bookings', function (Blueprint $t) {
            $t->string('stripe_session_id')->nullable()->unique();
            $t->text('stripe_checkout_url')->nullable();
            $t->timestamp('stripe_expires_at')->nullable();
            $t->string('stripe_payment_intent')->nullable()->index();
            $t->string('stripe_destination')->nullable();
            $t->boolean('stripe_livemode')->nullable();
            $t->timestamp('paid_at')->nullable();
        });
        Schema::create('payment_events', function (Blueprint $t) {
            $t->id();
            $t->string('stripe_event_id')->unique();
            $t->string('type');
            $t->timestamps();
        });
        Schema::create('privacy_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->string('type');
            $t->text('details')->nullable();
            $t->string('status')->default('pending');
            $t->text('response')->nullable();
            $t->timestamp('due_at');
            $t->timestamp('resolved_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_requests');
        Schema::dropIfExists('payment_events');
        Schema::table('bookings', fn (Blueprint $t) => $t->dropColumn(['stripe_session_id', 'stripe_checkout_url', 'stripe_expires_at', 'stripe_payment_intent', 'stripe_destination', 'stripe_livemode', 'paid_at']));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('stripe_account_id'));
    }
};
