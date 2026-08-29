<?php

namespace App\Http\Controllers;

use App\Exceptions\MissingShopifyScopeException;
use App\Models\Blog;
use App\Models\ShopifyShop;
use App\Models\ShopUser;
use App\Services\ImageService;
use App\Services\ShopifyBlogService;
use Illuminate\Http\Request;

class ShopifyBlogController extends Controller
{
    /**
     * Live view of blog containers (e.g. "News", "Guides") — rarely
     * more than a handful, so no pagination needed. This is a
     * navigation/grouping layer only; blog containers themselves are
     * never stored locally, only the articles within them (see create()).
     */
    public function liveBlogs(int $shopId)
    {
        $shopifyShop = ShopifyShop::where('shop_id', $shopId)->firstOrFail();
        $service = new ShopifyBlogService($shopifyShop);

        try {
            $blogs = $service->getBlogs();
        } catch (MissingShopifyScopeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'required_scope' => $e->requiredScope,
            ], 403);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 502);
        }

        return response()->json(['success' => true, 'blogs' => $blogs]);
    }

    /**
     * Live view of articles within ONE blog — bounded to 250 (Shopify's
     * own per-call max), no cursor pagination, same reasoning as pages.
     */
    public function liveArticles(Request $request, int $shopId, int $blogId)
    {
        $shopifyShop = ShopifyShop::where('shop_id', $shopId)->firstOrFail();
        $service = new ShopifyBlogService($shopifyShop);

        try {
            $articles = $service->getArticles($blogId, (int) $request->input('limit', 250));
        } catch (MissingShopifyScopeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'required_scope' => $e->requiredScope,
            ], 403);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 502);
        }

        return response()->json(['success' => true, 'articles' => $articles]);
    }

    /**
     * Single, explicit create — no bulk. Re-fetches the specific
     * article from Shopify by ID (authoritative), then creates a real
     * row in your blogs table (which stores individual articles/posts,
     * per your existing schema).
     */
    public function create(Request $request, int $shopId, int $blogId, string $articleId)
    {
        $validated = $request->validate([
            // Passed from the frontend, which already has this from the
            // live blogs list — avoids an extra Shopify call just to
            // look up a handle we were already shown once.
            'blog_handle' => ['nullable', 'string', 'max:255'],
        ]);
        $shopifyShop = ShopifyShop::where('shop_id', $shopId)->firstOrFail();
        $service = new ShopifyBlogService($shopifyShop);

        try {
            $article = $service->getArticleById($blogId, $articleId);
        } catch (MissingShopifyScopeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'required_scope' => $e->requiredScope,
            ], 403);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 502);
        }

        if (empty($article['id']) || empty($article['handle'])) {
            return response()->json(['success' => false, 'message' => 'Invalid article data.'], 422);
        }

        $existing = Blog::where('shop_id', $shopId)
            ->where('thirdparty_id', (string) $article['id'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'This article has already been created.',
                'blog_id' => $existing->id ?? $existing->blog_id ?? null,
            ], 422);
        }

        $blogImage = null;

        if (! empty($article['image']['src'])) {
            try {
                $blogImage = ImageService::storeFromUrl($article['image']['src'], 'blogs', 1200, 800);
            } catch (\Throwable $e) {
                $blogImage = null;
            }
        }


        $tags = ! empty($article['tags'])
            ? array_map('trim', explode(',', $article['tags']))
            : null;

        // Same author-resolution fallback used in the original bulk
        // importArticles() flow.
        $shopUser = ShopUser::where('shop_id', $shopId)
            ->where('role', '!=', 'superadmin')
            ->first();
        $userId = $shopUser?->user_id ?? 1;

        $blog = Blog::create([
            'shop_id'          => $shopId,
            'thirdparty_id'    => (string) $article['id'],
            'thirdparty_blog_id'     => (string) $blogId, // the parent blog container's Shopify id — needed to re-fetch this article via REST later
            'thirdparty_blog_handle' => $validated['blog_handle'] ?? null, // for reconstructing the original /blogs/{blog_handle}/{article_handle} URL later
            'blog_title'       => $article['title'] ?? 'Untitled',
            'blog_slug'        => $article['handle'],
            'blog_description' => $article['body_html'] ?? null,
            'blog_excerpt'     => $article['summary_html'] ?? null,
            'blog_image'       => $blogImage,
            'btags'            => $tags,
            'blog_status'      => 'active',
            'meta_title'       => $article['title'] ?? null,
            'meta_desc'        => $article['title'] ?? null,
            'user_id'          => $userId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Article created.',
            'blog_id' => $blog->blog_id ?? null,
        ]);
    }

    /**
     * Re-fetches an already-created article's content from Shopify and
     * updates the local row. This is what thirdparty_blog_id actually
     * enables — without it, there'd be no way to call Shopify's REST
     * article endpoint again for something already created, since it's
     * nested under a specific blog_id we'd otherwise have lost track of.
     */
    public function refresh(int $shopId, string $localBlogId)
    {
        $blog = Blog::where('shop_id', $shopId)->findOrFail($localBlogId);

        if (! $blog->thirdparty_id || ! $blog->thirdparty_blog_id) {
            return response()->json([
                'success' => false,
                'message' => 'This article has no Shopify reference to refresh from (created manually, not via import).',
            ], 422);
        }

        $shopifyShop = ShopifyShop::where('shop_id', $shopId)->firstOrFail();
        $service = new ShopifyBlogService($shopifyShop);

        try {
            $article = $service->getArticleById((int) $blog->thirdparty_blog_id, $blog->thirdparty_id);
        } catch (MissingShopifyScopeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'required_scope' => $e->requiredScope,
            ], 403);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 502);
        }

        $tags = ! empty($article['tags'])
            ? array_map('trim', explode(',', $article['tags']))
            : null;

        $blog->update([
            'blog_title'       => $article['title'] ?? $blog->blog_title,
            'blog_description' => $article['body_html'] ?? $blog->blog_description,
            'blog_excerpt'     => $article['summary_html'] ?? $blog->blog_excerpt,
            'btags'            => $tags,
        ]);

        return response()->json(['success' => true, 'message' => 'Article refreshed from Shopify.']);
    }

    /**
     * Syncs SEO title/description for already-created articles, from
     * Shopify's title_tag/description_tag metafields. Only updates rows
     * with a thirdparty_id — articles created manually in your own
     * admin (never came from Shopify) are correctly left alone.
     */
    /**
     * One-time backfill for articles that were imported BEFORE
     * thirdparty_id/thirdparty_blog_id/thirdparty_blog_handle existed
     * on this table. Matches by blog_slug (== Shopify's article handle)
     * against shop_id, since that's the only reliable link available
     * for these older rows.
     *
     * Deliberately UPDATE-ONLY, not create — this backfills tracking
     * data on rows that already exist locally, it does not import
     * anything new. Only touches the thirdparty_* columns; existing
     * content (title, description, etc.) is never overwritten, so any
     * manual edits made after the original import are preserved.
     *
     * Safe to re-run: only rows with thirdparty_id still NULL are
     * considered, so already-backfilled rows are skipped automatically.
     */
    public function backfillThirdpartyIds(int $shopId)
    {
        $shopifyShop = ShopifyShop::where('shop_id', $shopId)->firstOrFail();
        $service = new ShopifyBlogService($shopifyShop);

        try {
            $blogs = $service->getBlogs();
        } catch (MissingShopifyScopeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'required_scope' => $e->requiredScope,
            ], 403);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 502);
        }

        $matched = 0;
        $checked = 0;

        foreach ($blogs as $shopifyBlog) {
            $articles = $service->getArticles($shopifyBlog['id']);

            foreach ($articles as $article) {
                $checked++;

                $existing = Blog::where('shop_id', $shopId)
                    ->where('blog_slug', $article['handle'])
                    ->whereNull('thirdparty_id') // only ever touch un-tracked rows — makes this safe to re-run
                    ->first();

                if ($existing) {
                    $existing->update([
                        'thirdparty_id'          => (string) $article['id'],
                        'thirdparty_blog_id'     => (string) $shopifyBlog['id'],
                        'thirdparty_blog_handle' => $shopifyBlog['handle'] ?? null,
                    ]);
                    $matched++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'checked' => $checked, // total Shopify articles looked at
            'matched' => $matched, // how many local rows got backfilled
        ]);
    }

    public function syncSeo(int $shopId)
    {
        $shopifyShop = ShopifyShop::where('shop_id', $shopId)->firstOrFail();
        $service = new ShopifyBlogService($shopifyShop);

        $blogs = Blog::where('shop_id', $shopId)
            ->whereNotNull('thirdparty_id')
            ->get(['blog_id', 'thirdparty_id','shop_id','blog_slug']); // adjust 'id' to your actual PK if different — see note in create()

        if ($blogs->isEmpty()) {
            return response()->json(['success' => true, 'updated' => 0]);
        }

        $shopifyIds = $blogs->pluck('thirdparty_id')->map(fn ($id) => (int) $id)->all();

        try {
            $seoData = $service->getArticlesSeo($shopifyIds);
        } catch (MissingShopifyScopeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'required_scope' => $e->requiredScope,
            ], 403);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 502);
        }

        $updated = 0;

        foreach ($blogs as $blog) {
            $seo = $seoData[(int) $blog->thirdparty_id] ?? null;

            if (! $seo) {
                continue;
            }

            $updates = [];
            if (! empty($seo['title'])) {
                $updates['meta_title'] = $seo['title'];
            }
            if (! empty($seo['description'])) {
                $updates['meta_desc'] = $seo['description']; // matches your existing column name (meta_desc, not meta_description)
            }

            if ($updates) {
                $blog->update($updates);
                $updated++;
            }
        }

        return response()->json(['success' => true, 'updated' => $updated]);
    }
}
