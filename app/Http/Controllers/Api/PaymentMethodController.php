<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PaymentMethodController extends Controller
{
    public function index(): JsonResponse
    {
        $orange = config('shawarma.payment.orange_money');
        $mtn = config('shawarma.payment.mtn_momo');

        return response()->json([
            'methods' => [
                ['id' => 'cash', 'label' => 'Espèces à la livraison'],
                ['id' => 'orange_money', 'label' => $orange['label'], 'depositName' => $orange['name'], 'depositNumber' => $orange['number']],
                ['id' => 'mtn_momo', 'label' => $mtn['label'], 'depositName' => $mtn['name'], 'depositNumber' => $mtn['number']],
            ],
        ]);
    }
}
