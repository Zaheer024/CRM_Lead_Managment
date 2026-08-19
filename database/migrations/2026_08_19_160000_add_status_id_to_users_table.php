<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Make user status lookup-driven: users.status_id stores the id of the
     * matching option in the "options" table under the USER_STATUS category.
     */
    public function up(): void
    {
        foreach (['ACTIVE', 'INACTIVE'] as $value) {
            if (! DB::table('options')->where('category', 'USER_STATUS')->where('value', $value)->exists()) {
                DB::table('options')->insert([
                    'category' => 'USER_STATUS',
                    'value' => $value,
                    'label' => ucwords(strtolower($value)),
                    'status' => 'ACTIVE',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'status_id')) {
                $table->unsignedBigInteger('status_id')->nullable()->after('phone');
            }
        });

        if (Schema::hasColumn('users', 'status')) {
            DB::table('users')->orderBy('id')->chunkById(500, function ($users) {
                foreach ($users as $user) {
                    $option = DB::table('options')
                        ->where('category', 'USER_STATUS')
                        ->where('value', $user->status)
                        ->first();

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['status_id' => $option?->id]);
                }
            });

            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['status']);
                $table->dropColumn('status');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('status_id')->references('id')->on('options')->restrictOnDelete();
            $table->index('status_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
            $table->dropIndex(['status_id']);

            if (! Schema::hasColumn('users', 'status')) {
                $table->string('status', 20)->default('ACTIVE')->index();
            }
        });

        DB::table('users')->orderBy('id')->chunkById(500, function ($users) {
            foreach ($users as $user) {
                $option = DB::table('options')->where('id', $user->status_id)->first();

                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['status' => $option?->value ?? 'ACTIVE']);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'status_id')) {
                $table->dropColumn('status_id');
            }
        });
    }
};
