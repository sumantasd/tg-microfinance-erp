<?php

namespace App\Http\Middleware;

use App\Models\ApiIdempotencyKey;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureIdempotentRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('x-idempotency-key')
            ?? $request->header('X-Idempotency-Key')
            ?? $request->header('idempotency-key')
            ?? $request->header('Idempotency-Key')
            ?? $request->input('idempotency_key')
            ?? $request->input('offline_sync_id');

        if (is_array($key)) {
            $key = reset($key);
        }

        if (empty($key)) {
            return $next($request);
        }

        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        $endpoint = $request->method() . ' ' . $request->path();
        $payloadToHash = json_encode([
            'path' => $request->path(),
            'query' => $request->query(),
            'body' => $request->except(['idempotency_key', 'offline_sync_id']),
        ]);
        $requestHash = hash('sha256', $payloadToHash);

        $existing = ApiIdempotencyKey::where('user_id', $user->id)
            ->where('endpoint', $endpoint)
            ->where('idempotency_key', (string) $key)
            ->first();

        if ($existing) {
            if ($existing->request_hash !== $requestHash) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Idempotency key reused with different request parameters',
                ], 422);
            }

            if ($existing->status === 'processing') {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'A request with this idempotency key is currently processing',
                ], 409);
            }

            $decodedBody = json_decode($existing->response_body, true);
            return new JsonResponse($decodedBody ?? [], $existing->response_code ?? 200);
        }

        try {
            $record = ApiIdempotencyKey::create([
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'branch_id' => $user->branch_id,
                'endpoint' => $endpoint,
                'idempotency_key' => (string) $key,
                'request_hash' => $requestHash,
                'status' => 'processing',
            ]);
        } catch (\Exception $e) {
            // Race condition fallback check
            $existing = ApiIdempotencyKey::where('user_id', $user->id)
                ->where('endpoint', $endpoint)
                ->where('idempotency_key', (string) $key)
                ->first();

            if ($existing) {
                if ($existing->request_hash !== $requestHash) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Idempotency key reused with different request parameters',
                    ], 422);
                }
                if ($existing->status === 'processing') {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'A request with this idempotency key is currently processing',
                    ], 409);
                }
                $decodedBody = json_decode($existing->response_body, true);
                return new JsonResponse($decodedBody ?? [], $existing->response_code ?? 200);
            }

            return $next($request);
        }

        $response = $next($request);

        if ($response instanceof JsonResponse || $response->headers->get('Content-Type') === 'application/json') {
            $statusCode = $response->getStatusCode();
            $content = $response->getContent();

            if ($statusCode >= 200 && $statusCode < 300) {
                $record->update([
                    'status' => 'completed',
                    'response_code' => $statusCode,
                    'response_body' => $content,
                ]);
            } else {
                $record->delete();
            }
        } else {
            $record->delete();
        }

        return $response;
    }
}
