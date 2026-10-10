<?php

namespace Database\Seeders;

use App\Models\ServicePage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServicePagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $serviceCategories = [
            [
                'id' => 'haircare',
                'name' => 'Hair Care',
                'icon' => '💇‍♀️',
                'color' => 'from-purple-500 to-purple-500',
                'description' => 'Expert hair styling, coloring, and treatments',
                'services' => [
                    [
                        'name' => 'Hair Cut & Styling',
                        'description' => 'Professional haircuts and styling for all hair types',
                        'price' => 'From Rs. 1,500',
                        'duration' => '45-60 mins',
                        'image' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=800&q=80',
                        'features' => ['Consultation', 'Wash & Blow Dry', 'Styling'],
                    ],
                    [
                        'name' => 'Hair Coloring',
                        'description' => 'Full color, highlights, balayage, and ombre',
                        'price' => 'From Rs. 8,000',
                        'duration' => '2-3 hours',
                        'image' => 'https://images.unsplash.com/photo-1562322140-8baeececf3df?w=800&q=80',
                        'features' => ['Color Consultation', 'Premium Products', 'After-care'],
                    ],
                    [
                        'name' => 'Hair Treatment',
                        'description' => 'Deep conditioning, keratin, and protein treatments',
                        'price' => 'From Rs. 5,000',
                        'duration' => '1-2 hours',
                        'image' => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=800&q=80',
                        'features' => ['Scalp Analysis', 'Treatment', 'Massage'],
                    ],
                    [
                        'name' => 'Hair Extensions',
                        'description' => 'Premium quality clip-in and permanent extensions',
                        'price' => 'From Rs. 15,000',
                        'duration' => '2-4 hours',
                        'image' => 'https://images.unsplash.com/photo-1519699047748-de8e457a634e?w=800&q=80',
                        'features' => ['Quality Hair', 'Natural Look', 'Maintenance Guide'],
                    ],
                ],
            ],
            [
                'id' => 'skincare',
                'name' => 'Skin Care',
                'icon' => '✨',
                'color' => 'from-blue-500 to-cyan-500',
                'description' => 'Rejuvenating facials and skin treatments',
                'services' => [
                    [
                        'name' => 'Classic Facial',
                        'description' => 'Deep cleansing and hydrating facial treatment',
                        'price' => 'From Rs. 3,500',
                        'duration' => '60 mins',
                        'image' => 'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?w=800&q=80',
                        'features' => ['Cleansing', 'Exfoliation', 'Mask', 'Massage'],
                    ],
                    [
                        'name' => 'Anti-Aging Facial',
                        'description' => 'Advanced treatment for mature skin',
                        'price' => 'From Rs. 7,500',
                        'duration' => '90 mins',
                        'image' => 'https://images.unsplash.com/photo-1616394584738-fc6e612e71b9?w=800&q=80',
                        'features' => ['Collagen Boost', 'Firming', 'Brightening'],
                    ],
                    [
                        'name' => 'Acne Treatment',
                        'description' => 'Specialized treatment for acne-prone skin',
                        'price' => 'From Rs. 4,500',
                        'duration' => '75 mins',
                        'image' => 'https://images.unsplash.com/photo-1512290923902-8a9f81dc236c?w=800&q=80',
                        'features' => ['Deep Cleansing', 'Extraction', 'Healing Mask'],
                    ],
                    [
                        'name' => 'Skin Brightening',
                        'description' => 'Treatment for radiant, glowing skin',
                        'price' => 'From Rs. 6,000',
                        'duration' => '60 mins',
                        'image' => 'https://images.unsplash.com/photo-1598440947619-2c35fc9aa908?w=800&q=80',
                        'features' => ['Whitening', 'Even Tone', 'Glow Boost'],
                    ],
                ],
            ],
            [
                'id' => 'nails',
                'name' => 'Nails',
                'icon' => '💅',
                'color' => 'from-purple-500 to-rose-500',
                'description' => 'Beautiful manicures, pedicures, and nail art',
                'services' => [
                    [
                        'name' => 'Classic Manicure',
                        'description' => 'Traditional manicure with polish',
                        'price' => 'From Rs. 1,200',
                        'duration' => '45 mins',
                        'image' => 'https://images.unsplash.com/photo-1604654894610-df63bc536371?w=800&q=80',
                        'features' => ['Nail Shaping', 'Cuticle Care', 'Polish'],
                    ],
                    [
                        'name' => 'Gel Manicure',
                        'description' => 'Long-lasting gel polish manicure',
                        'price' => 'From Rs. 2,500',
                        'duration' => '60 mins',
                        'image' => 'https://images.unsplash.com/photo-1610992015732-2449b76344bc?w=800&q=80',
                        'features' => ['Gel Polish', 'Long-lasting', 'Chip-resistant'],
                    ],
                    [
                        'name' => 'Pedicure',
                        'description' => 'Complete foot care and polish',
                        'price' => 'From Rs. 2,000',
                        'duration' => '60 mins',
                        'image' => 'https://images.unsplash.com/photo-1519014816548-bf5fe059798b?w=800&q=80',
                        'features' => ['Soak', 'Scrub', 'Massage', 'Polish'],
                    ],
                    [
                        'name' => 'Nail Art',
                        'description' => 'Creative designs and extensions',
                        'price' => 'From Rs. 3,500',
                        'duration' => '90 mins',
                        'image' => 'https://images.unsplash.com/photo-1632345031435-8727f6897d53?w=800&q=80',
                        'features' => ['Custom Design', 'Premium Quality', 'Detailed Work'],
                    ],
                ],
            ],
            [
                'id' => 'spa',
                'name' => 'Spa & Wellness',
                'icon' => '🧖‍♀️',
                'color' => 'from-green-500 to-emerald-500',
                'description' => 'Relaxing massages and body treatments',
                'services' => [
                    [
                        'name' => 'Swedish Massage',
                        'description' => 'Relaxing full body massage',
                        'price' => 'From Rs. 6,000',
                        'duration' => '60 mins',
                        'image' => 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?w=800&q=80',
                        'features' => ['Full Body', 'Relaxation', 'Stress Relief'],
                    ],
                    [
                        'name' => 'Deep Tissue Massage',
                        'description' => 'Therapeutic massage for muscle tension',
                        'price' => 'From Rs. 7,500',
                        'duration' => '75 mins',
                        'image' => 'https://images.unsplash.com/photo-1600334129128-685c5582fd35?w=800&q=80',
                        'features' => ['Muscle Relief', 'Pain Management', 'Deep Pressure'],
                    ],
                    [
                        'name' => 'Aromatherapy',
                        'description' => 'Massage with essential oils',
                        'price' => 'From Rs. 8,000',
                        'duration' => '90 mins',
                        'image' => 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?w=800&q=80',
                        'features' => ['Essential Oils', 'Relaxation', 'Holistic'],
                    ],
                    [
                        'name' => 'Body Scrub',
                        'description' => 'Exfoliating body treatment',
                        'price' => 'From Rs. 5,500',
                        'duration' => '60 mins',
                        'image' => 'https://images.unsplash.com/photo-1596178060671-7a80dc8059ea?w=800&q=80',
                        'features' => ['Exfoliation', 'Moisturizing', 'Smooth Skin'],
                    ],
                ],
            ],
            [
                'id' => 'makeup',
                'name' => 'Makeup',
                'icon' => '💄',
                'color' => 'from-orange-500 to-red-500',
                'description' => 'Professional makeup for all occasions',
                'services' => [
                    [
                        'name' => 'Party Makeup',
                        'description' => 'Glamorous makeup for special events',
                        'price' => 'From Rs. 4,500',
                        'duration' => '60 mins',
                        'image' => 'https://images.unsplash.com/photo-1487412947147-5cebf100ffc2?w=800&q=80',
                        'features' => ['Full Face', 'Long-lasting', 'Photo-ready'],
                    ],
                    [
                        'name' => 'Bridal Makeup',
                        'description' => 'Complete bridal makeup package',
                        'price' => 'From Rs. 15,000',
                        'duration' => '2-3 hours',
                        'image' => 'https://plus.unsplash.com/premium_photo-1661326352695-6cbe1ff74ee9?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D',
                        'features' => ['Trial Session', 'HD Makeup', 'Hairstyling'],
                    ],
                    [
                        'name' => 'Natural Makeup',
                        'description' => 'Subtle, everyday makeup look',
                        'price' => 'From Rs. 3,000',
                        'duration' => '45 mins',
                        'image' => 'https://images.unsplash.com/photo-1516975080664-ed2fc6a32937?w=800&q=80',
                        'features' => ['Natural Look', 'Lightweight', 'Fresh'],
                    ],
                    [
                        'name' => 'Makeup Lesson',
                        'description' => 'Learn professional makeup techniques',
                        'price' => 'From Rs. 8,000',
                        'duration' => '2 hours',
                        'image' => 'https://images.unsplash.com/photo-1512496015851-a90fb38ba796?w=800&q=80',
                        'features' => ['One-on-One', 'Techniques', 'Product Guide'],
                    ],
                ],
            ],
            [
                'id' => 'waxing',
                'name' => 'Waxing & Threading',
                'icon' => '🌟',
                'color' => 'from-yellow-500 to-orange-500',
                'description' => 'Professional hair removal services',
                'services' => [
                    [
                        'name' => 'Full Body Waxing',
                        'description' => 'Complete body hair removal',
                        'price' => 'From Rs. 8,000',
                        'duration' => '90 mins',
                        'image' => 'https://images.unsplash.com/photo-1515377905703-c4788e51af15?w=800&q=80',
                        'features' => ['Full Body', 'Smooth Skin', 'Long-lasting'],
                    ],
                    [
                        'name' => 'Face Waxing',
                        'description' => 'Gentle facial hair removal',
                        'price' => 'From Rs. 800',
                        'duration' => '20 mins',
                        'image' => 'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=800&q=80',
                        'features' => ['Gentle', 'Smooth', 'Quick'],
                    ],
                    [
                        'name' => 'Eyebrow Threading',
                        'description' => 'Precise eyebrow shaping',
                        'price' => 'From Rs. 300',
                        'duration' => '15 mins',
                        'image' => 'https://images.unsplash.com/photo-1588514727390-91fd5ebaef81?w=800&q=80',
                        'features' => ['Precise', 'Natural Shape', 'Professional'],
                    ],
                    [
                        'name' => 'Brazilian Wax',
                        'description' => 'Complete bikini area waxing',
                        'price' => 'From Rs. 3,500',
                        'duration' => '45 mins',
                        'image' => 'https://images.unsplash.com/photo-1519415510236-718bdfcd89c8?w=800&q=80',
                        'features' => ['Complete', 'Hygenic', 'Professional'],
                    ],
                ],
            ],
        ];

        foreach ($serviceCategories as $category) {
            foreach ($category['services'] as $svc) {
                $slugBase = Str::slug($svc['name']);
                $slug = $slugBase;
                $i = 1;
                while (ServicePage::where('slug', $slug)->exists()) {
                    $slug = $slugBase . '-' . $i++;
                }

                ServicePage::create([
                    'title' => $svc['name'],
                    'slug' => $slug,
                    'description' => $svc['description'],
                    'price' => $svc['price'] ?? null,
                    'duration' => $svc['duration'] ?? null,
                    'image' => $svc['image'] ?? null,
                    'features' => $svc['features'] ?? [],
                    'category_id' => $category['id'],
                    'category_name' => $category['name'],
                    'category_color' => $category['color'],
                    'status' => 'published',
                ]);
            }
        }
    }
}
