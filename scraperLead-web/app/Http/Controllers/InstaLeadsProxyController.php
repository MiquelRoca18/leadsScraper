<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Proxy controller for InstaLeads backend (http://localhost:8002).
 *
 * Same security pattern as ApiProxyController but targets the Instagram scraper service.
 * Handles all /api/instagram/* routes from the frontend.
 */
class InstaLeadsProxyController extends Controller
{
    private const ALLOWED_METHODS = ['GET', 'POST', 'DELETE'];
    private const ALLOWED_HEADERS = ['accept', 'content-type', 'x-requested-with', 'x-csrf-token', 'x-xsrf-token'];
    private const ALLOWED_PATH_PATTERNS = [
        'instagram/health',
        'instagram/debug/last',
        'instagram/diagnose',
        'instagram/profile/{username}',
        'instagram/search',
        'instagram/jobs',
        'instagram/jobs/{jobId}',
        'instagram/leads',
        'instagram/export/{jobId}',
        'proxy/status',
    ];

    public function proxy(Request $request)
    {
        $path = (string) ($request->route()?->defaults['path'] ?? '');
        if ($path === '') {
            return response()->json(['message' => 'Path not allowed'], 404);
        }

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

            // Mismo patrón que ApiProxyController: aceptar cookies/contexto local
            // para que el proxy no rompa llamadas del navegador, pero nunca reenviar.
            if ($lower === 'authorization' || $lower === 'cookie' || str_starts_with($lower, 'x-forwarded-')) {
                continue;
            }

            if (str_starts_with($lower, 'x-') && ! in_array($lower, self::ALLOWED_HEADERS, true)) {
                return response()->json(['message' => "Header not allowed: {$lower}"], 400);
            }
        }

        $apiUrl = rtrim((string) config('services.instaleads.api_url'), '/');
        if (blank($apiUrl)) {
            return response()->json(['message' => 'InstaLeads API unavailable'], 502);
        }

        $targetUrl = "{$apiUrl}/api/{$resolvedPath}";

        if ($request->getQueryString()) {
            $targetUrl .= '?' . $request->getQueryString();
        }

        $headers = collect($request->headers->all())
            ->mapWithKeys(fn ($value, $name) => [strtolower($name) => is_array($value) ? $value[0] : $value])
            ->only(self::ALLOWED_HEADERS)
            ->toArray();

        $pendingRequest = Http::withHeaders($headers)->timeout(25);
        try {
            $response = match ($method) {
                'GET' => $pendingRequest->get($targetUrl),
                'POST' => $pendingRequest
                    ->withBody($request->getContent(), $request->header('Content-Type', 'application/json'))
                    ->post($targetUrl),
                'DELETE' => $pendingRequest->delete($targetUrl),
            };
        } catch (Throwable) {
            return response()->json(['message' => 'InstaLeads API unavailable'], 502);
        }

        if ($response->failed()) {
            return response()->json(['message' => 'InstaLeads API unavailable'], 502);
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
            $wildcard = (string) preg_replace('/\{[^}]+\}/', '*', $pattern);
            if (fnmatch($wildcard, $resolvedPath)) {
                return true;
            }
        }

        return false;
    }
}
