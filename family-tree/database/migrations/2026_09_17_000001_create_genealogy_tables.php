<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('family_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            $table->unique(['family_id', 'user_id']);
        });

        Schema::create('places', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('country')->nullable();
            $table->string('region')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });

        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('maiden_name')->nullable();
            $table->string('gender')->default('unknown');
            $table->date('birth_date')->nullable();
            $table->string('birth_date_precision')->default('unknown');
            $table->foreignId('birth_place_id')->nullable()->constrained('places')->nullOnDelete();
            $table->date('death_date')->nullable();
            $table->string('death_date_precision')->nullable();
            $table->foreignId('death_place_id')->nullable()->constrained('places')->nullOnDelete();
            $table->boolean('is_living')->default(true);
            $table->string('profile_photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            $table->index('family_id');
        });

        Schema::create('relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_one_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('person_two_id')->constrained('people')->cascadeOnDelete();
            $table->string('relationship_type');
            $table->boolean('is_directional')->default(true);
            $table->date('start_date')->nullable();
            $table->string('start_date_precision')->nullable();
            $table->string('start_date_text')->nullable();
            $table->date('start_date_range_end')->nullable();
            $table->date('end_date')->nullable();
            $table->string('end_date_precision')->nullable();
            $table->string('end_date_text')->nullable();
            $table->date('end_date_range_end')->nullable();
            $table->string('confidence_level')->default('confirmed');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['person_one_id', 'person_two_id', 'relationship_type']);
            $table->index(['person_one_id', 'relationship_type']);
            $table->index(['person_two_id', 'relationship_type']);
            $table->index('family_id');
        });

        Schema::create('life_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->string('event_type');
            $table->date('event_date')->nullable();
            $table->string('event_date_precision')->default('unknown');
            $table->string('event_date_text')->nullable();
            $table->date('event_date_range_end')->nullable();
            $table->foreignId('place_id')->nullable()->constrained('places')->nullOnDelete();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->index('family_id');
        });

        Schema::create('life_event_people', function (Blueprint $table) {
            $table->id();
            $table->foreignId('life_event_id')->constrained('life_events')->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->string('role')->default('subject');
            $table->timestamp('created_at')->nullable();
            $table->unique(['life_event_id', 'person_id', 'role']);
        });

        Schema::create('media_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_type')->default('photo');
            $table->string('original_name')->nullable();
            $table->string('caption')->nullable();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
            $table->index('family_id');
        });

        Schema::create('media_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_item_id')->constrained('media_items')->cascadeOnDelete();
            $table->string('linkable_type');
            $table->unsignedBigInteger('linkable_id');
            $table->timestamp('created_at')->nullable();
            $table->unique(['media_item_id', 'linkable_type', 'linkable_id']);
            $table->index(['linkable_type', 'linkable_id']);
        });

        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('type')->default('other');
            $table->text('citation_text')->nullable();
            $table->string('url')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->index('family_id');
        });

        Schema::create('citations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained('sources')->cascadeOnDelete();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->string('citable_type');
            $table->unsignedBigInteger('citable_id');
            $table->string('field_name')->nullable();
            $table->string('confidence_level')->default('confirmed');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->index(['citable_type', 'citable_id', 'field_name']);
            $table->index('family_id');
        });

        Schema::create('family_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('role');
            $table->string('token')->unique();
            $table->foreignId('invited_by')->constrained('users');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index('family_id');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('action');
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->json('changes')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index('family_id');
            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('family_invitations');
        Schema::dropIfExists('citations');
        Schema::dropIfExists('sources');
        Schema::dropIfExists('media_links');
        Schema::dropIfExists('media_items');
        Schema::dropIfExists('life_event_people');
        Schema::dropIfExists('life_events');
        Schema::dropIfExists('relationships');
        Schema::dropIfExists('people');
        Schema::dropIfExists('places');
        Schema::dropIfExists('family_user');
        Schema::dropIfExists('families');
    }
};
