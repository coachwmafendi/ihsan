<?php

use App\Enums\ElementType;
use App\Models\Campaign;
use App\Models\Element;
use App\Models\Organization;

function numbered_words(int $count): string
{
    return collect(range(1, $count))
        ->map(fn (int $n) => 'word_' . str_pad((string) $n, 3, '0', STR_PAD_LEFT))
        ->implode(' ');
}

it('truncates checkout modal descriptions to eighty words with a read more link', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'description' => numbered_words(120),
        'checkout_modal_enabled' => true,
    ]);

    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
        'token' => 'form-read-more-001',
    ]);

    $response = $this->get(route('donations.show', $element));

    $response->assertOk();
    $response->assertSee('word_080');
    $response->assertSee('Read more', false);
});

it('does not show a read more link for short descriptions', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'description' => numbered_words(50),
        'checkout_modal_enabled' => true,
    ]);

    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
        'token' => 'form-short-001',
    ]);

    $response = $this->get(route('donations.show', $element));

    $response->assertOk();
    $response->assertDontSee('Read more', false);
});
