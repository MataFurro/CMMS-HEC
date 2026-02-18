<?php

namespace Backend\Models;

enum AssetStatus: string
{
    case OPERATIVE = 'OPERATIVE';
    case MAINTENANCE = 'MAINTENANCE';
    case NO_OPERATIVE = 'NO_OPERATIVE';
    case OPERATIVE_WITH_OBS = 'OPERATIVE_WITH_OBS';

    public function getLabel(): string
    {
        return match ($this) {
            self::OPERATIVE => 'Operativo',
            self::MAINTENANCE => 'En Mantenimiento',
            self::NO_OPERATIVE => 'Fuera de Servicio',
            self::OPERATIVE_WITH_OBS => 'Operativo con Obs.',
        };
    }
}
