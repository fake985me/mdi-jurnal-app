<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DeliveryNoteService;

class DeliveryNoteController extends Controller
{
    public function show(string $key, DeliveryNoteService $service)
    {
        return response()->json($service->data($key));
    }
}
