<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Models\ChatHistory;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    public function chat(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        try {
            // if validation passes, send the message to the chatbot
            $response = Http::post('http://localhost:11434/api/generate', [
                'model' => 'mistral',
                'prompt' => $request->message,
                'stream' => false
            ]);

            // if the request is successful, return the response in json format
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
            'session_id' => 'nullable|string',
        ]);

        // if none of the above are filled or invalid, return an error
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $userId = $request->user()->id;
        $sessionId = $request->session_id;

        try {
            if (!$sessionId) {
                // For a new conversation, generate a new session ID
                $sessionId = (string) Str::uuid();
                $messages = [['role' => 'user', 'content' => $request->message]];
            } else {
                // For an existing conversation, retrieve previous chats
                $previousChats = ChatHistory::where('user_id', $userId)
                    ->where('session_id', $sessionId)
                    ->orderBy('created_at', 'asc')
                    ->get();

                // Organizes past messages into a format the AI can understand
                $previousMessages = $previousChats
                    ->map(fn($chat) => [
                        ['role' => 'user', 'content' => $chat->user_message],
                        ['role' => 'assistant', 'content' => $chat->bot_response],
                    ])
                    ->flatten(1)
                    ->toArray();

                // Add the new user message to the conversation history
                $messages = array_merge($previousMessages, [
                    ['role' => 'user', 'content' => $request->message]
                ]);
            }

            $response = Http::post('http://localhost:11434/api/chat', [
                'model' => 'mistral',
                'messages' => $messages,
                'stream' => false,
            ]);

            // If the AI responds successfully
            if ($response->successful()) {
                $aiResponse = $response->json()['message']['content'];

                // Save this exchange to the chat history
                ChatHistory::create([
                    'user_id' => $userId,
                    'session_id' => $sessionId,
                    'user_message' => $request->message,
                    'bot_response' => $aiResponse,
                ]);

                // Send the AI's response back to the user
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
