<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /** Ordre d'affichage voulu : shawarma avant boissons. */
    private const CATEGORY_ORDER = ['shawarma' => 1, 'boissons' => 2];

    public function index(): JsonResponse
    {
        $rows = Product::where('active', true)
            ->orderBy('name')
            ->get();

        $byCategory = [];
        foreach ($rows as $p) {
            $byCategory[$p->category][] = [
                'id' => $p->id,
                'name' => $p->name,
                'description' => $p->description,
                'basePrice' => (int) $p->base_price,
                'hasFlavors' => (bool) $p->has_flavors,
            ];
        }

        uksort($byCategory, function ($a, $b) {
            return (self::CATEGORY_ORDER[$a] ?? 99) <=> (self::CATEGORY_ORDER[$b] ?? 99);
        });

        return response()->json([
            'categories' => array_keys($byCategory),
            'products' => (object) $byCategory,
        ]);
    }
}
