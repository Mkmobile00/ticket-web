<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlogPostSeeder extends Seeder
{
    public function run(): void
    {
        $categories = BlogCategory::all();
        $tags = BlogTag::all();
        $author = User::where('role', 'admin')->first();

        $posts = [
            ['title' => 'Cheap Movie Tickets - Bring Your Loved Ones to See New Releases', 'excerpt' => 'Learn how to get the best movie ticket deals.'],
            ['title' => '10 Must-Watch Movies This Month', 'excerpt' => 'Discover the top 10 movies you should not miss.'],
            ['title' => 'The Ultimate Guide to Event Planning', 'excerpt' => 'Tips and tricks for organizing memorable events.'],
            ['title' => 'Behind the Scenes of Movie Production', 'excerpt' => 'Explore the fascinating world of filmmaking.'],
            ['title' => 'Entertainment Trends in 2025', 'excerpt' => 'What to expect in the entertainment industry this year.'],
            ['title' => 'How to Review a Movie Like a Pro', 'excerpt' => 'A guide to writing effective movie reviews.'],
            ['title' => 'Live Events vs Streaming: What\'s Better?', 'excerpt' => 'Comparing live entertainment with online streaming.'],
            ['title' => 'Celebrity Updates and Gossip', 'excerpt' => 'Latest news from the entertainment world.'],
            ['title' => 'Smart Ways to Save on Entertainment', 'excerpt' => 'Budget-friendly tips for enjoying entertainment.'],
            ['title' => 'The Future of Movie Theaters', 'excerpt' => 'How technology is changing cinema experiences.'],
        ];

        foreach ($posts as $index => $postData) {
            $post = BlogPost::create([
                'title' => $postData['title'],
                'slug' => Str::slug($postData['title']),
                'excerpt' => $postData['excerpt'],
                'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.',
                'author_id' => $author->id,
                'thumbnail' => 'assets/images/blog/blog' . str_pad((($index % 4) + 1), 2, '0', STR_PAD_LEFT) . '.jpg',
                'views' => rand(100, 5000),
                'is_featured' => $index < 3,
                'published_at' => now()->subDays($index),
            ]);

            // Attach random categories and tags
            $post->categories()->attach($categories->random(2)->pluck('id'));
            $post->tags()->attach($tags->random(3)->pluck('id'));
        }
    }
}
