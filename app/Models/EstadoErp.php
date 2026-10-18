<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['nivel'])]
class EstadoErp extends Model
{
    protected $table = 'estado_erp';

    public const NIVELES = ['basico' => 'Básico', 'integrado' => 'Integrado', 'automatizado' => 'Automatizado', 'optimizado' => 'Optimizado'];

    public const DESCRIPCIONES = [
        'basico' => 'Registro centralizado de operaciones empresariales.',
        'integrado' => 'CRM, SCM y ERP comparten clientes y recursos.',
        'automatizado' => 'Las órdenes procesadas actualizan inventario, movimientos y reposición.',
        'optimizado' => 'Nivel declarado tras revisar los procesos y sus oportunidades de mejora.',
    ];

    public static function actual(): self
    {
        return self::query()->firstOrCreate(['id' => 1], ['nivel' => 'basico']);
    }

    public function checklist(): array
    {
        return [
            'CRM integrado' => true, 'SCM integrado' => true, 'Órdenes empresariales' => true,
            'Movimientos automáticos' => true, 'Reportes ERP' => true,
            'Procesos optimizados (declarado)' => $this->nivel === 'optimizado',
        ];
    }
}
