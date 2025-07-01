<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Http\Redirect;

use App\Models\Link;
use App\Factories\LinkFactory;
use App\Helpers\CryptoHelper;
use App\Helpers\LinkHelper;
use App\Helpers\ClickHelper;

class LinkController extends Controller {
    /**
     * Show the admin panel, and process admin AJAX requests.
     *
     * @return Response
     */

    private function renderError($message) {
        return redirect(route('index'))->with('error', $message);
    }

    public function performShorten(Request $request) {
        if (env('SETTING_SHORTEN_PERMISSION') && !self::isLoggedIn()) {
            return redirect(route('index'))->with('error', 'You must be logged in to shorten links.');
        }

        // Validate URL form data
        $this->validate($request, [
            'link-url' => 'required|url',
            'custom-ending' => 'alpha_dash'
        ]);

        $long_url = $request->input('link-url');
        $custom_ending = $request->input('custom-ending');
        $is_secret = ($request->input('options') == "s" ? true : false);
        $creator = session('username');
        $link_ip = $request->ip();

        try {
            $short_url = LinkFactory::createLink($long_url, $is_secret, $custom_ending, $link_ip, $creator);
        }
        catch (\Exception $e) {
            return self::renderError($e->getMessage());
        }

        return view('shorten_result', ['short_url' => $short_url['formatted_link']]);
    }

    /**
     * Handles redirect via path parameter (/{short_url})
     */
    public function performRedirect(Request $request, $short_url, $secret_key = false)
    {
        return $this->handleRedirect($request, $short_url, $secret_key);
    }

    /**
     * Handles redirect via query parameter (/r?k={short_url})
     */
    public function performRedirectWithQuery(Request $request)
    {
        $short_url = $request->query('k');

        if (!$short_url) {
            return abort(400, 'Missing short URL');
        }

        return $this->handleRedirect($request, $short_url);
    }

    /**
     * Shared logic for performing redirect
     */
    private function handleRedirect(Request $request, $short_url, $secret_key = false)
    {
        $link = Link::where('short_url', $short_url)->first();

        // Return 404 if link not found
        if (!$link) {
            return abort(404);
        }

        // Return an error if the link has been disabled
        if ($link->is_disabled == 1) {
            if (env('SETTING_REDIRECT_404')) {
                return abort(404);
            }

            return view('error', [
                'message' => 'Sorry, but this link has been disabled by an administrator.'
            ]);
        }

        // Check secret key if applicable
        if ($link->secret_key) {
            if (!$secret_key || $link->secret_key !== $secret_key) {
                return abort(403);
            }
        }

        // Increment click count
        $link->clicks = intval($link->clicks) + 1;
        $link->save();

        // Advanced analytics
        if (env('SETTING_ADV_ANALYTICS')) {
            ClickHelper::recordClick($link, $request);
        }

        // Redirect
        return redirect()->to($link->long_url, 301);
    }

}
