<?php
namespace App\Helpers;
use App\Models\Link;
use App\Helpers\BaseHelper;

class LinkHelper {
    static public function checkIfAlreadyShortened($long_link) {
        /**
         * Provided a long link (string),
         * detect whether the link belongs to an URL shortener.
         * @return boolean
         */
        $shortener_domains = [
            'polr.me',
            'bit.ly',
            'is.gd',
            'tiny.cc',
            'adf.ly',
            'ur1.ca',
            'goo.gl',
            'ow.ly',
            'j.mp',
            't.co',
            env('APP_ADDRESS')
        ];

        foreach ($shortener_domains as $shortener_domain) {
            if (strstr($long_link, '://' . $shortener_domain)) {
                return true;
            }
        }

        return false;
    }

    public static function linkExists($link_ending)
    {
        $link = Link::where('short_url', $link_ending)->first();
        return $link ?: false;
    }

    public static function longLinkExists($long_url, $username = false)
    {
        $query = Link::longUrl($long_url)
            ->where('is_custom', 0)
            ->where(function ($q) {
                $q->whereNull('secret_key')->orWhere('secret_key', '');
            });

        if ($username === null) {
            $link = $query->where(function ($q) {
                $q->whereNull('creator')->orWhere('creator', '');
            })->first();
        } elseif ($username !== false) {
            $link = $query->where('creator', $username)->first();
        } else {
            $link = $query->first();
        }

        return $link ?: false; // Return the full model
    }

    public static function secretLinkExists($long_url, $username = false)
    {
        $query = Link::longUrl($long_url)
            ->where('is_custom', 0)
            ->whereNotNull('secret_key');

        if ($username === null) {
            $query->where(function ($q) {
                $q->whereNull('creator')->orWhere('creator', '');
            });
        } elseif ($username !== false) {
            $query->where('creator', $username);
        }

        $link = $query->first();
        return $link ?: false; // Return the full model
    }

    public static function validateEnding($link_ending)
    {
        return preg_match('/^[a-zA-Z0-9-_]+$/', $link_ending);
    }

    static public function findPseudoRandomEnding() {
        /**
         * Return an available pseudorandom string of length _PSEUDO_RANDOM_KEY_LENGTH,
         * as defined in .env
         * Edit _PSEUDO_RANDOM_KEY_LENGTH in .env if you wish to increase the length
         * of the pseudorandom string generated.
         * @return string
         */

        $length = env('_PSEUDO_RANDOM_KEY_LENGTH', 6);
        do {
            $pr_str = str_random($length);
        } while (self::linkExists($pr_str));

        return $pr_str;
    }

    static public function findSuitableEnding() {
        /**
         * Provided an in-use link ending (string),
         * find the next available base-32/62 ending.
         * @return string
         */
        $base = env('POLR_BASE');

        $link = Link::where('is_custom', 0)
            ->orderBy('created_at', 'desc')
            ->first();

        $base10_val = $link ? BaseHelper::toBase10($link->short_url, $base) + 1 : 0;

        do {
            $base_x_val = BaseHelper::toBase($base10_val, $base);
            $base10_val++;
        } while (self::linkExists($base_x_val));

        return $base_x_val;
    }

    public static function findByEnding($ending)
    {
        return Link::where('short_url', $ending)->first();
    }
}
