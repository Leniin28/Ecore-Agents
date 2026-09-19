<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('usuario')->change();
            $table->json('permissions')->nullable()->after('role');
        });

        DB::table('users')
            ->where('role', 'usuario')
            ->whereExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('clientes')
                ->whereColumn('clientes.user_id', 'users.id'))
            ->update(['role' => 'cliente', 'permissions' => null]);

        DB::table('users')
            ->where('role', 'usuario')
            ->whereNull('permissions')
            ->update(['permissions' => json_encode(['crm'])]);
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'cliente')->update(['role' => 'usuario']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('permissions');
            $table->enum('role', ['admin', 'usuario'])->default('usuario')->change();
        });
    }
};
