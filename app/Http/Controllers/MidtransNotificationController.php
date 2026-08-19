<?php

namespace App\Http\Controllers;

use App\Services\ShopOrderService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;

class MidtransNotificationController extends Controller
{
    public function store(Request $request, ShopOrderService $orders): Response
    {
        try {
            $orders->handleNotification($request->all());
        } catch (RuntimeException $e) {
            return response($e->getMessage(), 403);
        }

        return response('OK');
    }
}
