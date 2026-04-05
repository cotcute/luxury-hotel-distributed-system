<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('node_logs', function (Blueprint $table) {
            $table->id();
            $table->string('node_port'); 
            $table->string('transaction_id')->nullable(); 
            $table->string('action'); 
            $table->text('details')->nullable(); 
            $table->string('status')->default('info'); 
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('node_logs');
    }
};
