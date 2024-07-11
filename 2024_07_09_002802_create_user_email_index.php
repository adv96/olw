<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add a virtual column for email if not deleted
        Schema::table('users', function (Blueprint $table) {
            $table->string('unique_email')->virtualAs("IF(deleted_at IS NULL, email, NULL)");
        });

        // Create a unique index on the virtual column
        Schema::table('users', function (Blueprint $table) {
            $table->unique('unique_email', 'user_email_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('user_email_index');
            $table->dropColumn('unique_email');
        });
    }
};

