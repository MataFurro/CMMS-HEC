<?php

namespace Backend\Models;

enum WorkOrderStatus: string
{
    case PENDING = 'Pendiente';
    case IN_PROGRESS = 'En Proceso';
    case COMPLETED = 'Terminada';
    case CANCELED = 'Cancelada';
}
