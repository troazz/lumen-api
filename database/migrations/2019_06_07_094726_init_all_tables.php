<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InitAllTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        Schema::create('checklists', function (Blueprint $table) {
            $table->increments('id');
            $table->string('object_id');
            $table->string('object_domain');
            $table->string('description');
            $table->string('task_id')->nullable();
            $table->datetime('due')->nullable();
            $table->unsignedTinyInteger('urgency')->nullable();
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedTinyInteger('is_completed')->default(0);
            $table->datetime('completed_at')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('restrict')->onUpdate('cascade');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('items', function (Blueprint $table) {
            $table->increments('id');
            $table->string('description');
            $table->string('task_id')->nullable();
            $table->datetime('due')->nullable();
            $table->unsignedTinyInteger('urgency')->nullable();
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('asignee_id')->nullable();
            $table->unsignedTinyInteger('is_completed')->default(0);
            $table->datetime('completed_at')->nullable();
            $table->unsignedInteger('checklist_id');
            $table->foreign('checklist_id')->references('id')->on('checklists')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('restrict')->onUpdate('cascade');
            $table->timestamps();
        });

        Schema::create('histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->morphs('loggable');
            $table->string('action');
            $table->unsignedInteger('uid');
            $table->string('value');
            $table->foreign('uid')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('histories');
        Schema::drop('items');
        Schema::drop('checklists');
        Schema::drop('users');
    }
}
