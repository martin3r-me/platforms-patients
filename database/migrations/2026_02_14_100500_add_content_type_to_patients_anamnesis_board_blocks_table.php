<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients_anamnesis_board_blocks', function (Blueprint $table) {
            $table->string('content_type')->nullable()->after('span');
            $table->unsignedBigInteger('content_id')->nullable()->after('content_type');
            
            $table->index(['content_type', 'content_id']);
        });
    }

    public function down(): void
    {
        Schema::table('patients_anamnesis_board_blocks', function (Blueprint $table) {
            $table->dropIndex(['content_type', 'content_id']);
            $table->dropColumn(['content_type', 'content_id']);
        });
    }
};
