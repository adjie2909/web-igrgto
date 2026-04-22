<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;

class PreventDuplicateSubmissions
{
    private const LOCK_TTL_SECONDS = 30;
    private const RECENT_TTL_SECONDS = 5;

    public function handle(Request $request, Closure $next): Response
    {
        if (!in_array(strtoupper($request->method()), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        $signature = $this->buildSignature($request);
        $recentKey = "dedupe:recent:{$signature}";
        $lockKey = "dedupe:lock:{$signature}";

        if (Cache::has($recentKey)) {
            return $this->duplicateResponse($request);
        }

        $lock = null;
        $acquired = false;

        try {
            $lock = Cache::lock($lockKey, self::LOCK_TTL_SECONDS);
            $acquired = (bool) $lock->get();
        } catch (Throwable) {
            // Some cache stores may not support locks; fallback to best-effort add.
            $acquired = Cache::add($lockKey, true, self::LOCK_TTL_SECONDS);
        }

        if (!$acquired) {
            return $this->duplicateResponse($request);
        }

        try {
            return $next($request);
        } finally {
            Cache::put($recentKey, true, self::RECENT_TTL_SECONDS);

            try {
                if ($lock) {
                    $lock->release();
                } else {
                    Cache::forget($lockKey);
                }
            } catch (Throwable) {
                // ignore
            }
        }
    }

    private function duplicateResponse(Request $request): Response
    {
        $message = 'Request sedang diproses. Mohon tunggu, jangan klik berkali-kali.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 409);
        }

        return back()
            ->withInput()
            ->with('error', $message);
    }

    private function buildSignature(Request $request): string
    {
        $userId = (int) (optional($request->user())->id ?? 0);
        $actor = $userId > 0 ? "u:{$userId}" : 'g:' . (string) $request->ip();

        $payload = [
            'path' => '/' . ltrim((string) $request->path(), '/'),
            'method' => strtoupper((string) $request->method()),
            'query' => $request->query(),
            'input' => $request->except(['_token', '_method']),
            'files' => $this->summarizeFiles($request),
        ];

        $normalized = $this->normalize($payload);
        $json = json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return sha1($actor . '|' . ($json ?: ''));
    }

    private function summarizeFiles(Request $request): array
    {
        $files = $request->allFiles();
        if (empty($files)) {
            return [];
        }

        $walk = function ($value) use (&$walk) {
            if ($value instanceof UploadedFile) {
                return [
                    'name' => $value->getClientOriginalName(),
                    'size' => $value->getSize(),
                    'mime' => $value->getClientMimeType(),
                ];
            }

            if (is_array($value)) {
                return array_map($walk, $value);
            }

            return null;
        };

        return $walk($files);
    }

    private function normalize(mixed $value): mixed
    {
        if (is_array($value)) {
            ksort($value);
            foreach ($value as $k => $v) {
                $value[$k] = $this->normalize($v);
            }
            return $value;
        }

        if (is_bool($value) || is_int($value) || is_float($value) || is_string($value) || $value === null) {
            return $value;
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        return (string) json_encode($value);
    }
}

