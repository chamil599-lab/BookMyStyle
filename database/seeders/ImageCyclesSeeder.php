<?php

namespace Database\Seeders;

use App\Models\ImageCycle;
use Illuminate\Database\Seeder;

class ImageCyclesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $items = [
            [
                'src_url' => 'https://www.pngall.com/wp-content/uploads/5/Hair-Salon-PNG-Free-Image.png',
                'alt' => 'Hair Styling',
                'title' => 'Expert Hair Styling',
                'description' => 'Transform your look with professional styling',
            ],
            [
                'src_url' => 'https://purepng.com/public/uploads/large/purepng.com-beautiful-girlwomenpeoplepersonsfemalebeautiful-1121525108709vpm7g.png',
                'alt' => 'Makeup Services',
                'title' => 'Glamorous Makeup',
                'description' => 'Enhance your natural beauty',
            ],
            [
                'src_url' => 'https://www.pngkey.com/png/detail/71-710475_love-your-hair-hair-salon-girl-png.png',
                'alt' => 'Nail Services',
                'title' => 'Nail Artistry',
                'description' => 'Perfect nails for every occasion',
            ],
            [
                'src_url' => 'https://www.pngall.com/wp-content/uploads/5/Hair-Salon-PNG-Free-Image.png',
                'alt' => 'Spa Treatment',
                'title' => 'Relaxing Spa',
                'description' => 'Indulge in ultimate relaxation',
            ],
            [
                'src_url' => 'https://purepng.com/public/uploads/large/purepng.com-beautiful-girlwomenpeoplepersonsfemalebeautiful-1121525108709vpm7g.png',
                'alt' => 'Professional Styling',
                'title' => 'Professional Care',
                'description' => 'Excellence in every service',
            ],
        ];

        foreach ($items as $i => $it) {
            // avoid duplicates by title + src_url
            $exists = ImageCycle::where('title', $it['title'])
                ->where('src_url', $it['src_url'])
                ->exists();

            if ($exists) {
                continue;
            }

            ImageCycle::create([
                'title' => $it['title'],
                'description' => $it['description'] ?? null,
                'alt' => $it['alt'] ?? null,
                'src_url' => $it['src_url'] ?? null,
                'upload_path' => null,
                'order' => $i,
                'status' => 1,
            ]);
        }
    }
}
