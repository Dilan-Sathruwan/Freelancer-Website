<?php
/**
 * Image and Asset Resolver Helper
 * Ensures 100% valid image paths, eliminates 404 network errors,
 * and provides modern SVG vector placeholders when physical images are missing.
 */

if (!function_exists('getGigPlaceholderUrl')) {
    /**
     * Returns the standard vector SVG placeholder for gigs and services
     */
    function getGigPlaceholderUrl(string $rootPrefix = './'): string {
        $rootPrefix = rtrim($rootPrefix, '/') . '/';
        return $rootPrefix . 'assets/img/placeholder-gig.svg';
    }
}

if (!function_exists('getAvatarPlaceholderUrl')) {
    /**
     * Returns the standard vector SVG placeholder for user avatars
     */
    function getAvatarPlaceholderUrl(string $rootPrefix = './'): string {
        $rootPrefix = rtrim($rootPrefix, '/') . '/';
        return $rootPrefix . 'assets/img/placeholder-avatar.svg';
    }
}

if (!function_exists('getInitialsAvatarUri')) {
    /**
     * Generates a modern, self-contained SVG Data URI avatar with user initials
     * Zero HTTP requests, instantaneous rendering, perfectly responsive.
     */
    function getInitialsAvatarUri(string $name): string {
        $name = trim($name);
        $words = preg_split('/\s+/', $name);
        $initials = '';
        if (!empty($words[0])) {
            $initials .= mb_strtoupper(mb_substr($words[0], 0, 1));
        }
        if (count($words) > 1 && !empty($words[count($words) - 1])) {
            $initials .= mb_strtoupper(mb_substr($words[count($words) - 1], 0, 1));
        }
        if (empty($initials)) {
            $initials = 'U';
        }

        // Deterministic gradient hue based on name
        $hash = crc32($name);
        $gradients = [
            ['#4f46e5', '#7c3aed'], // Indigo - Violet
            ['#2563eb', '#06b6d4'], // Blue - Cyan
            ['#059669', '#10b981'], // Emerald - Teal
            ['#d97706', '#f59e0b'], // Amber
            ['#e11d48', '#ec4899'], // Rose - Pink
            ['#7c3aed', '#c084fc']  // Purple
        ];
        $grad = $gradients[abs($hash) % count($gradients)];

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100">'
            . '<defs><linearGradient id="g" x1="0%" y1="0%" x2="100%" y2="100%">'
            . '<stop offset="0%" stop-color="' . $grad[0] . '"/>'
            . '<stop offset="100%" stop-color="' . $grad[1] . '"/>'
            . '</linearGradient></defs>'
            . '<rect width="100" height="100" rx="50" fill="url(#g)"/>'
            . '<text x="50" y="60" text-anchor="middle" fill="#ffffff" font-family="-apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif" font-size="38" font-weight="700">' . htmlspecialchars($initials) . '</text>'
            . '</svg>';

        return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
    }
}

if (!function_exists('resolveGigImage')) {
    /**
     * Resolves a gig image path to a valid, existing URL or SVG placeholder.
     * 
     * @param string|null $image The database image field value
     * @param string $rootPrefix Relative path prefix to workspace root ('./' or '../')
     * @return string Valid image path or vector SVG placeholder
     */
    function resolveGigImage(?string $image, string $rootPrefix = './'): string {
        $rootPrefix = rtrim($rootPrefix, '/') . '/';
        
        if (!empty($image)) {
            // If already absolute URL or data URI
            if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://') || str_starts_with($image, 'data:image/')) {
                return $image;
            }
            
            $cleanName = basename($image);
            $docRoot = dirname(__DIR__); // Points to project root
            
            // Check in uploads/gigs
            if (is_file($docRoot . '/uploads/gigs/' . $cleanName)) {
                return $rootPrefix . 'uploads/gigs/' . $cleanName;
            }

            // Check in root directory
            if (is_file($docRoot . '/' . $cleanName)) {
                return $rootPrefix . $cleanName;
            }
            
            // Check if relative path exists directly
            $directPath = $docRoot . '/' . ltrim($image, './');
            if (is_file($directPath)) {
                return $rootPrefix . ltrim($image, './');
            }
        }
        
        // Return crisp modern SVG placeholder
        return getGigPlaceholderUrl($rootPrefix);
    }
}

if (!function_exists('resolveAvatarUrl')) {
    /**
     * Resolves a user profile picture to an existing, valid URL or placeholder.
     * 
     * @param string|null $avatar The database profile_picture value
     * @param string $rootPrefix Relative path prefix to workspace root ('./' or '../')
     * @param string $name Optional name to generate initial-based avatar
     * @return string Valid avatar path or vector SVG placeholder
     */
    function resolveAvatarUrl(?string $avatar, string $rootPrefix = './', string $name = ''): string {
        $rootPrefix = rtrim($rootPrefix, '/') . '/';
        
        if (!empty($avatar)) {
            // If absolute URL or data URI
            if (str_starts_with($avatar, 'http://') || str_starts_with($avatar, 'https://') || str_starts_with($avatar, 'data:image/')) {
                return $avatar;
            }
            
            $cleanName = basename($avatar);
            $docRoot = dirname(__DIR__);
            
            // Check in uploads/proPic
            if (is_file($docRoot . '/uploads/proPic/' . $cleanName)) {
                return $rootPrefix . 'uploads/proPic/' . $cleanName;
            }

            // Check in root
            if (is_file($docRoot . '/' . $cleanName)) {
                return $rootPrefix . $cleanName;
            }
            
            // Check direct relative path
            $directPath = $docRoot . '/' . ltrim($avatar, './');
            if (is_file($directPath)) {
                return $rootPrefix . ltrim($avatar, './');
            }
        }
        
        // If a name was supplied, generate an initials avatar data URI
        if (!empty($name)) {
            return getInitialsAvatarUri($name);
        }
        
        // Otherwise return crisp SVG avatar placeholder
        return getAvatarPlaceholderUrl($rootPrefix);
    }
}
