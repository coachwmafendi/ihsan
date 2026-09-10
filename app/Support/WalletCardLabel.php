<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

/**
 * What a wallet payment actually settled on.
 *
 * The Apple Pay and Google Pay marks in the lists carry no hover text, while
 * the plain card icon beside them names its brand. The wallet mark hides more,
 * not less: the card underneath is the thing that was charged, the thing that
 * gets declined, and the thing a supporter will name on the phone.
 */
class WalletCardLabel
{
    public static function for(?string $walletType, ?string $brand, ?string $last4): string
    {
        $wallet = PaymentMethodLabel::for($walletType, 'Wallet');
        $card = filled($brand) ? Str::headline((string) $brand) : null;

        if ($card === null) {
            return $wallet;
        }

        return $wallet.' · '.$card.(filled($last4) ? ' •••• '.$last4 : '');
    }
}
