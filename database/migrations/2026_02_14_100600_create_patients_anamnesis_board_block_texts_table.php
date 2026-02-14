<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients_anamnesis_board_block_texts', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('content')->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            
            $table->timestamps();
            
            $table->index(['team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients_anamnesis_board_block_texts');
    }
};
