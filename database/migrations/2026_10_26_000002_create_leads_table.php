<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            // order | account | principal | application | enquiry (App\Enums\LeadType)
            $table->string('type', 16)->index();
            $table->string('status', 20)->default('new')->index();
            $table->string('name', 120);
            $table->string('business_name', 160)->nullable();
            $table->string('phone', 30);
            $table->string('phone_digits', 20)->nullable()->index();
            $table->string('email')->nullable();
            $table->string('contact_pref', 10)->default('phone');
            // Order requests from (or applications that became) an approved trade account.
            $table->foreignId('trade_account_id')->nullable()->constrained()->nullOnDelete();
            // Job applications.
            $table->foreignId('vacancy_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            // Order requests: the delivery day the customer asked for.
            $table->date('delivery_date')->nullable()->index();
            // Type-specific answers (order lines, business details, brands, CV answers ...).
            $table->json('details')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('consented_at')->nullable();
            $table->string('source_url', 300)->nullable();
            $table->timestamp('first_contacted_at')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('lead_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // note | status | assign | credit | system
            $table->string('kind', 12)->default('note');
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('lead_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            // trade_licence | vat_certificate | id | pharmacy_licence | bank_reference | cv | letter | deck
            $table->string('kind', 20);
            // Always the private "local" disk; streamed to authorised staff only.
            $table->string('path');
            $table->string('original_name', 200);
            $table->string('mime', 100)->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_files');
        Schema::dropIfExists('lead_notes');
        Schema::dropIfExists('leads');
    }
};
