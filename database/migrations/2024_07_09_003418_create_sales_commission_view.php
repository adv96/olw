<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        Schema::create('materialized_sales_commission_view', function (Blueprint $table) {
            $table->string('company');
            $table->string('seller');
            $table->string('client');
            $table->string('city');
            $table->string('state');
            $table->timestamp('sold_at');
            $table->string('status');
            $table->decimal('total_amount', 15, 2);
            $table->decimal('commission', 15, 2);
        });

        // Drop the procedure if it already exists
        DB::unprepared('DROP PROCEDURE IF EXISTS refresh_sales_commission_view');

        // Create the procedure
        DB::unprepared('
            CREATE PROCEDURE refresh_sales_commission_view()
            BEGIN
                TRUNCATE TABLE materialized_sales_commission_view;
                INSERT INTO materialized_sales_commission_view (company, seller, client, city, state, sold_at, status, total_amount, commission)
                SELECT 
                    cp.name AS company,
                    us.name AS seller,
                    uc.name AS client,
                    ad.city,
                    ad.state,
                    s.sold_at,
                    s.status,
                    s.total_amount,
                    ROUND(s.total_amount * cp.commission_rate / 100) AS commission
                FROM sales AS s
                INNER JOIN sellers AS sl ON sl.id = s.seller_id
                INNER JOIN clients AS cl ON cl.id = s.client_id
                INNER JOIN companies AS cp ON cp.id = sl.company_id
                INNER JOIN addresses AS ad ON ad.id = cl.address_id
                INNER JOIN users AS us ON us.id = sl.user_id
                INNER JOIN users AS uc ON uc.id = cl.user_id;
            END
        ');

        // Drop the event if it already exists
        DB::unprepared('DROP EVENT IF EXISTS refresh_materialized_sales_commission_view');

        // Create the event
        DB::unprepared('
            CREATE EVENT refresh_materialized_sales_commission_view
            ON SCHEDULE EVERY 1 HOUR
            DO
                CALL refresh_sales_commission_view();
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared('DROP EVENT IF EXISTS refresh_materialized_sales_commission_view');
        DB::unprepared('DROP PROCEDURE IF EXISTS refresh_sales_commission_view');
        Schema::dropIfExists('materialized_sales_commission_view');
    }
};
