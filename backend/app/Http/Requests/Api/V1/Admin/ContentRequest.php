<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ContentRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('manage-content') === true; }

    public function rules(): array
    {
        $resource = (string) $this->route('resource');
        $creating = $this->isMethod('post');

        return [
            'title' => [$creating && in_array($resource, ['page-meta', 'services', 'properties', 'airbnb-listings'], true) ? 'required' : 'sometimes', 'string', 'max:255'],
            'name' => ['sometimes', 'string', 'max:255'],
            'location' => [$creating && $resource === 'properties' ? 'required' : 'sometimes', 'string', 'max:255'],
            'price_label' => [$creating && $resource === 'properties' ? 'required' : 'sometimes', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'hero_image' => ['nullable', 'string', 'max:2048'],
            'og_image' => ['nullable', 'string', 'max:2048'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'description' => ['sometimes', 'string'],
            'short_description' => [$creating && $resource === 'services' ? 'required' : 'sometimes', 'string'],
            'quote' => ['sometimes', 'string'],
            'text' => ['sometimes', 'string', 'max:255'],
            'author_name' => ['sometimes', 'string', 'max:255'],
            'author_role' => ['nullable', 'string', 'max:255'],
            'value' => ['sometimes', 'string', 'max:255'],
            'label' => ['sometimes', 'string', 'max:255'],
            'section' => ['sometimes', 'in:hero,airbnb'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
            'whatsapp_number' => ['nullable', 'string', 'max:80'],
            'whatsapp_url' => ['nullable', 'url', 'max:2048'],
            'footer_tagline' => ['nullable', 'string', 'max:1000'],
            'footer_copyright' => ['nullable', 'string', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:2048'],
            'facebook_url' => ['nullable', 'url', 'max:2048'],
            'linkedin_url' => ['nullable', 'url', 'max:2048'],
            'logo' => ['nullable', 'string', 'max:2048'],
            'site_name' => ['nullable', 'string', 'max:255'],
            'nav_cta_label' => ['nullable', 'string', 'max:100'],
            'nav_cta_url' => ['nullable', 'string', 'max:2048'],
            'home_hero_cta_label' => ['nullable', 'string', 'max:100'],
            'home_hero_cta_url' => ['nullable', 'string', 'max:2048'],
            'home_footer_cta_label' => ['nullable', 'string', 'max:100'],
            'home_footer_cta_url' => ['nullable', 'string', 'max:2048'],
            'whatsapp_label' => ['nullable', 'string', 'max:100'],
            'contact_heading' => ['nullable', 'string', 'max:100'],
            'map_url' => ['nullable', 'string', 'max:2048'],
            'contact_form_labels' => ['nullable', 'array'],
            'contact_form_labels.*' => ['nullable', 'string', 'max:255'],
            'home_background_image' => ['nullable', 'string', 'max:2048'],
            'home_content' => ['nullable', 'array'],
            'font_assets' => ['nullable', 'array'],
            'nav_items' => ['nullable', 'array'],
            'order' => ['sometimes', 'integer', 'min:0'],
            'published' => ['sometimes', 'boolean'],
            'is_published' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
            'price_value' => ['nullable', 'numeric', 'min:0'],
            'beds' => ['nullable', 'integer', 'min:0'],
            'baths' => ['nullable', 'integer', 'min:0'],
            'hero_eyebrow' => ['nullable', 'string', 'max:255'],
            'hero_headline' => ['nullable', 'string', 'max:255'],
            'hero_subheadline' => ['nullable', 'string', 'max:5000'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:255'],
            'media_ids' => ['sometimes', 'array'],
            'media_ids.*' => ['integer', 'distinct', 'exists:media,id'],
            'items' => ['sometimes', 'array'],
            'items.*.id' => ['sometimes', 'integer'],
            'items.*.text' => ['required_with:items', 'string', 'max:255'],
            'items.*.order' => ['sometimes', 'integer', 'min:0'],
            'blocks' => ['sometimes', 'array'],
            'blocks.*.id' => ['sometimes', 'integer'],
            'blocks.*.key' => ['required_with:blocks', 'string', 'max:255'],
            'blocks.*.type' => ['sometimes', 'string', 'max:80'],
            'blocks.*.content' => ['nullable', 'string'],
            'blocks.*.order' => ['sometimes', 'integer', 'min:0'],
            'blocks.*.published' => ['sometimes', 'boolean'],
            'occupancy_stats' => ['nullable', 'array'],
            'occupancy_stats.*' => ['array'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:255'],
        ];
    }
}