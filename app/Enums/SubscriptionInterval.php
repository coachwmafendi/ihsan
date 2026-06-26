<?php

namespace App\Enums;

enum SubscriptionInterval: string
{
    case Weekly = 'weekly';
    case Biweekly = 'biweekly';
    case Monthly = 'monthly';
    case Bimonthly = 'bimonthly';
    case Quarterly = 'quarterly';
    case Semiannual = 'semiannual';
    case Yearly = 'yearly';
}
