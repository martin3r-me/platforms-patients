<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients_progress_cards', function (Blueprint $table) {
            $table->text('body_md')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('patients_progress_cards', function (Blueprint $table) {
            $table->dropColumn('body_md');
        });
    }
};
