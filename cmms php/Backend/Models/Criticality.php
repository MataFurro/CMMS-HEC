<?php

namespace Backend\Models;

enum Criticality: string
{
    case CRITICAL = 'CRITICAL';
    case RELEVANT = 'RELEVANT';
    case LOW = 'LOW';
}
