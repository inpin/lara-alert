<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaraalertAlertsTable extends Migration
{
    public function up()
    {
        Schema::create('laraalert_alerts', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('type')->default('alert');
            $table->morphs('alertable');
            $table->unsignedInteger('user_id');
            $table->timestamp('seen_at')->nullable();
            $table->text('description')->nullable();
        });

        Schema::table('laraalert_alerts', function (Blueprint $table) {
            $table->foreign('user_id', 'laraalert_alerts_ibfk_1')
                ->references('id')
                ->on('users')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
        });
    }

    public function down()
    {
        Schema::table('laraalert_alerts', function (Blueprint $table) {
            $table->dropForeign('laraalert_alerts_ibfk_1');
        });

        Schema::drop('laraalert_alerts');
    }
}
