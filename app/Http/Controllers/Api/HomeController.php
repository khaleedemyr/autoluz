<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HomeController as WebHomeController;
use App\Support\NavBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function show(WebHomeController $home): JsonResponse
    {
        return response()->json($home->buildPayload() + [
            'nav' => NavBuilder::build(),
            'locale' => app()->getLocale(),
        ]);
    }

    public function videos(Request $request, WebHomeController $home): JsonResponse
    {
        return $home->videosFeed($request);
    }
}
