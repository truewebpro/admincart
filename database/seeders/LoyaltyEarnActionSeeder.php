<?php

namespace Database\Seeders;

use App\Models\LoyaltyEarnAction;
use Illuminate\Database\Seeder;

/**
 * Example only — run manually or adapt into your own shop-onboarding flow.
 * These are exactly the actions requested: product reviews, Google/Trustpilot
 * reviews, social follows, product share, plus one custom example. "Complete
 * order" is included as an informational row only — actual order points are
 * still calculated dynamically via LoyaltySetting / LoyaltyProductPoint, not
 * this table.
 */
class LoyaltyEarnActionSeeder extends Seeder
{
    public function run(): void
    {
        $shopId = 2; // replace with your target shop

        $actions = [
            [
                'category' => 'review',
                'platform' => 'onsite',
                'label' => 'Leave a product review',
                'description' => 'Review any product you\'ve purchased.',
                'points' => 50,
                'verification' => 'automatic',
                'repeat_scope' => 'once_per_reference',
            ],
            [
                'category' => 'review',
                'platform' => 'google',
                'label' => 'Leave us a Google review',
                'description' => 'Share your experience on Google — link your review below.',
                'action_url' => 'https://g.page/r/your-shop/review',
                'points' => 150,
                'verification' => 'manual_admin',
                'repeat_scope' => 'once_per_customer',
            ],
            [
                'category' => 'review',
                'platform' => 'trustpilot',
                'label' => 'Leave us a Trustpilot review',
                'description' => 'Rate us on Trustpilot — link your review below.',
                'action_url' => 'https://uk.trustpilot.com/review/your-shop.com',
                'points' => 150,
                'verification' => 'manual_admin',
                'repeat_scope' => 'once_per_customer',
            ],
            [
                'category' => 'social_follow',
                'platform' => 'facebook',
                'label' => 'Follow us on Facebook',
                'action_url' => 'https://facebook.com/yourshop',
                'points' => 50,
                'verification' => 'manual_admin',
                'repeat_scope' => 'once_per_customer',
            ],
            [
                'category' => 'social_follow',
                'platform' => 'instagram',
                'label' => 'Follow us on Instagram',
                'action_url' => 'https://instagram.com/yourshop',
                'points' => 50,
                'verification' => 'manual_admin',
                'repeat_scope' => 'once_per_customer',
            ],
            [
                'category' => 'social_follow',
                'platform' => 'twitter',
                'label' => 'Follow us on X / Twitter',
                'action_url' => 'https://x.com/yourshop',
                'points' => 50,
                'verification' => 'manual_admin',
                'repeat_scope' => 'once_per_customer',
            ],
            [
                'category' => 'social_follow',
                'platform' => 'tiktok',
                'label' => 'Follow us on TikTok',
                'action_url' => 'https://tiktok.com/@yourshop',
                'points' => 50,
                'verification' => 'manual_admin',
                'repeat_scope' => 'once_per_customer',
            ],
            [
                'category' => 'social_follow',
                'platform' => 'youtube',
                'label' => 'Subscribe on YouTube',
                'action_url' => 'https://youtube.com/@yourshop',
                'points' => 50,
                'verification' => 'manual_admin',
                'repeat_scope' => 'once_per_customer',
            ],
            [
                'category' => 'social_follow',
                'platform' => 'whatsapp_channel',
                'label' => 'Join our WhatsApp Channel',
                'action_url' => 'https://whatsapp.com/channel/yourshop',
                'points' => 50,
                'verification' => 'manual_admin',
                'repeat_scope' => 'once_per_customer',
            ],
            [
                'category' => 'social_share',
                'platform' => null,
                'label' => 'Share a product with friends',
                'description' => 'Share to any platform you like — Facebook, X, WhatsApp, Reddit, Pinterest, or copy the link. Once per product.',
                'points' => 25,
                'verification' => 'manual_admin',
                'repeat_scope' => 'once_per_reference',
            ],
            [
                'category' => 'social_share',
                'platform' => 'reddit',
                'label' => 'Share our product feed on Reddit',
                'description' => 'Post a product to a relevant subreddit.',
                'points' => 40,
                'verification' => 'manual_admin',
                'repeat_scope' => 'once_per_reference',
            ],
            [
                'category' => 'order',
                'platform' => 'onsite',
                'label' => 'Complete an order',
                'description' => 'Points are calculated from what you spend — see the rate below.',
                'points' => 0, // informational row; real value comes from LoyaltySetting
                'verification' => 'automatic',
                'repeat_scope' => 'unlimited',
            ],
            [
                'category' => 'custom',
                'platform' => null,
                'label' => 'Sign up for our newsletter',
                'points' => 30,
                'verification' => 'manual_admin',
                'repeat_scope' => 'once_per_customer',
            ],
        ];

        foreach ($actions as $i => $action) {
            LoyaltyEarnAction::create($action + ['shop_id' => $shopId, 'is_active' => true, 'sort_order' => $i]);
        }
    }
}
