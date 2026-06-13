<?php

namespace App\Enums;

enum ElementType: string
{
    case Button = 'button';
    case Form = 'form';
    case Popup = 'popup';
    case FloatingButton = 'floating_button';
    case StickyButton = 'sticky_button';
    case QrCode = 'qr_code';
    case Link = 'link';
}
