<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('role')->default('client');
            $t->string('account_status')->default('pending');
            $t->string('phone')->nullable();
            $t->string('city')->nullable();
            $t->timestamp('terms_accepted_at')->nullable();
        });
        Schema::create('driver_applications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained();
            $t->string('status')->default('pending');
            $t->string('business_type');
            $t->string('business_number')->nullable();
            $t->text('experience');
            $t->json('documents');
            $t->text('review_note')->nullable();
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('trips', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->string('origin');
            $t->string('destination');
            $t->string('country');
            $t->date('departure_date');
            $t->date('arrival_date');
            $t->text('pickup_points');
            $t->text('conditions');
            $t->unsignedInteger('price_cents');
            $t->unsignedInteger('capacity_kg');
            $t->string('status')->default('published');
            $t->timestamps();
        });
        Schema::create('bookings', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->unique();
            $t->foreignId('user_id')->constrained();
            $t->foreignId('trip_id')->constrained();
            $t->unsignedInteger('weight_kg');
            $t->text('contents');
            $t->unsignedInteger('declared_value_cents');
            $t->string('recipient_name');
            $t->string('recipient_phone');
            $t->text('recipient_address');
            $t->unsignedInteger('transport_cents');
            $t->unsignedInteger('fee_cents');
            $t->unsignedInteger('total_cents');
            $t->string('status')->default('pending');
            $t->string('payment_status')->default('unpaid');
            $t->string('declaration_version');
            $t->timestamp('declared_at');
            $t->timestamps();
        });
        Schema::create('booking_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('booking_id')->constrained();
            $t->foreignId('user_id')->constrained();
            $t->text('body');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_messages');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('trips');
        Schema::dropIfExists('driver_applications');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['role', 'account_status', 'phone', 'city', 'terms_accepted_at']));
    }
};
