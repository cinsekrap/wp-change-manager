<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Keys whose values are stored encrypted at rest.
     */
    protected static array $encryptedKeys = [
        'mail_password',
        'entra_client_secret',
    ];

    /**
     * Get a setting value by key. Decrypts if sensitive.
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return self::shouldEncrypt($key)
            ? self::decryptValue($setting->value)
            : $setting->value;
    }

    /**
     * Set (upsert) a setting value. Encrypts if sensitive.
     */
    public static function set(string $key, $value): void
    {
        $storedValue = self::shouldEncrypt($key) && $value !== null
            ? Crypt::encryptString($value)
            : $value;

        static::updateOrCreate(['key' => $key], ['value' => $storedValue]);
    }

    /**
     * Get multiple settings as a keyed collection. Decrypts sensitive values.
     */
    public static function getMany(array $keys): \Illuminate\Support\Collection
    {
        $settings = static::whereIn('key', $keys)->pluck('value', 'key');

        return $settings->map(function ($value, $key) {
            return self::shouldEncrypt($key)
                ? self::decryptValue($value)
                : $value;
        });
    }

    /**
     * Check if a key should be encrypted.
     */
    protected static function shouldEncrypt(string $key): bool
    {
        return in_array($key, static::$encryptedKeys);
    }

    /**
     * Get email template content (subject + body) with placeholders replaced.
     *
     * Falls back to defaults from config/email-templates.php when no
     * custom value has been saved in the settings table.
     */
    public static function getEmailContent(string $template, array $replacements): array
    {
        $defaults = config("email-templates.{$template}");

        $subject = static::get("email_{$template}_subject") ?? $defaults['subject'];
        $body = static::get("email_{$template}_body") ?? $defaults['body'];

        foreach ($replacements as $key => $value) {
            $subject = str_replace('{' . $key . '}', $value ?? '', $subject);
            $body = str_replace('{' . $key . '}', $value ?? '', $body);
        }

        return ['subject' => $subject, 'body' => $body];
    }

    /**
     * Safely decrypt a value. Returns null if decryption fails
     * (e.g. value was stored before encryption was enabled).
     */
    protected static function decryptValue($value)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            // Value may have been stored unencrypted before this feature was added.
            // Return as-is rather than breaking the app.
            return $value;
        }
    }
}
