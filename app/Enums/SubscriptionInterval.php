<?php

namespace App\Enums;

enum SubscriptionInterval: string
{
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';
}
