<?php
if (!function_exists('activeRoute')) {
    function activeRoute($route, $isClass = false): string
    {
        $requestUrl = request()->fullUrl() === $route ? true : false;
        if ($isClass) {
            return $requestUrl ? $isClass : '';
        } else {
            return $requestUrl ? 'active' : '';
        }
    }
}
