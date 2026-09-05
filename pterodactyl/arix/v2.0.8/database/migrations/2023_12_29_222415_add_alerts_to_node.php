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
        Schema::table('nodes', function (Blueprint $table) {
            if (!Schema::hasColumn('nodes', 'alert')) {
                $table->string('alert')->nullable()->after('name');
            }
            if (!Schema::hasColumn('nodes', 'daemon_text')) {
                $table->string('daemon_text')->default('[Pterodactyl Daemon]:')->after('alert');
            }
            if (!Schema::hasColumn('nodes', 'container_text')) {
                $table->string('container_text')->default('container@pterodactyl~')->after('daemon_text');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            if (Schema::hasColumn('nodes', 'alert')) {
                $table->dropColumn('alert');
            }
            if (Schema::hasColumn('nodes', 'daemon_text')) {
                $table->dropColumn('daemon_text');
            }
            if (Schema::hasColumn('nodes', 'container_text')) {
                $table->dropColumn('container_text');
            }
        });
    }
};
