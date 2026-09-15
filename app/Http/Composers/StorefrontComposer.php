<?php

namespace App\Http\Composers;

use App\Models\Category;
use App\Services\Cart\CartService;
use Illuminate\View\View;

/**
 * The header needs the category tree for the mega menu and a basket count on
 * every page of the storefront. The tree is cached, because it changes when
 * someone edits the catalogue, not on every request.
 */
class StorefrontComposer
{
    public function __construct(
        private readonly CartService $cart,
    ) {
    }

    public function compose(View $view): void
    {
        $view->with([
            'navCategories' => $this->categories(),
            'cartCount' => $this->cart->pieceCount(),
            'cartLines' => $this->cart->lineCount(),
        ]);
    }

    private function categories()
    {
        return cache()->remember(
            'nav.categories',
            now()->addHour(),
            fn () => Category::query()
                ->withCount(['products' => fn ($q) => $q->where('is_published', true)])
                ->orderBy('position')
                ->get()
                ->groupBy('group')
        );
    }
}
