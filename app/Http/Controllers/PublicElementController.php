<?php

namespace App\Http\Controllers;

use App\Enums\ElementType;
use App\Models\Element;
use App\Support\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

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
                'image' => null,
                'color' => 'campaign',
            ], $settings);

            $imagePath = null;
            $rawImage = $settings['image'] ?? null;
            if (is_string($rawImage) && $rawImage !== '' && ! in_array($rawImage, ['campaign', 'none'], true)) {
                $imagePath = $rawImage;
            } elseif (is_array($rawImage)) {
                foreach ($rawImage as $item) {
                    if (is_string($item) && $item !== '' && ! in_array($item, ['campaign', 'none'], true)) {
                        $imagePath = $item;
                        break;
                    }
                    if (is_array($item) || is_object($item)) {
                        foreach ((array) $item as $v) {
                            if (is_string($v) && $v !== '' && ! in_array($v, ['campaign', 'none'], true) && str_starts_with($v, 'elements/')) {
                                $imagePath = $v;
                                break 2;
                            }
                        }
                    }
                }
            }
            if ($imagePath) {
                if (Storage::disk('public')->exists($imagePath)) {
                    $settings['image_url'] = request()->getSchemeAndHttpHost().'/storage/'.$imagePath;
                }
            }
            if (empty($settings['image_url']) && $element->campaign?->image_path) {
                $settings['image_url'] = url('/donate/campaign/'.$element->campaign->form_parameter.'/image');
            }
        }

        if ($element->type === ElementType::Button) {
            $settings = array_merge([
                'button_text' => 'Donate Now',
                'button_color' => 'bg-blue-600 hover:bg-blue-700',
                'button_size' => 'text-base px-6 py-3',
                'corner_radius' => 8,
                'action' => 'checkout_modal',
            ], $settings);
        }

        if ($element->type === ElementType::Form) {
            $settings = array_merge([
                'button_text' => 'Derma Sekarang',
                'action' => 'checkout_modal',
                'color' => 'campaign',
            ], $settings);
        }

        if ($element->type === ElementType::QrCode) {
            $settings = array_merge([
                'size' => 200,
                'label' => 'Scan to donate',
                'alignment' => 'center',
                'margin_top' => 0,
                'margin_bottom' => 0,
            ], $settings);
        }

        if ($element->type === ElementType::Link) {
            $settings = array_merge([
                'text' => 'Derma Sekarang',
                'url' => '',
                'style' => 'button',
                'color' => 'campaign',
                'size' => 'medium',
                'alignment' => 'left',
            ], $settings);
        }

        $response = [
            'id' => $element->getKey(),
            'type' => $element->type->value,
            'token' => $element->token,
            'is_active' => $element->is_active,
            'name' => $element->name,
            'campaign_name' => $element->campaign?->title,
            'campaign_url' => $campaignUrl,
            'campaign_form_parameter' => $campaignFormParameter,
            'settings' => $settings,
        ];

        if ($element->campaign) {
            $response['campaign_org_name'] = $element->campaign->organization?->name;
            $response['campaign_collected_amount'] = (float) $element->campaign->collected_amount;
            $response['campaign_target_amount'] = $element->campaign->has_target ? (float) $element->campaign->target_amount : 0;
            $response['campaign_has_target'] = $element->campaign->has_target;
            $response['campaign_currency_symbol'] = Currency::symbol($element->campaign->organization?->settings['default_currency'] ?? 'myr');

            if ($element->campaign->image_path) {
                $response['campaign_image_url'] = url('/donate/campaign/'.$element->campaign->form_parameter.'/image');
            }
        }

        return response()->json($response)
            ->header('Access-Control-Allow-Origin', '*');
    }
}
