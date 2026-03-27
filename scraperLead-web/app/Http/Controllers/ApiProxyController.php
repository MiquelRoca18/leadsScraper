<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

class ApiProxyController extends Controller
{
    private const ALLOWED_METHODS = ['GET', 'POST', 'DELETE'];
    private const ALLOWED_HEADERS = ['accept', 'content-type', 'x-requested-with', 'x-csrf-token', 'x-xsrf-token'];
    private const ALLOWED_PATH_PATTERNS = [
        'proxy/status',
        'proxy/capacity',
        'search',
        'leads',
        'leads/{leadId}',
        'jobs/{jobId}',
        'export/{jobId}',
    ];

    public function proxy(Request $request, string $path)
    {
        $method = strtoupper($request->method());
        if (! in_array($method, self::ALLOWED_METHODS, true)) {
            return response()->json(['message' => 'Method not allowed'], 405);
        }

        $resolvedPath = $this->resolvePathParameters($request, $path);
        if (! $this->isAllowedPath($resolvedPath)) {
            return response()->json(['message' => 'Path not allowed'], 404);
        }

        foreach ($request->headers->all() as $name => $_) {
            $lower = strtolower($name);

            if ($lower === 'authorization' || $lower === 'cookie' || str_starts_with($lower, 'x-forwarded-')) {
                return response()->json(['message' => "Header not allowed: {$lower}"], 400);
            }

            if (str_starts_with($lower, 'x-') && ! in_array($lower, self::ALLOWED_HEADERS, true)) {
                return response()->json(['message' => "Header not allowed: {$lower}"], 400);
            }
        }

        $apiUrl = rtrim((string) config('services.mapleads.api_url'), '/');
        if (blank($apiUrl)) {
            return response()->json(['message' => 'Upstream API unavailable'], 502);
        }

        $targetUrl = "{$apiUrl}/api/{$resolvedPath}";

        if ($request->getQueryString()) {
            $targetUrl .= '?' . $request->getQueryString();
        }

        $headers = collect($request->headers->all())
            ->mapWithKeys(fn ($value, $name) => [strtolower($name) => is_array($value) ? $value[0] : $value])
            ->only(self::ALLOWED_HEADERS)
            ->toArray();

        $pendingRequest = Http::withHeaders($headers)->timeout(60);
        try {
            $response = match ($method) {
                'GET' => $pendingRequest->get($targetUrl),
                'POST' => $pendingRequest
                    ->withBody($request->getContent(), $request->header('Content-Type', 'application/json'))
                    ->post($targetUrl),
                'DELETE' => $pendingRequest->delete($targetUrl),
            };
        } catch (Throwable) {
            return response()->json(['message' => 'Upstream API unavailable'], 502);
        }

        if ($response->failed()) {
            return response()->json(['message' => 'Upstream API unavailable'], 502);
        }

        return response($response->body(), $response->status())
            ->header('Content-Type', $response->header('Content-Type', 'application/json'));
    }

    private function resolvePathParameters(Request $request, string $path): string
    {
        return (string) preg_replace_callback(
            '/\{([^}]+)\}/',
            fn (array $matches) => (string) $request->route($matches[1], $matches[0]),
            $path
        );
    }

    private function isAllowedPath(string $resolvedPath): bool
    {
        foreach (self::ALLOWED_PATH_PATTERNS as $pattern) {
            $regex = '#^' . preg_replace('/\{[^}]+\}/', '[^/]+', preg_quote($pattern, '#')) . '$#';
            if (preg_match($regex, $resolvedPath) === 1) {
                return true;
            }
        }

        return false;
    }
}
