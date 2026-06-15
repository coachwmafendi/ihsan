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

    public function label(): string
    {
        return match ($this) {
            self::Button => 'Button',
            self::Form => 'Form',
            self::Popup => 'Popup',
            self::FloatingButton => 'Floating Button',
            self::StickyButton => 'Sticky Button',
            self::QrCode => 'QR Code',
            self::Link => 'Link',
        };
    }
}
