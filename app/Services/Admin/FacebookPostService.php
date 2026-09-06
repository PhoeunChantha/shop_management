<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Exceptions\FacebookPublishException;
use App\Helpers\ImageManager;
use App\Models\Product;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Publishes a product to the configured Facebook Page as a photo post via
 * the Graph API. The image is uploaded directly (multipart), so no publicly
 * reachable URL is required — this also works against a local dev site.
 */
final class FacebookPostService
{
    private const GRAPH_VERSION = 'v19.0';

    public function __construct(
        private readonly SettingService $settings,
    ) {}

    /**
     * @throws FacebookPublishException
     */
    public function publish(Product $product): void
    {
        $config = $this->settings->facebook();

        if (! $this->settings->facebookConfigured()) {
            throw new FacebookPublishException(__('Facebook publishing is not set up yet. Add your Page ID and access token in Settings → Facebook Page.'));
        }

        $imagePath = $this->firstImagePath($product);

        if (! $imagePath || ! is_file($imagePath)) {
            throw new FacebookPublishException(__('This product has no image to publish.'));
        }

        $response = Http::attach('source', file_get_contents($imagePath), basename($imagePath))
            ->post(sprintf('https://graph.facebook.com/%s/%s/photos', self::GRAPH_VERSION, $config['page_id']), [
                'caption' => $this->caption($product),
                'access_token' => $config['access_token'],
            ]);

        if ($response->failed()) {
            throw new FacebookPublishException($this->readableError($response));
        }

        $data = $response->json();
        $postId = $data['post_id'] ?? $data['id'] ?? null;

        $product->forceFill([
            'facebook_post_id' => $postId,
            'facebook_permalink_url' => $postId ? "https://www.facebook.com/{$postId}" : null,
            'facebook_posted_at' => now(),
        ])->save();
    }

    private function firstImagePath(Product $product): ?string
    {
        $image = $product->thumbnail ?: optional($product->images->first())->image;

        $relative = $image ? ImageManager::path($image, 'products') : null;

        return $relative ? public_path($relative) : null;
    }

    private function caption(Product $product): string
    {
        $price = $product->has_discount ? $product->final_price : (float) $product->price;

        $lines = [
            (string) $product->name,
            '$'.number_format($price, 2),
            route('frontend.shop.show', $product->slug),
        ];

        return implode("\n\n", array_filter($lines, fn ($line) => trim($line) !== ''));
    }

    /**
     * @param  Response  $response
     */
    private function readableError($response): string
    {
        $message = $response->json('error.message');

        return $message
            ? __('Facebook rejected the request: :message', ['message' => $message])
            : __('Could not reach Facebook. Please try again.');
    }
}
