<?php

namespace App\Enums;

enum DonationType: string
{
    case OneTime = 'one_time';
    case Recurring = 'recurring';
}
