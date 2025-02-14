<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Models\ChatHistory;

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

    public function chatAuth(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:1000',
            'session_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $userId = $request->user()->id;
        $sessionId = $request->session_id;

        try {
            $previousChats = ChatHistory::where('user_id', $userId)
                ->where('session_id', $sessionId)
                ->orderBy('created_at', 'asc')
                ->get();

            $previousMessages = $previousChats
                ->map(fn($chat) => [
                    ['role' => 'user', 'content' => $chat->user_message],
                    ['role' => 'assistant', 'content' => $chat->bot_response],
                ])
                ->flatten(1)
                ->toArray();

            $messages = array_merge($previousMessages, [
                ['role' => 'user', 'content' => $request->message]
            ]);

            $response = Http::post('http://localhost:11434/api/chat', [
                'model' => 'mistral',
                'messages' => $messages,
                'stream' => false,
            ]);

            if ($response->successful()) {
                $aiResponse = $response->json()['message']['content'];

                ChatHistory::create([
                    'user_id' => $userId,
                    'session_id' => $sessionId,
                    'user_message' => $request->message,
                    'bot_response' => $aiResponse,
                ]);

                return response()->json([
                    'response' => $aiResponse,
                    'session_id' => $sessionId
                ]);
            } else {
                return response()->json(['error' => 'API request failed'], $response->status());
            }

        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

}