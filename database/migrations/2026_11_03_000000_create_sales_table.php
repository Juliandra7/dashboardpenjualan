<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            // Catatan: order_id di dataset TIDAK unik (satu order bisa punya banyak baris),
            // jadi hanya diberi index biasa, bukan unique.
            $table->string('order_id')->index();
            $table->decimal('amount', 15, 2);
            $table->decimal('profit', 15, 2);
            $table->integer('quantity');
            $table->string('category')->index();
            $table->string('sub_category')->index();
            $table->string('payment_mode')->index();
            $table->date('order_date');
            $table->string('customer_name');
            $table->string('state')->index();
            $table->string('city')->index();
            $table->string('year_month', 7)->index(); // format YYYY-MM
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
