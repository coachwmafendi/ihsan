<?php

namespace App\Console\Commands;

use App\Services\Cloudflare\IpRanges;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:cloudflare-update-ips')]
#[Description('Fetch and cache the latest Cloudflare IP ranges')]
class CloudflareUpdateIps extends Command
{
    public function handle(IpRanges $ipRanges): int
    {
        $this->info('Fetching latest Cloudflare IP ranges...');

        $ranges = $ipRanges->refresh();

        $this->info(count($ranges).' IP ranges cached.');

        return self::SUCCESS;
    }
}
