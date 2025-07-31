<?php
namespace App\Factories;

use App\Models\Link;
use App\Helpers\CryptoHelper;
use App\Helpers\LinkHelper;


class LinkFactory {
    const MAXIMUM_LINK_LENGTH = 65535;

    private static function formatLink($link_ending, $secret_ending=false) {
        /**
        * Given a link ending and a boolean indicating whether a secret ending is needed,
        * return a link formatted with app protocol, app address, and link ending.
        * @param string $link_ending
        * @param boolean $secret_ending
        * @return string
        */
        $short_url = env('APP_PROTOCOL') . env('APP_ADDRESS') . '/' . $link_ending;

        if ($secret_ending) {
            $short_url .= '/' . $secret_ending;
        }

        return $short_url;
    }

    private static function formatLinkQueryParamFormat($link_ending, $secret_ending=false) {
        /**
        * Given a link ending and a boolean indicating whether a secret ending is needed,
        * return a link formatted with app protocol, app address, and link ending.
        * @param string $link_ending
        * @param boolean $secret_ending
        * @return string
        */
        $short_url = env('APP_PROTOCOL') . env('APP_ADDRESS') . '/r?k=' . $link_ending;

        if ($secret_ending) {
            $short_url .= '/' . $secret_ending;
        }

        return $short_url;
    }

    public static function createLink($long_url, $is_secret=false, $custom_ending=null, $link_ip='127.0.0.1', $creator=false, $return_object=false, $is_api=false) {
        /**
        * Given parameters needed to create a link, generate appropriate ending and
        * return formatted link.
        *
        * @param string $custom_ending
        * @param boolean (optional) $is_secret
        * @param string (optional) $custom_ending
        * @param string $link_ip
        * @param string $creator
        * @param bool $return_object
        * @param bool $is_api
        * @return string $formatted_link
        */

        if (strlen($long_url) > self::MAXIMUM_LINK_LENGTH) {
            // If $long_url is longer than the maximum length, then
            // throw an Exception
            throw new \Exception('Sorry, but your link is longer than the
                maximum length allowed.');
        }
    
        $is_already_short = LinkHelper::checkIfAlreadyShortened($long_url);

        if ($is_already_short) {
            throw new \Exception('Sorry, but your link already
                looks like a shortened URL.');
        }
    
        //  Reuse existing link if not secret
        if (!$is_secret && empty($custom_ending)) {
            $existingLink = LinkHelper::longLinkExists($long_url, $creator);
            if ($existingLink) {
                return $return_object ? $existingLink : [
                    'formatted_link' => self::formatLink($existingLink->short_url),
                    'formatted_link_query' => self::formatLinkQueryParamFormat($existingLink->short_url),
                    'key' => $existingLink->short_url,
                ];
            }
        }
    
        //  Reuse existing secret link if secret
        if ($is_secret && empty($custom_ending)) {
            $existingSecretLink = LinkHelper::secretLinkExists($long_url, $creator);
            if ($existingSecretLink) {
                return $return_object ? $existingSecretLink : [
                    'formatted_link' => self::formatLink($existingSecretLink->short_url, $existingSecretLink->secret_key),
                    'formatted_link_query' => self::formatLinkQueryParamFormat($existingSecretLink->short_url, $existingSecretLink->secret_key),
                    'key' => $existingSecretLink->short_url,
                ];
            }
        }
    
        // Custom ending handling
        if (!empty($custom_ending)) {
            if (!LinkHelper::validateEnding($custom_ending)) {
                throw new \Exception('Custom endings can only contain alphanumeric characters, hyphens, and underscores.');
            }
    
            if (LinkHelper::linkExists($custom_ending)) {
                throw new \Exception('This URL ending is already in use.');
            }
    
            $link_ending = $custom_ending;
        } else {
            $link_ending = env('SETTING_PSEUDORANDOM_ENDING')
                ? LinkHelper::findPseudoRandomEnding()
                : LinkHelper::findSuitableEnding();
        }
    
        $link = new Link;
        $link->short_url = $link_ending;
        $link->long_url  = $long_url;
        $link->ip        = $link_ip;
        $link->is_custom = $custom_ending != null;

        $link->is_api    = $is_api;
    
        if ($creator) {
            $link->creator = $creator;
        }
    
        $secret_key = false;
        if ($is_secret) {
            $rand_bytes_num = intval(env('POLR_SECRET_BYTES'));
            $secret_key = CryptoHelper::generateRandomHex($rand_bytes_num);
            $link->secret_key = $secret_key;
        }
    
        $link->save();
    
        return $return_object ? $link : [
            'formatted_link' => self::formatLink($link_ending, $secret_key),
            'formatted_link_query' => self::formatLinkQueryParamFormat($link_ending, $secret_key),
            'key' => $link_ending,
        ];
    }
    
}
