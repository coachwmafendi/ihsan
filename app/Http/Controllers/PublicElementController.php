<?php

namespace App\Http\Controllers;

use App\Enums\ElementType;
use App\Models\Element;
use Illuminate\Http\JsonResponse;

class PublicElementController extends Controller
{
    public function show(string $token): JsonResponse
    {
        $element = Element::query()
            ->where('token', $token)
            ->where('is_active', true)
            ->with('campaign')
            ->first();

        if (! $element) {
            return response()->json(['error' => 'Element not found'], 404)
                ->header('Access-Control-Allow-Origin', '*');
        }

        $campaignUrl = null;
        $campaignFormParameter = null;

        if ($element->campaign) {
            $campaignUrl = $element->campaign->checkout_modal_enabled
                ? url('/donate/campaign/'.$element->campaign->form_parameter)
                : url('/donate/'.$element->token);
            $campaignFormParameter = $element->campaign->checkout_modal_enabled
                ? $element->campaign->form_parameter
                : null;
        }

        $settings = $element->config ?? [];

        if ($element->type === ElementType::FloatingButton) {
            $settings = array_merge([
                'button_text' => 'Derma Sekarang',
                'action' => 'checkout_modal',
                'position' => 'bottom-right',
                'color' => 'campaign',
                'visibility' => 'desktop_mobile',
                'icon' => 'heart',
                'shape' => 'pill',
                'size' => 'medium',
            ], $settings);
        }

        if ($element->type === ElementType::Popup) {
            $settings = array_merge([
                'title' => 'Bantu Anak Tahfiz Hari Ini',
                'message' => null,
                'button_text' => 'Derma Sekarang',
                'action' => 'checkout_modal',
                'trigger' => 'after_delay',
                'delay' => 8,
                'frequency' => 'once_per_day',
                'visibility' => 'desktop_mobile',
                'layout' => 'simple',
                'image' => 'campaign',
                'color' => 'campaign',
            ], $settings);

            if ($element->campaign?->image_path) {
                $settings['image_url'] = url('/donate/campaign/'.$element->campaign->form_parameter.'/image');
            }
        }

        if ($element->type === ElementType::Button) {
            $settings = array_merge([
                'button_text' => 'Donate Now',
                'button_color' => 'bg-blue-600 hover:bg-blue-700',
                'button_size' => 'text-base px-6 py-3',
                'corner_radius' => 8,
                'show_amount_input' => true,
            ], $settings);
        }

        return response()->json([
            'id' => $element->getKey(),
            'type' => $element->type->value,
            'token' => $element->token,
            'is_active' => $element->is_active,
            'name' => $element->name,
            'campaign_name' => $element->campaign?->title,
            'campaign_url' => $campaignUrl,
            'campaign_form_parameter' => $campaignFormParameter,
            'settings' => $settings,
        ])->header('Access-Control-Allow-Origin', '*');
    }
}
