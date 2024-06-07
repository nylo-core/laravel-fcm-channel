<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFcmDevicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fcm_devices', function (Blueprint $table) {
            $table->id();
            $table->text('uuid');
            $table->string('model')->nullable();
            $table->string('display_name')->nullable();
            $table->string('platform')->nullable();
            $table->string('version')->nullable();
            $table->integer('notifyable_id');
            $table->string('notifyable_type');
            $table->text('fcm_token')->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fcm_devices');
    }
}
