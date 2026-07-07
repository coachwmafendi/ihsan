<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class CaseStudyController extends Controller
{
    public function show(string $slug): View
    {
        $studies = [
            'madrasah-darul-falah' => [
                'title' => 'Madrasah Darul Falah',
                'subtitle' => 'How a small Islamic school raised RM43,000 in 30 days with Ihsan',
                'hero_image' => null,
                'stats' => [
                    ['label' => 'Raised', 'value' => 'RM43,000'],
                    ['label' => 'Donors', 'value' => 312],
                    ['label' => 'Days', 'value' => 30],
                ],
                'content' => <<<'HTML'
<p>Madrasah Darul Falah needed to repair its roof and buy classroom supplies before the new academic year. With no full-time fundraising staff, they turned to Ihsan.</p>

<h3>The challenge</h3>
<p>The school had a loyal community but no easy way for parents and alumni to donate online. Cash collections were slow and hard to track.</p>

<h3>The approach</h3>
<p>They created a branded donation page, embedded a giving button on their website, and shared the campaign link via WhatsApp and Friday announcements.</p>

<h3>The result</h3>
<p>Within a month, 312 donors contributed more than RM43,000. Recurring donations now cover monthly utilities.</p>
HTML,
            ],
        ];

        if (! isset($studies[$slug])) {
            abort(404);
        }

        return view('case-studies.show', [
            'study' => $studies[$slug],
            'slug' => $slug,
        ]);
    }
}
