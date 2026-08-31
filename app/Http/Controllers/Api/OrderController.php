<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private const SIZE_PRICES = ['Normal' => 0, 'Grand' => 500];
    private const DELIVERY_FREE_BELOW_KM = 1;
    private const DELIVERY_BASE = 33;
    private const DELIVERY_PER_EXTRA_KM = 50;
    private const PROMO_QTY_PER_FREE = 10;
    private const PAYMENT_METHODS = ['cash', 'orange_money', 'mtn_momo'];

    /**
     * Étapes que le LIVREUR peut faire avancer lui-même.
     * "delivering" est volontairement absent : il faut que le CLIENT
     * confirme la réception avant que le livreur puisse clôturer.
     */
    private const LIVREUR_NEXT = [
        'received' => 'preparing',
        'preparing' => 'delivering',
        'client_confirmed' => 'delivered',
    ];

    private function deliveryFeeFor($km): ?int
    {
        if ($km === null || ! is_numeric($km) || (float) $km < 0) {
            return null;
        }
        $km = (float) $km;
        if ($km < self::DELIVERY_FREE_BELOW_KM) {
            return 0;
        }

        return self::DELIVERY_BASE + (int) ceil($km - self::DELIVERY_FREE_BELOW_KM) * self::DELIVERY_PER_EXTRA_KM;
    }

    private function buildSummary(Product $product, ?array $customization): string
    {
        $c = $customization ?? [];
        if ($product->category === 'shawarma') {
            $legumes = ! empty($c['legumes']) ? implode(', ', (array) $c['legumes']) : 'sans légumes';
            $sauceLabels = ['Sans sauce', 'Léger', 'Normal', 'Généreux'];
            $sauceIdx = (int) ($c['sauce'] ?? 2);
            $sauce = $sauceLabels[$sauceIdx] ?? $sauceLabels[2];
            $piment = $c['piment'] ?? 1;
            $ketchup = ! empty($c['ketchup']) ? ' · Ketchup' : '';
            $taille = $c['taille'] ?? 'Normal';

            return "{$taille} · {$legumes} · Sauce: {$sauce} · Piment: {$piment}/3{$ketchup}";
        }

        if ($product->has_flavors) {
            return 'Saveur : ' . ($c['saveur'] ?? '—');
        }

        return 'Boisson standard';
    }

    private function priceFor(Product $product, ?array $customization): int
    {
        $price = (int) $product->base_price;
        if ($product->category === 'shawarma') {
            $taille = ($customization['taille'] ?? 'Normal');
            $price += self::SIZE_PRICES[$taille] ?? 0;
        }

        return $price;
    }

    /**
     * CLIENT : créer une commande. Le serveur recalcule tous les prix à
     * partir du catalogue — le total envoyé par le navigateur n'est
     * jamais utilisé tel quel.
     */
    public function store(Request $request): JsonResponse
    {
        $items = $request->input('items');
        $address = trim((string) $request->input('address', ''));
        $addressCoords = $request->input('addressCoords');
        $distanceKm = $request->input('distanceKm');
        $paymentMethod = (string) $request->input('paymentMethod', 'cash');

        if (! in_array($paymentMethod, self::PAYMENT_METHODS, true)) {
            $paymentMethod = 'cash';
        }
        if (! is_array($items) || count($items) === 0) {
            return response()->json(['error' => 'Le panier est vide.'], 400);
        }
        if ($address === '') {
            return response()->json(['error' => 'Adresse de livraison requise.'], 400);
        }

        $fee = $this->deliveryFeeFor($distanceKm);
        if ($fee === null) {
            return response()->json(['error' => 'Distance de livraison invalide.'], 400);
        }

        $subtotal = 0;
        $resolvedItems = [];
        foreach ($items as $raw) {
            $productId = is_array($raw) ? ($raw['productId'] ?? null) : null;
            $product = $productId ? Product::where('id', $productId)->where('active', true)->first() : null;
            if (! $product) {
                return response()->json(['error' => 'Produit inconnu : ' . ($productId ?? '?')], 400);
            }
            $qty = max(1, (int) ($raw['qty'] ?? 1));
            $customization = is_array($raw['customization'] ?? null) ? $raw['customization'] : [];
            $unitPrice = $this->priceFor($product, $customization);
            $subtotal += $unitPrice * $qty;
            $resolvedItems[] = [
                'productId' => $product->id,
                'category' => $product->category,
                'name' => $product->name,
                'qty' => $qty,
                'unitPrice' => $unitPrice,
                'summary' => $this->buildSummary($product, $customization),
            ];
        }

        $shawItems = array_values(array_filter($resolvedItems, fn ($i) => $i['category'] === 'shawarma'));
        $shawQty = array_sum(array_column($shawItems, 'qty'));
        $free = intdiv($shawQty, self::PROMO_QTY_PER_FREE);
        $promoDiscount = 0;
        if ($free > 0 && count($shawItems) > 0) {
            $promoDiscount = $free * min(array_column($shawItems, 'unitPrice'));
        }

        $total = $subtotal - $promoDiscount + $fee;
        $ref = '#' . random_int(1000, 9999);
        $now = (int) round(microtime(true) * 1000);

        $order = Order::create([
            'ref' => $ref,
            'client_id' => $request->user()->id,
            'items_json' => $resolvedItems,
            'subtotal' => $subtotal,
            'promo_discount' => $promoDiscount,
            'promo_free' => $free,
            'delivery_fee' => $fee,
            'distance_km' => $distanceKm,
            'address' => $address,
            'address_lat' => $addressCoords['lat'] ?? null,
            'address_lng' => $addressCoords['lng'] ?? null,
            'total' => $total,
            'payment_method' => $paymentMethod,
            'status' => 'received',
            'placed_at' => $now,
            'status_at' => $now,
        ]);

        return response()->json(['order' => $this->serialize($order)], 201);
    }

    private function serialize(Order $order): array
    {
        return [
            'id' => $order->id,
            'ref' => $order->ref,
            'items' => $order->items_json,
            'subtotal' => (int) $order->subtotal,
            'promoDiscount' => (int) $order->promo_discount,
            'promoFree' => (int) $order->promo_free,
            'deliveryFee' => (int) $order->delivery_fee,
            'distanceKm' => $order->distance_km !== null ? (float) $order->distance_km : null,
            'address' => $order->address,
            'addressCoords' => ($order->address_lat !== null && $order->address_lng !== null)
                ? ['lat' => (float) $order->address_lat, 'lng' => (float) $order->address_lng]
                : null,
            'total' => (int) $order->total,
            'paymentMethod' => $order->payment_method,
            'status' => $order->status,
            'placedAt' => (int) $order->placed_at,
            'statusAt' => (int) $order->status_at,
        ];
    }

    private function serializeWithClient(Order $order): array
    {
        $data = $this->serialize($order);
        $client = $order->client;
        $data['client'] = $client ? ['name' => $client->name, 'email' => $client->email] : null;

        return $data;
    }

    /** CLIENT : uniquement ses propres commandes. */
    public function mine(Request $request): JsonResponse
    {
        $orders = Order::where('client_id', $request->user()->id)
            ->orderByDesc('placed_at')
            ->get();

        return response()->json(['orders' => $orders->map(fn ($o) => $this->serialize($o))->all()]);
    }

    /** LIVREUR (+ admin) : commandes actives, tous clients confondus. */
    public function active(): JsonResponse
    {
        $orders = Order::with('client')
            ->where('status', '!=', 'delivered')
            ->orderBy('placed_at')
            ->get();

        return response()->json(['orders' => $orders->map(fn ($o) => $this->serializeWithClient($o))->all()]);
    }

    /** ADMIN : toutes les commandes. */
    public function index(): JsonResponse
    {
        $orders = Order::with('client')->orderByDesc('placed_at')->get();

        return response()->json(['orders' => $orders->map(fn ($o) => $this->serializeWithClient($o))->all()]);
    }

    /**
     * CLIENT : confirme avoir reçu sa commande. Seule action possible
     * quand le statut est "delivering" — c'est ce qui débloque ensuite
     * la confirmation finale du livreur.
     */
    public function confirmReceipt(Request $request, string $ref): JsonResponse
    {
        $order = Order::where('ref', $ref)->where('client_id', $request->user()->id)->first();
        if (! $order) {
            return response()->json(['error' => 'Commande introuvable.'], 404);
        }
        if ($order->status !== 'delivering') {
            return response()->json(['error' => "Cette commande n'est pas en attente de confirmation."], 400);
        }

        $order->status = 'client_confirmed';
        $order->status_at = (int) round(microtime(true) * 1000);
        $order->save();

        return response()->json(['order' => $this->serialize($order)]);
    }

    /**
     * LIVREUR (+ admin) : fait avancer le statut d'une étape. Bloqué sur
     * "delivering" tant que le client n'a pas confirmé la réception.
     */
    public function updateStatus(string $ref): JsonResponse
    {
        $order = Order::where('ref', $ref)->first();
        if (! $order) {
            return response()->json(['error' => 'Commande introuvable.'], 404);
        }

        if ($order->status === 'delivering') {
            return response()->json(['error' => 'En attente de la confirmation de réception du client.'], 400);
        }
        if (! isset(self::LIVREUR_NEXT[$order->status])) {
            return response()->json(['error' => 'Cette commande est déjà à son statut final.'], 400);
        }

        $order->status = self::LIVREUR_NEXT[$order->status];
        $order->status_at = (int) round(microtime(true) * 1000);
        $order->save();

        return response()->json(['order' => $this->serializeWithClient($order->fresh('client'))]);
    }

    /** ADMIN : statistiques agrégées. */
    public function statsSummary(): JsonResponse
    {
        $orders = Order::select('status', 'total')->get();
        $delivered = $orders->where('status', 'delivered')->count();

        return response()->json([
            'totalOrders' => $orders->count(),
            'totalRevenue' => (int) $orders->sum('total'),
            'delivered' => $delivered,
            'inProgress' => $orders->count() - $delivered,
        ]);
    }
}
