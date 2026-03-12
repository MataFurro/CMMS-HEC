<?php

namespace Backend\Models;

enum WorkOrderStatus: string
{
    case IN_PROGRESS = 'En Curso';
    case ON_HOLD = 'En Espera';
    case COMPLETED = 'Terminada';
    case CANCELED = 'Cancelada';
}
