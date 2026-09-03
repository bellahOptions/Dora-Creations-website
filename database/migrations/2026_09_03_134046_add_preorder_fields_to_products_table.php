<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_preorder')->default(false)->after('is_featured');
            $table->date('preorder_release_date')->nullable()->after('is_preorder');
            $table->string('preorder_note')->nullable()->after('preorder_release_date');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_preorder', 'preorder_release_date', 'preorder_note']);
        });
    }
};
