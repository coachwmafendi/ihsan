<?php

namespace Database\Seeders;

use App\Enums\CampaignStatus;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\ElementType;
use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Element;
use App\Models\Organization;
use App\Models\PlatformFee;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $organization = Organization::factory()->create([
            'name' => 'Maahad Tahfiz Mumtazatut Taqwa',
            'slug' => 'maahad-tahfiz-mumtazatut-taqwa',
            'contact_email' => 'ngo@ihsan.test',
            'status' => 'active',
        ]);

        User::factory()->create([
            'name' => 'Ihsan Admin',
            'email' => 'admin@ihsan.test',
            'role' => UserRole::SuperAdmin,
        ]);

        User::factory()->for($organization)->create([
            'name' => 'Wan Mohd Afendi',
            'email' => 'ngo@ihsan.test',
            'role' => UserRole::NgoAdmin,
        ]);

        $generalFund = Campaign::factory()->for($organization)->create([
            'title' => 'Dana Operasi Maahad',
            'slug' => 'dana-operasi-maahad',
            'target_amount' => null,
            'has_target' => false,
            'collected_amount' => 4380.00,
        ]);

        $studentSponsorship = Campaign::factory()->for($organization)->create([
            'title' => 'Kempen Tajaan Pelajar',
            'slug' => 'tajaan-pelajar',
            'target_amount' => 50000.00,
            'has_target' => true,
            'collected_amount' => 8250.00,
        ]);

        $iftarCampaign = Campaign::factory()->for($organization)->create([
            'title' => 'Dana Iftar Ramadan',
            'slug' => 'dana-iftar-ramadan',
            'target_amount' => 15000.00,
            'has_target' => true,
            'collected_amount' => 6120.00,
            'status' => CampaignStatus::Paused,
        ]);

        $libraryCampaign = Campaign::factory()->for($organization)->create([
            'title' => 'Wakaf Buku Perpustakaan',
            'slug' => 'wakaf-buku-perpustakaan',
            'target_amount' => 8000.00,
            'has_target' => true,
            'collected_amount' => 2210.00,
            'status' => CampaignStatus::Draft,
        ]);

        $secondOrg = Organization::factory()->create([
            'name' => 'Pertubuhan Kebajikan Nur Hidayah',
            'slug' => 'pertubuhan-kebajikan-nur-hidayah',
            'contact_email' => 'nurhidayah@example.test',
            'status' => 'active',
        ]);

        $thirdOrg = Organization::factory()->create([
            'name' => 'Masjid Al-Islah Taman Bahagia',
            'slug' => 'masjid-al-islah-taman-bahagia',
            'contact_email' => 'masjid@example.test',
            'status' => 'active',
        ]);

        $fourthOrg = Organization::factory()->create([
            'name' => 'Rumah Anak Yatim Permata Ibu',
            'slug' => 'rumah-anak-yatim-permata-ibu',
            'contact_email' => 'permata@example.test',
            'status' => 'pending',
        ]);

        $fifthOrg = Organization::factory()->create([
            'name' => 'Yayasan Pendidikan Islam Terengganu',
            'slug' => 'yayasan-pendidikan-islam-terengganu',
            'contact_email' => 'ypit@example.test',
            'status' => 'pending',
        ]);

        User::factory()->for($secondOrg)->create([
            'name' => 'Aminah Yusof',
            'email' => 'aminah@example.test',
            'role' => UserRole::NgoAdmin,
        ]);

        User::factory()->for($thirdOrg)->create([
            'name' => 'Hassan Basri',
            'email' => 'hassan@example.test',
            'role' => UserRole::NgoAdmin,
        ]);

        $secondCampaign = Campaign::factory()->for($secondOrg)->create([
            'title' => 'Bantuan Anak Yatim',
            'slug' => 'bantuan-anak-yatim',
            'target_amount' => 20000.00,
            'has_target' => true,
            'collected_amount' => 4500.00,
        ]);

        $thirdCampaign = Campaign::factory()->for($thirdOrg)->create([
            'title' => 'Dana Pembangunan Masjid',
            'slug' => 'dana-pembangunan-masjid',
            'target_amount' => 100000.00,
            'has_target' => true,
            'collected_amount' => 12350.00,
        ]);

        $donors = Donor::factory(12)->sequence(
            ['name' => 'Hafizah Ahmad', 'email' => 'hafizah@example.test'],
            ['name' => 'Muhammad Amir', 'email' => 'amir@example.test'],
            ['name' => 'Nur Aisyah', 'email' => 'aisyah@example.test'],
            ['name' => 'Siti Khadijah', 'email' => 'khadijah@example.test'],
            ['name' => 'Ahmad Zikri', 'email' => 'zikri@example.test'],
            ['name' => 'Farah Nabilah', 'email' => 'farah@example.test'],
            ['name' => 'Mohd Azlan', 'email' => 'azlan@example.test'],
            ['name' => 'Nurul Iman', 'email' => 'iman@example.test'],
            ['name' => 'Rosli Ahmad', 'email' => 'rosli@example.test'],
            ['name' => 'Zaiton Ismail', 'email' => 'zaiton@example.test'],
            ['name' => 'Kamal Ariffin', 'email' => 'kamal@example.test'],
            ['name' => 'Salina Md Nor', 'email' => 'salina@example.test'],
        )->create();

        $donationRows = [
            [$generalFund, $donors[0], 50.00, DonationType::OneTime, DonationStatus::Succeeded, 3],
            [$studentSponsorship, $donors[1], 250.00, DonationType::OneTime, DonationStatus::Succeeded, 4],
            [$generalFund, $donors[2], 100.00, DonationType::OneTime, DonationStatus::Succeeded, 5],
            [$iftarCampaign, $donors[3], 500.00, DonationType::OneTime, DonationStatus::Succeeded, 8],
            [$studentSponsorship, $donors[4], 75.00, DonationType::Recurring, DonationStatus::Succeeded, 10],
            [$libraryCampaign, $donors[5], 40.00, DonationType::OneTime, DonationStatus::Succeeded, 12],
            [$generalFund, $donors[6], 150.00, DonationType::OneTime, DonationStatus::Succeeded, 15],
            [$iftarCampaign, $donors[7], 80.00, DonationType::OneTime, DonationStatus::Failed, 16],
            [$studentSponsorship, $donors[0], 30.00, DonationType::Recurring, DonationStatus::Succeeded, 20],
            [$generalFund, $donors[3], 120.00, DonationType::OneTime, DonationStatus::Refunded, 22],
            [$secondCampaign, $donors[8], 80.00, DonationType::OneTime, DonationStatus::Succeeded, 2],
            [$secondCampaign, $donors[9], 50.00, DonationType::OneTime, DonationStatus::Succeeded, 5],
            [$secondCampaign, $donors[10], 200.00, DonationType::Recurring, DonationStatus::Succeeded, 7],
            [$thirdCampaign, $donors[11], 150.00, DonationType::OneTime, DonationStatus::Succeeded, 1],
            [$thirdCampaign, $donors[8], 100.00, DonationType::Recurring, DonationStatus::Pending, 3],
            [$thirdCampaign, $donors[9], 500.00, DonationType::OneTime, DonationStatus::Succeeded, 10],
        ];

        foreach ($donationRows as [$campaign, $donor, $amount, $type, $status, $daysAgo]) {
            $donation = Donation::factory()->for($campaign)->for($donor)->create([
                'gross_amount' => $amount,
                'platform_fee' => round($amount * 0.03, 2),
                'net_amount' => round($amount * 0.935, 2),
                'stripe_fee' => round($amount * 0.022 + 1.30, 2),
                'status' => $status,
                'type' => $type,
                'created_at' => now()->subDays($daysAgo),
            ]);

            if ($status === DonationStatus::Succeeded) {
                PlatformFee::factory()->create([
                    'donation_id' => $donation->id,
                    'organization_id' => $campaign->organization_id,
                    'fee_amount' => round($amount * 0.03, 2),
                    'status' => 'transferred',
                ]);
            }
        }

        Subscription::factory()->for($generalFund)->for($donors[0])->create([
            'amount' => 30.00,
            'interval' => SubscriptionInterval::Monthly,
            'status' => SubscriptionStatus::Active,
        ]);

        Subscription::factory()->for($studentSponsorship)->for($donors[4])->create([
            'amount' => 75.00,
            'interval' => SubscriptionInterval::Monthly,
            'status' => SubscriptionStatus::Active,
        ]);

        Subscription::factory()->for($secondCampaign)->for($donors[10])->create([
            'amount' => 200.00,
            'interval' => SubscriptionInterval::Monthly,
            'status' => SubscriptionStatus::Active,
        ]);

        Subscription::factory()->for($thirdCampaign)->for($donors[8])->create([
            'amount' => 100.00,
            'interval' => SubscriptionInterval::Monthly,
            'status' => SubscriptionStatus::Incomplete,
        ]);

        Subscription::factory()->for($iftarCampaign)->for($donors[6])->create([
            'amount' => 20.00,
            'interval' => SubscriptionInterval::Weekly,
            'status' => SubscriptionStatus::PastDue,
            'retry_count' => 1,
        ]);

        Subscription::factory()->for($libraryCampaign)->for($donors[7])->create([
            'amount' => 100.00,
            'interval' => SubscriptionInterval::Yearly,
            'status' => SubscriptionStatus::Paused,
            'paused_until' => now()->addMonth(),
        ]);

        Element::factory()->for($organization)->for($generalFund)->create([
            'name' => 'Main Donation Form',
            'type' => ElementType::Form,
        ]);

        Element::factory()->for($organization)->for($studentSponsorship)->create([
            'name' => 'Sponsor A Student Button',
            'type' => ElementType::Button,
        ]);

        Element::factory()->for($organization)->for($iftarCampaign)->create([
            'name' => 'Ramadan Popup',
            'type' => ElementType::Popup,
        ]);

        Element::factory()->for($organization)->create([
            'name' => 'General Floating Button',
            'type' => ElementType::Button,
            'campaign_id' => null,
        ]);
    }
}
