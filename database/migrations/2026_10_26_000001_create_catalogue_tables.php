<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The trade catalogue. No prices and no stock levels: the trade desk quotes both.
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 140)->unique();
            $table->string('name', 140);
            $table->string('brand', 80)->index();
            // A key of peterco.divisions (never the pharmaceutical range, which is not listed publicly).
            $table->string('division', 30)->index();
            $table->string('category', 60)->nullable();
            $table->string('pack_size', 80)->nullable();
            $table->string('sku', 40)->nullable();
            $table->text('description')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('thumb_path')->nullable();
            $table->boolean('published')->default(true);
            $table->boolean('featured')->default(false);
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        // "vacancies", not "jobs": Laravel's queue owns the jobs table.
        Schema::create('vacancies', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 140)->unique();
            $table->string('title', 140);
            $table->string('department', 80);
            $table->string('location', 80)->default('Cul de Sac, Castries');
            $table->string('employment_type', 30)->default('Full time');
            $table->text('summary');
            $table->text('duties')->nullable();
            $table->text('requirements')->nullable();
            $table->date('closes_on')->nullable();
            $table->boolean('published')->default(true);
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        // Approved trade customers. Opened by credit control when an application is approved.
        Schema::create('trade_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->string('business_name', 160);
            $table->string('trade_type', 20);
            $table->string('contact_name', 120);
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->string('district', 30)->nullable();
            $table->string('terms', 10)->default('cod');
            $table->string('status', 10)->default('active')->index();
            $table->string('pharmacy_licence', 60)->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_accounts');
        Schema::dropIfExists('vacancies');
        Schema::dropIfExists('products');
    }
};
