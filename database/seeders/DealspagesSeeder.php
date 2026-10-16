<?php

namespace Database\Seeders;

use App\Models\Dealspage;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DealspagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $deals = [
            [
                'category' => 'haircare',
                'title' => 'Premium Hair Color Package',
                'salon' => 'Glamour Studio Colombo',
                'originalPrice' => 15000,
                'discountedPrice' => 9999,
                'discount' => 33,
                'image' => 'https://images.unsplash.com/photo-1562322140-8baeececf3df?w=800&q=80',
                'badge' => 'Hot Deal',
                'badgeColor' => 'from-red-500 to-orange-500',
                'description' => 'Full hair coloring with premium products + free hair treatment',
                'validUntil' => '2024-12-31',
                'features' => ['Premium Color', 'Hair Treatment', 'Styling'],
            ],
            [
                'category' => 'skincare',
                'title' => 'Luxury Facial Package',
                'salon' => 'Serenity Spa',
                'originalPrice' => 12000,
                'discountedPrice' => 7499,
                'discount' => 37,
                'image' => 'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?w=800&q=80',
                'badge' => 'Limited',
                'badgeColor' => 'from-purple-500 to-purple-500',
                'description' => 'Deep cleansing facial with organic products + face massage',
                'validUntil' => '2024-12-25',
                'features' => ['Deep Cleansing', 'Organic Products', 'Face Massage'],
            ],
            [
                'category' => 'nails',
                'title' => 'Gel Manicure & Pedicure',
                'salon' => 'Nail Art Heaven',
                'originalPrice' => 8000,
                'discountedPrice' => 4999,
                'discount' => 37,
                'image' => 'https://images.unsplash.com/photo-1604654894610-df63bc536371?w=800&q=80',
                'badge' => 'Popular',
                'badgeColor' => 'from-blue-500 to-cyan-500',
                'description' => 'Complete gel mani-pedi with nail art of your choice',
                'validUntil' => '2024-12-30',
                'features' => ['Gel Polish', 'Nail Art', 'Hand Massage'],
            ],
            [
                'category' => 'spa',
                'title' => 'Full Body Massage Special',
                'salon' => 'Tranquil Touch Spa',
                'originalPrice' => 18000,
                'discountedPrice' => 12999,
                'discount' => 28,
                'image' => 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?w=800&q=80',
                'badge' => 'Exclusive',
                'badgeColor' => 'from-green-500 to-emerald-500',
                'description' => '90-minute aromatherapy massage with essential oils',
                'validUntil' => '2024-12-28',
                'features' => ['90 Minutes', 'Aromatherapy', 'Essential Oils'],
            ],
            [
                'category' => 'makeup',
                'title' => 'Bridal Makeup Trial',
                'salon' => 'Elegant Bridal Studio',
                'originalPrice' => 10000,
                'discountedPrice' => 5999,
                'discount' => 40,
                'image' => 'https://images.unsplash.com/photo-1487412947147-5cebf100ffc2?w=800&q=80',
                'badge' => 'New',
                'badgeColor' => 'from-yellow-500 to-orange-500',
                'description' => 'Complete bridal makeup trial with hairstyling',
                'validUntil' => '2024-12-31',
                'features' => ['Makeup Trial', 'Hairstyling', 'Consultation'],
            ],
            [
                'category' => 'haircare',
                'title' => 'Keratin Treatment Offer',
                'salon' => 'Hair Therapy Lounge',
                'originalPrice' => 25000,
                'discountedPrice' => 17999,
                'discount' => 28,
                'image' => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=800&q=80',
                'badge' => 'Premium',
                'badgeColor' => 'from-purple-500 to-indigo-500',
                'description' => 'Brazilian keratin treatment for smooth, frizz-free hair',
                'validUntil' => '2024-12-29',
                'features' => ['Brazilian Keratin', 'Smoothing', 'Long-lasting'],
            ],
            [
                'category' => 'skincare',
                'title' => 'Anti-Aging Treatment',
                'salon' => 'Youth Restore Clinic',
                'originalPrice' => 20000,
                'discountedPrice' => 13999,
                'discount' => 30,
                'image' => 'https://images.unsplash.com/photo-1616394584738-fc6e612e71b9?w=800&q=80',
                'badge' => 'Trending',
                'badgeColor' => 'from-purple-500 to-rose-500',
                'description' => 'Advanced anti-aging facial with collagen boost',
                'validUntil' => '2024-12-27',
                'features' => ['Anti-Aging', 'Collagen Boost', 'Skin Tightening'],
            ],
            [
                'category' => 'spa',
                'title' => 'Couples Spa Package',
                'salon' => 'Paradise Wellness Spa',
                'originalPrice' => 30000,
                'discountedPrice' => 21999,
                'discount' => 27,
                'image' => 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?w=800&q=80',
                'badge' => 'Romantic',
                'badgeColor' => 'from-red-500 to-purple-500',
                'description' => 'Couples massage and spa experience for two',
                'validUntil' => '2024-12-31',
                'features' => ['For 2 People', 'Massage', 'Refreshments'],
            ],
            [
                'category' => 'nails',
                'title' => 'Acrylic Nails Extension',
                'salon' => 'Chic Nails Studio',
                'originalPrice' => 6000,
                'discountedPrice' => 3999,
                'discount' => 33,
                'image' => 'https://images.unsplash.com/photo-1610992015732-2449b76344bc?w=800&q=80',
                'badge' => 'Best Value',
                'badgeColor' => 'from-teal-500 to-cyan-500',
                'description' => 'Full set acrylic nail extensions with design',
                'validUntil' => '2024-12-30',
                'features' => ['Full Set', 'Custom Design', 'Long-lasting'],
            ],
        ];

        foreach ($deals as $d) {
            $slugBase = Str::slug($d['title']);
            $slug = $slugBase;
            $i = 1;
            while (Dealspage::where('slug', $slug)->exists()) {
                $slug = $slugBase.'-'.$i++;
            }

            Dealspage::create([
                'title' => $d['title'],
                'slug' => $slug,
                'category' => $d['category'],
                'salon' => $d['salon'] ?? null,
                'original_price' => $d['originalPrice'] ?? null,
                'discounted_price' => $d['discountedPrice'] ?? null,
                'discount' => $d['discount'] ?? null,
                'image' => $d['image'] ?? null,
                'badge' => $d['badge'] ?? null,
                'badge_color' => $d['badgeColor'] ?? null,
                'description' => $d['description'] ?? null,
                'valid_until' => isset($d['validUntil']) ? Carbon::parse($d['validUntil'])->toDateString() : null,
                'features' => $d['features'] ?? [],
                'status' => 1,
            ]);
        }
    }
}
