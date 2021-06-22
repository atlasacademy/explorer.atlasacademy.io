<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBucketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('buckets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('server');
            $table->timestamps();
        });

        Schema::create('bucket_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bucket_id')->constrained('buckets');
            $table->string('key', 512);
            $table->string('parent', 512);
            $table->integer('size');
            $table->dateTime('modified_at')->nullable();
            $table->boolean('stale')->default(false);

            $table->unique(['bucket_id', 'key']);
            $table->index('parent');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bucket_files');
        Schema::dropIfExists('buckets');
    }
}
