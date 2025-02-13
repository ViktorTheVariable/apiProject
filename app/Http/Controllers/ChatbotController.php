<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class ChatbotController extends Controller
{
    public function chat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        try {
            $response = Http::post('http://localhost:11434/api/generate', [
                'model' => 'mistral',
                'prompt' => $request->message,
                'stream' => false
            ]);

            if ($response->successful()) {
                $aiResponse = $response->json()['response'];
                return response()->json(['response' => $aiResponse]);
            } else {
                return response()->json(['error' => 'API request failed'], $response->status());
            }

        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }
}