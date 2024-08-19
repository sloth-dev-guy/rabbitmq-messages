<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function __construct()
    {
        $this->connection = config('rabbitmq-messages.database_connection');
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection($this->getConnection())->create('dispatch_message', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('name')->index();
            $table->enum('status', ['created', 'dispatched', 'failed'])->index();
            $table->json('properties')->nullable();
            $table->json('payload');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::connection($this->getConnection())->create('listen_message', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('name')->index();
            $table->enum('status', ['queued', 'processed', 'failed'])->index();
            $table->json('properties')->nullable();
            $table->json('payload');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**$this->get
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists('dispatch_message');
        Schema::connection($this->getConnection())->dropIfExists('listen_message');
    }
};
