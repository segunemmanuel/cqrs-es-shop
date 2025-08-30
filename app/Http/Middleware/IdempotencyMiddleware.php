<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IdempotencyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->isMethod('post')) {
            return $next($request);
        }

        $key = $request->header('Idempotency-Key');
        if (! $key) {
            return $next($request);
        }

        $hash = hash('sha256', $request->getContent());

        $existing = DB::table('idempotency_keys')->where('key', $key)->first();
        if ($existing) {
            // If body matches, replay prior response
            if ($existing->request_hash === $hash) {
                return response($existing->response_body, (int)$existing->status_code, [
                    'Content-Type' => 'application/json',
                    'X-Idempotent-Replay' => 'true',
                ]);
            }
            // Key reused with a different body → conflict
            return response()->json([
                'error' => 'idempotency_key_conflict',
                'message' => 'Idempotency-Key was used for a different payload'
            ], 409);
        }

        // Reserve key before executing
        DB::table('idempotency_keys')->insert([
            'key'          => $key,
            'request_hash' => $hash,
            'response_body'=> null,
            'status_code'  => null,
        ]);

        $response = $next($request);

        // Persist response
        DB::table('idempotency_keys')->where('key', $key)->update([
            'response_body' => $response->getContent(),
            'status_code'   => $response->getStatusCode(),
        ]);

        return $response;
    }
}
