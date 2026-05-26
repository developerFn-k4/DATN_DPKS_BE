<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->enum('status', [
                'available',
                'cleaning',
                'maintenance',
                'inactive'
            ])->default('available')->change();

            if (!Schema::hasColumn('rooms', 'note')) {
                $table->text('note')->nullable()->after('status');
            }

            if (!Schema::hasColumn('rooms', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            if (Schema::hasColumn('rooms', 'note')) {
                $table->dropColumn('note');
            }
            if (Schema::hasColumn('rooms', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            $table->enum('status', [
                'available',
                'maintenance'
            ])->default('available')->change();
        });
    }
};
