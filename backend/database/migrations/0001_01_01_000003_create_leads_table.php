<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $t) {
        $t->id();
        $t->string('name');
        $t->string('email');
        $t->string('phone')->nullable();
        $t->text('message');
        $t->string('source')->default('Website');
        $t->string('status')->default('new'); // new, qualified, quoted, won, lost
        $t->unsignedBigInteger('odoo_id')->nullable();
        $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};



