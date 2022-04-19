<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use VeskoDigital\LaravelFCM\Models\FcmUserDevice;

class CreateFcmDeviceApiRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fcm_device_api_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(FcmUserDevice::class)->constrained();
            $table->text('path')->nullable();
            $table->string('ip')->nullable();
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
        Schema::dropIfExists('api_app_requests');
    }
}
