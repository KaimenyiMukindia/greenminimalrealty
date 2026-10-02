<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\PageMeta;
use App\Models\Property;
use App\Models\PropertyTag;
use App\Models\Service;
use App\Models\ServiceItem;
use App\Models\SiteSetting;
use App\Models\Stat;
use App\Models\SustainabilityPillar;
use App\Models\Testimonial;
use App\Models\Value;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $media = $this->copyImages();

        $settings = SiteSetting::updateOrCreate([], [
            'site_name' => 'Green Minimal Realty',
            'logo' => $media['logo-BYadtnuV.png'] ?? null,
            'address' => 'Serena Road, Shanzu, Mombasa, Kenya',
            'phone' => '+254 714 171 102',
            'email' => 'greenminimalorg@gmail.com',
            'whatsapp_number' => '254714171102',
            'whatsapp_url' => 'https://wa.me/254714171102',
            'whatsapp_label' => 'WhatsApp',
            'nav_cta_label' => 'Book Consultation',
            'nav_cta_url' => '/contact',
            'home_hero_cta_label' => 'View Properties',
            'home_hero_cta_url' => '/properties',
            'home_footer_cta_label' => 'Book a Consultation',
            'home_footer_cta_url' => '/contact',
            'contact_heading' => 'Reach us',
            'map_url' => 'https://www.google.com/maps?q=Serena%20Road%20Shanzu%20Mombasa&output=embed',
            'contact_form_labels' => ['success' => 'Message sent.', 'name' => 'Full name', 'email' => 'Email', 'phone' => 'Phone', 'interest' => 'I am interested in', 'message' => 'Tell us a little more', 'submit' => 'Send message'],
            'footer_tagline' => 'A Kenyan real estate company connecting people with exceptional properties while promoting sustainable living, smart investments and stress-free property management.',
            'footer_copyright' => '© 2026 Green Minimal Realty. All rights reserved.',
            'home_background_image' => $media['leaves-DnSaEzhY.jpg'] ?? null,
            'font_assets' => $this->fontAssets($media),
            'home_content' => [
                'new_listing' => ['eyebrow' => 'New listing', 'title' => 'Watamu 4-Bedroom Villa', 'summary' => 'KSh 45M · 4 ensuite bedrooms · Pool · Sold fully furnished', 'image' => $media['watamu-0876.jpg'] ?? null],
                'about' => ['eyebrow' => 'About Us', 'headline' => 'A quieter way to do real estate.', 'body' => 'Green Minimal Realty is a customer-focused Kenyan real estate company combining property expertise, sustainability knowledge and personalised service. We believe great spaces should improve quality of life — and minimise their impact on the planet.'],
                'sustainability' => ['eyebrow' => 'Sustainability', 'headline' => 'Properties designed to tread lightly.', 'body' => 'We champion buildings that are healthier to live in, cheaper to run and more valuable over time — from EDGE-certified developments to low-impact retrofits.'],
                'airbnb' => ['eyebrow' => 'Airbnb Management', 'headline' => 'Your property, fully looked after.', 'body' => 'We run your short-let like a small hotel — beautifully presented, dynamically priced and meticulously maintained.'],
                'why' => ['eyebrow' => 'Why Choose Us', 'headline' => 'Quietly excellent. Reliably yours.', 'items' => [['title' => 'Sustainability Expertise', 'description' => 'Deep knowledge of EDGE, IFC and green building economics.'], ['title' => 'Local Market Knowledge', 'description' => 'On-the-ground intelligence across Nairobi and the coast.'], ['title' => 'Personalized Service', 'description' => 'One dedicated lead from first call to final handover.'], ['title' => 'Professional Management', 'description' => 'Hotel-grade operations for short-lets and long-term rentals.'], ['title' => 'Trusted Guidance', 'description' => 'Independent advice — we are paid for outcomes, not pressure.'], ['title' => 'End-to-End Solutions', 'description' => 'Sourcing, transacting, certifying, managing, optimising.']]],
                'cta' => ['eyebrow' => 'Let’s talk', 'headline' => 'Ready to find your better space?', 'body' => 'Book a complimentary 30-minute consultation. We’ll listen first, then map out the path that fits your goals.'],
            ],
            'nav_items' => [['label' => 'Home', 'to' => '/'], ['label' => 'About', 'to' => '/about'], ['label' => 'Services', 'to' => '/services'], ['label' => 'Properties', 'to' => '/properties'], ['label' => 'Sustainability', 'to' => '/sustainability'], ['label' => 'Airbnb Management', 'to' => '/airbnb-management'], ['label' => 'Contact', 'to' => '/contact']],
        ]);

        if (! Menu::query()->where('location', 'primary')->exists()) {
            $primaryMenu = Menu::create(['name' => 'Primary Navigation', 'location' => 'primary', 'order' => 0, 'published' => true]);
            foreach ((array) $settings->nav_items as $order => $item) {
                if (! is_array($item) || ! isset($item['label']) || ! (isset($item['to']) || isset($item['url']))) continue;
                MenuItem::create(['menu_id' => $primaryMenu->id, 'label' => (string) $item['label'], 'url' => (string) ($item['to'] ?? $item['url']), 'order' => $order, 'published' => true]);
            }
        }

        $meta = [
            '/' => ['Green Minimal Realty — Sustainable Real Estate in Kenya', 'Find sustainable homes, investment properties and stress-free Airbnb management in Kenya with Green Minimal Realty.', 'Green Minimal Realty · Kenya', 'Find spaces that feel good and live better.', 'We connect people with exceptional properties while promoting sustainable living, smart investments and stress-free property management.'],
            '/about' => ['About — Green Minimal Realty', "Meet the team behind Kenya's sustainable real estate practice.", 'About Us', 'Real estate, rooted in care.', 'We blend property expertise with deep sustainability knowledge to help our clients live, host and invest with confidence.'],
            '/services' => ['Services — Green Minimal Realty', 'Property agency, sustainability consulting and Airbnb management in Kenya.', 'Services', 'Three practices. One quiet standard.', "Whether you're buying, building, certifying or hosting, we work end-to-end so nothing falls between the cracks."],
            '/properties' => ['Properties — Green Minimal Realty', 'Explore sustainable homes and investment properties in Kenya.', 'Properties', 'Homes worth coming home to.', 'Thoughtfully selected spaces for living, hosting and investing.'],
            '/sustainability' => ['Sustainability — Green Minimal Realty', 'Energy, water and healthy-space strategies that lower costs and raise value.', 'Sustainability', 'Tread lightly. Live better. Earn more.', "Sustainable property isn't a trade-off. Done well, it lowers operating costs, improves health, and protects long-term value."],
            '/airbnb-management' => ['Airbnb Management — Green Minimal Realty', 'Full-service short-let management in Kenya — pricing, guests, housekeeping, optimisation.', 'Airbnb Management', 'Your short-let, beautifully run.', 'From listing to laundry, we manage every detail — so your property earns more and you do less.'],
            '/contact' => ['Contact — Green Minimal Realty', 'Book a consultation or send us a message. We respond within one business day.', 'Contact', 'Let’s start a quiet conversation.', 'Tell us about your property goals. We’ll come back to you within one business day.'],
        ];
        foreach ($meta as $route => [$title, $description, $eyebrow, $headline, $subheadline]) {
            PageMeta::updateOrCreate(['route' => $route], ['title' => $title, 'description' => $description, 'hero_eyebrow' => $eyebrow, 'hero_headline' => $headline, 'hero_subheadline' => $subheadline, 'hero_image' => $route === '/' ? ($media['hero-CXYyLE1-.jpg'] ?? null) : null]);
        }

        foreach ([['180+', 'Properties placed'], ['86%', 'Avg. Airbnb occupancy'], ['32%', 'Lower utility costs'], ['12', 'Counties served']] as $order => [$value, $label]) {
            Stat::updateOrCreate(['section' => 'hero', 'order' => $order], compact('value', 'label'));
        }
        foreach ([['86%', 'Avg. occupancy'], ['4.9★', 'Guest rating'], ['+38%', 'Revenue uplift']] as $order => [$value, $label]) {
            Stat::updateOrCreate(['section' => 'airbnb', 'order' => $order], compact('value', 'label'));
        }

        foreach ([
            ['sustainability', 'Sustainability', 'Every recommendation is filtered through long-term environmental value.'],
            ['eye', 'Transparency', 'Clear pricing, honest advice, and full visibility on every transaction.'],
            ['building', 'Professionalism', 'Calm, considered execution from sourcing through to handover.'],
            ['heart', 'Client-centered', 'Your goals lead. We adapt our process to fit your life and timeline.'],
        ] as $order => [$icon, $title, $description]) {
            Value::updateOrCreate(['title' => $title], compact('icon', 'title', 'description') + ['order' => $order, 'published' => true]);
        }

        $services = [
            ['property-agency', 'Property Agency', 'End-to-end property sales, rentals, sourcing, investment advisory and high-impact marketing.', ['Property sales', 'Property rentals', 'Property sourcing', 'Investment advisory', 'Property marketing']],
            ['sustainability-consulting', 'Sustainability Consulting', 'Green building advisory, EDGE guidance and strategies that lower operating costs and raise value.', ['Green building advisory', 'Sustainability assessments', 'EDGE project guidance', 'Energy & water efficiency', 'Sustainable property strategies']],
            ['airbnb-management', 'Airbnb Management', 'Full-service short-let management — from listings and pricing to housekeeping and reviews.', ['Listing creation', 'Guest communication', 'Dynamic pricing', 'Housekeeping coordination', 'Check-in / check-out', 'Marketing & occupancy']],
        ];
        foreach ($services as $order => [$slug, $title, $description, $items]) {
            $service = Service::updateOrCreate(['slug' => $slug], ['title' => $title, 'short_description' => $description, 'order' => $order, 'published' => true]);
            foreach ($items as $itemOrder => $text) ServiceItem::updateOrCreate(['service_id' => $service->id, 'order' => $itemOrder], ['text' => $text]);
        }

        foreach ([['sun', 'Energy efficiency', 'Passive design, solar PV and efficient systems that cut bills.'], ['water', 'Water conservation', 'Rainwater harvesting, greywater reuse and low-flow fixtures.'], ['wind', 'Healthy indoor spaces', 'Cross-ventilation, daylight and low-VOC, healthier materials.'], ['coins', 'Reduced operating costs', 'Typical 25–40% savings on combined utility costs.'], ['badge', 'Green certifications', 'EDGE, LEED and IFC-aligned advisory and documentation.'], ['tree', 'Long-term value', 'Future-proof buildings that resist climate and regulatory shifts.']] as $order => [$icon, $title, $description]) {
            SustainabilityPillar::updateOrCreate(['title' => $title], compact('icon', 'title', 'description') + ['order' => $order, 'published' => true]);
        }

        foreach ([['Links Gardens', 'links-gardens', 'Nyali, Mombasa', 'From KSh 22,000,000', 3, 3, 'links-lg0006.jpg', ['Rooftop garden', 'Swimming pool', 'Fitness center']], ["Junior's Palace", 'juniors-palace', 'Nyali, Mombasa', 'From KSh 8,500,000', 2, 2, 'juniors-23-39-47_1.jpg', ['Swimming pool', 'Solar hot water', '5-min walk to beach']], ['Georgia Luxury Apartment', 'georgia-luxury-apartment', 'Shanzu Go-Kart Area, Mombasa', 'KSh 16,000,000', 3, 3, 'georgia-dsc0803.jpg', ['Rooftop pool', 'Sea view', '10-min walk to beach']], ['Go-Kart Heights', 'go-kart-heights', 'Shanzu, Mombasa', 'KSh 95,000,000', 4, 4, 'property-6-u5zdNFs8.jpg', ['Oceanfront', 'Smart home', 'Rooftop lounge']]] as $order => [$title, $slug, $location, $price, $beds, $baths, $image, $tags]) {
            $property = Property::updateOrCreate(['slug' => $slug], ['title' => $title, 'location' => $location, 'price_label' => $price, 'beds' => $beds, 'baths' => $baths, 'hero_image' => $media[$image] ?? null, 'is_featured' => true, 'is_published' => true, 'order' => $order + 2]);
            foreach ($tags as $tagOrder => $label) PropertyTag::updateOrCreate(['property_id' => $property->id, 'order' => $tagOrder], compact('label'));
        }

        foreach ([['Watamu 3-Bedroom Villa + DSQ', 'watamu-3-bedroom-villa-dsq', 'Near Turtle Bay, Watamu', 'KSh 42,000,000', 3, 3, 'watamu-3bed-exterior.jpg', ['Swimming pool', 'Walk-in closets', 'Servant quarters']], ['Watamu 4-Bedroom Villa', 'watamu-4-bedroom-villa', 'Watamu, Kilifi County', 'KSh 45,000,000', 4, 4, 'watamu-0876.jpg', ['Swimming pool', 'Fully furnished', 'Half-acre grounds']]] as $order => [$title, $slug, $location, $price, $beds, $baths, $image, $tags]) {
            $property = Property::updateOrCreate(['slug' => $slug], ['title' => $title, 'location' => $location, 'price_label' => $price, 'beds' => $beds, 'baths' => $baths, 'hero_image' => $media[$image] ?? null, 'is_featured' => true, 'is_published' => true, 'order' => $order]);
            foreach ($tags as $tagOrder => $label) PropertyTag::updateOrCreate(['property_id' => $property->id, 'order' => $tagOrder], compact('label'));
        }

        foreach ([['Green Minimal Realty helped us find a coastal home that feels like a retreat. Their sustainability lens saved us on energy from day one.', 'Wanjiru & James M.', 'Homeowners, Diani'], ['Our short-let occupancy jumped from 48% to 86% in three months. They are calm, professional and obsessive about guest experience.', 'Aisha K.', 'Airbnb Host, Kilimani'], ['The EDGE guidance on our 40-unit project unlocked green financing and lowered projected utility costs by a third.', 'Daniel O.', 'Developer, Nairobi']] as $order => [$quote, $author_name, $author_role]) {
            Testimonial::updateOrCreate(['author_name' => $author_name], compact('quote', 'author_name', 'author_role') + ['order' => $order, 'published' => true]);
        }
    }

    private function copyImages(): array
    {
        $source = base_path('../GreenMinimal');
        $urls = [];
        foreach (File::allFiles($source) as $file) {
            if (! in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp', 'ttf', 'woff', 'woff2'], true)) continue;
                $name = $file->getFilename();
                $path = 'uploads/' . $name;
                Storage::disk('public')->put($path, File::get($file->getPathname()));
                $media = Media::updateOrCreate(['path' => $path], ['url' => rtrim((string) config('app.url'), '/') . '/storage/' . $path, 'mime' => File::mimeType($file->getPathname()), 'size' => File::size($file->getPathname()), 'alt' => pathinfo($name, PATHINFO_FILENAME)]);
                $urls[$name] = $media->url;
            }
        return $urls;
    }

    private function fontAssets(array $media): array
    {
        $definitions = [
            ['family' => 'Fraunces', 'weight' => 300, 'file' => '6NUh8FyLNQOQZAnv9bYEvDiIdE9Ea92uemAk_WBq8U_9v0c2Wa0K7iN7hzFUPJH58nib1603gg7S2nfgRYIc6RujDg.ttf'],
            ['family' => 'Fraunces', 'weight' => 400, 'file' => '6NUh8FyLNQOQZAnv9bYEvDiIdE9Ea92uemAk_WBq8U_9v0c2Wa0K7iN7hzFUPJH58nib1603gg7S2nfgRYIctxujDg.ttf'],
            ['family' => 'Fraunces', 'weight' => 500, 'file' => '6NUh8FyLNQOQZAnv9bYEvDiIdE9Ea92uemAk_WBq8U_9v0c2Wa0K7iN7hzFUPJH58nib1603gg7S2nfgRYIchRujDg.ttf'],
            ['family' => 'Fraunces', 'weight' => 600, 'file' => '6NUh8FyLNQOQZAnv9bYEvDiIdE9Ea92uemAk_WBq8U_9v0c2Wa0K7iN7hzFUPJH58nib1603gg7S2nfgRYIcaRyjDg.ttf'],
            ['family' => 'Inter', 'weight' => 300, 'file' => 'UcCO3FwrK3iLTeHuS_nVMrMxCp50SjIw2boKoduKmMEVuOKfMZg.ttf'],
            ['family' => 'Inter', 'weight' => 400, 'file' => 'UcCO3FwrK3iLTeHuS_nVMrMxCp50SjIw2boKoduKmMEVuLyfMZg.ttf'],
            ['family' => 'Inter', 'weight' => 500, 'file' => 'UcCO3FwrK3iLTeHuS_nVMrMxCp50SjIw2boKoduKmMEVuI6fMZg.ttf'],
            ['family' => 'Inter', 'weight' => 600, 'file' => 'UcCO3FwrK3iLTeHuS_nVMrMxCp50SjIw2boKoduKmMEVuGKYMZg.ttf'],
            ['family' => 'Inter', 'weight' => 700, 'file' => 'UcCO3FwrK3iLTeHuS_nVMrMxCp50SjIw2boKoduKmMEVuFuYMZg.ttf'],
            ['family' => 'CameraPlainVariable', 'weight' => '100 900', 'file' => 'CameraPlainVariable.woff2'],
        ];
        return array_values(array_filter(array_map(fn (array $font): ?array => isset($media[$font['file']]) ? $font + ['url' => $media[$font['file']]] : null, $definitions)));
    }
}