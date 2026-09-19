<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['nivel_scm'])]
class ConfiguracionScm extends Model
{
    protected $table = 'configuracion_scm';

    public const NIVEL_INICIAL = 'inicial';

    public const NIVEL_EN_DESARROLLO = 'en_desarrollo';

    public const NIVEL_OPTIMIZADO = 'optimizado';

    public const NIVELES = [self::NIVEL_INICIAL, self::NIVEL_EN_DESARROLLO, self::NIVEL_OPTIMIZADO];

    public static function actual(): self
    {
        return self::query()->firstOrCreate(['id' => 1], ['nivel_scm' => self::NIVEL_INICIAL]);
    }

    public function nivelLabel(): string
    {
        return match ($this->nivel_scm) {
            self::NIVEL_INICIAL => 'Inicial',
            self::NIVEL_EN_DESARROLLO => 'En desarrollo',
            self::NIVEL_OPTIMIZADO => 'Optimizado',
            default => 'Sin definir',
        };
    }
}
