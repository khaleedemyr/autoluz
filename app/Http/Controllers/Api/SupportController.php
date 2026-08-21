<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SupportController as WebSupportController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function current(Request $request, WebSupportController $web): JsonResponse
    {
        return $web->current($request);
    }

    public function store(Request $request, WebSupportController $web): JsonResponse
    {
        return $web->store($request);
    }

    public function poll(Request $request, WebSupportController $web): JsonResponse
    {
        return $web->poll($request);
    }

    public function privacy(): JsonResponse
    {
        return response()->json(['page' => trans('legal.privacy')]);
    }

    public function faq(): JsonResponse
    {
        return response()->json(['page' => trans('legal.faq')]);
    }
}
