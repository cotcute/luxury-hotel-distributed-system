<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('node_logs', function (Blueprint $table) {
            $table->id();
            $table->string('node_port'); // Node identifier
            $table->string('transaction_id')->nullable(); 
            $table->string('action'); // PHA 1, PHA 2, v.v.
            $table->text('details')->nullable(); 
            $table->string('status')->default('info'); // info, success, warning, error
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('node_logs');
    }
};
