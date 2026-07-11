<?php

namespace App\Http\Controllers;

use App\Enums\ElementType;
use App\Models\Organization;
use Illuminate\View\View;

class DemoLandingController extends Controller
{
    public function __invoke(): View
    {
        $config = [
            'org' => [
                'name' => 'Madrasah Saidatina Khadijah',
                'tagline' => 'Membentuk generasi celik al-Quran, berilmu, dan berakhlak mulia.',
                'primary_color' => 'emerald',
                'accent_color' => 'amber',
            ],
            'nav' => [
                ['label' => 'Program', 'href' => '#programs'],
                ['label' => 'Impak', 'href' => '#impact'],
                ['label' => 'Derma', 'href' => '#donate'],
            ],
            'stats' => [
                ['value' => '1,240', 'label' => 'Pelajar'],
                ['value' => 'RM 85,000', 'label' => 'Sumbangan Diterima'],
                ['value' => '98%', 'label' => 'Kepuasan Ibu Bapa'],
            ],
            'programs' => [
                [
                    'title' => 'Tahfiz Al-Quran',
                    'description' => 'Program hafazan intensif dibimbing guru berpengalaman.',
                    'image' => 'https://placehold.co/800x500/1e293b/34d399?text=Tahfiz+Al-Quran',
                ],
                [
                    'title' => 'Pendidikan Akademik',
                    'description' => 'Kurikulum KSSR & KSSM yang komprehensif dan berkualiti.',
                    'image' => 'https://placehold.co/800x500/1e293b/38bdf8?text=Pendidikan+Akademik',
                ],
                [
                    'title' => 'Bantuan Pelajar',
                    'description' => 'Biasiswa dan bantuan yuran untuk pelajar kurang berkemampuan.',
                    'image' => 'https://placehold.co/800x500/1e293b/f472b6?text=Bantuan+Pelajar',
                ],
            ],
            'impact' => [
                'title' => 'Bina Masa Depan Bersama',
                'body' => 'Setiap sumbangan membantu kami menyediakan kelas tambahan, buku latihan, dan biasiswa kepada pelajar yang memerlukan. Sertai kami dalam membentuk generasi yang cemerlang.',
                'image' => 'https://placehold.co/1200x900/0f172a/f59e0b?text=Impak+Program',
            ],
            'monthly' => [
                'title' => 'Jadi Penderma Tetap',
                'body' => 'Sumbangan bulanan membolehkan kami merancang program jangka panjang dan memberi manfaat berterusan kepada pelajar.',
                'image' => 'https://placehold.co/1200x600/1e293b/a855f7?text=Monthly+Giving',
                'amounts' => ['RM 30', 'RM 50', 'RM 100'],
            ],
            'images' => [
                'hero' => 'https://placehold.co/1600x900/0f172a/10b981?text=Madrasah+Saidatina+Khadijah',
            ],
            'widget' => [
                'token' => 'msk-demo',
                'base_url' => url('/'),
                'button_text' => 'Derma Sekarang',
                'floating_text' => 'Sumbang',
                'color' => 'emerald',
                'position' => 'bottom-right',
            ],
            'footer' => [
                'address' => "Madrasah Saidatina Khadijah\nJalan Pendidikan 1\nTaman Ilmu, 43000 Kajang, Selangor",
                'socials' => [
                    ['label' => 'Facebook', 'href' => '#'],
                    ['label' => 'Instagram', 'href' => '#'],
                    ['label' => 'YouTube', 'href' => '#'],
                ],
            ],
        ];

        $tokens = $this->resolveElementTokens($config['org']['name']);

        return view('demo.msk', compact('config', 'tokens'));
    }

    /**
     * @return array<string, string|null>
     */
    private function resolveElementTokens(string $orgName): array
    {
        $fallback = [
            'button' => 'msk-demo',
            'floating_button' => 'msk-demo-float',
            'form' => null,
        ];

        $org = Organization::where('name', $orgName)->first();

        if (! $org) {
            return $fallback;
        }

        $elements = $org->elements()
            ->where('is_active', true)
            ->whereNull('archived_at')
            ->whereIn('type', [
                ElementType::Button->value,
                ElementType::FloatingButton->value,
                ElementType::Form->value,
            ])
            ->get()
            ->keyBy(fn ($element) => $element->type->value);

        return [
            'button' => $elements[ElementType::Button->value]?->token ?? $fallback['button'],
            'floating_button' => $elements[ElementType::FloatingButton->value]?->token ?? $fallback['floating_button'],
            'form' => $elements[ElementType::Form->value]?->token ?? $fallback['form'],
        ];
    }
}
