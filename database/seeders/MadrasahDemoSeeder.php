<?php

namespace Database\Seeders;

use App\Enums\CampaignStatus;
use App\Enums\ElementType;
use App\Enums\OrganizationStatus;
use App\Models\Campaign;
use App\Models\Element;
use App\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MadrasahDemoSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::firstOrCreate(
            ['name' => 'Madrasah Saidatina Khadijah'],
            [
                'public_id' => null,
                'code' => 'MSK'.Str::random(5),
                'registration_type' => 'others',
                'description' => 'Demo madrasah untuk showcase Ihsan.',
                'contact_email' => 'demo@madrasahsk.test',
                'contact_phone' => '0123456789',
                'status' => OrganizationStatus::Active,
                'stripe_account_id' => 'acct_'.Str::random(24),
                'stripe_onboarded' => true,
                'settings' => [
                    'primary_color' => '#059669',
                    'suggested_amounts' => [30, 50, 100],
                ],
            ]
        );

        $campaign = Campaign::firstOrCreate(
            ['organization_id' => $org->id, 'title' => 'Tabung Pendidikan 2026'],
            [
                'public_id' => null,
                'description' => 'Sumbangan untuk yuran, buku, dan kelas tambahan pelajar.',
                'target_amount' => 100000.00,
                'collected_amount' => 0,
                'has_target' => true,
                'campaign_page_enabled' => true,
                'allow_recurring' => true,
                'end_date' => now()->addYear()->toDateString(),
                'status' => CampaignStatus::Active,
                'suggested_amounts' => [30, 50, 100],
            ]
        );

        $button = $org->elements()
            ->where('type', ElementType::Button->value)
            ->where('is_active', true)
            ->whereNull('archived_at')
            ->first();

        if (! $button) {
            $button = Element::create([
                'organization_id' => $org->id,
                'campaign_id' => $campaign->id,
                'public_id' => null,
                'name' => 'Tombol Derma Utama',
                'token' => Str::random(6),
                'type' => ElementType::Button,
                'config' => [
                    'button_text' => 'Derma Sekarang',
                    'button_color' => 'bg-emerald-600 hover:bg-emerald-700',
                    'button_size' => 'text-base px-6 py-3',
                    'corner_radius' => 999,
                    'button_icon' => 'heart',
                    'color' => 'emerald',
                    'action' => 'checkout_modal',
                    'alignment' => 'center',
                ],
                'is_active' => true,
            ]);
        }

        $floating = $org->elements()
            ->where('type', ElementType::FloatingButton->value)
            ->where('is_active', true)
            ->whereNull('archived_at')
            ->first();

        if (! $floating) {
            $floating = Element::create([
                'organization_id' => $org->id,
                'campaign_id' => $campaign->id,
                'public_id' => null,
                'name' => 'Tombol Melayang',
                'token' => Str::random(6),
                'type' => ElementType::FloatingButton,
                'config' => [
                    'button_text' => 'Sumbang',
                    'button_color' => 'bg-emerald-600 hover:bg-emerald-700',
                    'button_size' => 'text-base px-6 py-3',
                    'corner_radius' => 999,
                    'button_icon' => 'heart',
                    'color' => 'emerald',
                    'position' => 'bottom-right',
                    'action' => 'checkout_modal',
                ],
                'is_active' => true,
            ]);
        }

        $form = $org->elements()
            ->where('type', ElementType::Form->value)
            ->where('is_active', true)
            ->whereNull('archived_at')
            ->first();

        if (! $form) {
            $form = Element::create([
                'organization_id' => $org->id,
                'campaign_id' => $campaign->id,
                'public_id' => null,
                'name' => 'Borang Derma Demo',
                'token' => Str::random(6),
                'type' => ElementType::Form,
                'config' => [
                    'title' => 'Sumbangan untuk Madrasah Saidatina Khadijah',
                    'text_color' => '#212830',
                    'background_color' => '#FFFFFF',
                    'icon_color' => '#FF435A',
                    'border_size' => 2,
                    'border_radius' => 6,
                    'border_color' => '#DEDFF3',
                    'show_shadow' => false,
                    'submit_text' => 'Derma dan Sokong',
                    'suggested_amounts' => [30, 50, 100, 200],
                    'default_amount' => 50,
                    'default_frequency' => 'one_time',
                    'allow_monthly' => true,
                    'show_dedication' => true,
                    'show_comment' => true,
                    'button_text' => 'Derma Sekarang',
                    'button_color' => 'bg-emerald-600 hover:bg-emerald-700',
                    'button_size' => 'text-base px-6 py-3',
                    'corner_radius' => 8,
                    'show_name' => true,
                    'show_email' => true,
                    'show_phone' => true,
                    'show_message' => true,
                    'show_suggested' => true,
                    'show_amount_input' => true,
                    'success_action' => 'message',
                    'success_message' => 'Terima kasih atas sumbangan demo anda!',
                    'thank_you_title' => 'Terima Kasih!',
                    'thank_you_description' => '',
                ],
                'is_active' => true,
            ]);
        }

        $this->command->info('Madrasah Saidatina Khadijah demo seeded successfully.');
        $this->command->info('Demo page: '.route('demo.msk'));
        $this->command->info('Button token: '.$button->token);
        $this->command->info('Floating button token: '.$floating->token);
        $this->command->info('Form token: '.$form->token);
    }
}
