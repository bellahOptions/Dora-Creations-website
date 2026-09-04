<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * meta_description was a varchar(255), tight enough that a normal
     * paragraph-length SEO description (or one with a couple of multi-byte
     * characters) can overflow it and crash the save with a raw SQL error
     * instead of a form validation message. Widening to text removes the
     * DB-side limit entirely; the Filament form still guides admins toward
     * a sensible SEO length.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('meta_description')->nullable()->change();
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->text('meta_description')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('meta_description')->nullable()->change();
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->string('meta_description')->nullable()->change();
        });
    }
};
