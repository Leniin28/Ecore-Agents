<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('slug')->unique();
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Migrar categorías de texto existentes hacia la tabla normalizada.
        if (Schema::hasTable('productos')) {
            $nombres = DB::table('productos')
                ->whereNotNull('categoria')
                ->where('categoria', '!=', '')
                ->distinct()
                ->pluck('categoria');

            foreach ($nombres as $nombre) {
                $slug = Str::slug($nombre);
                if (empty($slug)) {
                    $slug = 'cat-'.Str::random(6);
                }
                DB::table('categorias')->insertOrIgnore([
                    'nombre' => $nombre,
                    'slug' => $slug,
                    'descripcion' => "Categoría {$nombre}",
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::table('productos', function (Blueprint $table) {
            // Los recursos existentes permanecen internos hasta publicarlos expresamente.
            $table->boolean('visible_tienda')->default(false);
            $table->boolean('destacado')->default(false);
            $table->string('imagen_url')->nullable();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();
        });

        // Sincronizar categoria_id en productos existentes
        if (Schema::hasTable('productos')) {
            $categorias = DB::table('categorias')->get()->keyBy('nombre');
            foreach ($categorias as $nombre => $cat) {
                DB::table('productos')->where('categoria', $nombre)->update(['categoria_id' => $cat->id]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('categoria_id');
            $table->dropColumn(['visible_tienda', 'destacado', 'imagen_url']);
        });

        Schema::dropIfExists('categorias');
    }
};
