<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AppLinkAssociationController extends Controller
{
    public function apple(): JsonResponse
    {
        $teamId = trim((string) config('spoton.app_links.apple_team_id'));
        $bundleIdentifier = trim((string) config('spoton.app_links.apple_bundle_identifier'));

        abort_if($teamId === '' || $bundleIdentifier === '', 404);

        return response()->json([
            'applinks' => [
                'apps' => [],
                'details' => [[
                    'appID' => "{$teamId}.{$bundleIdentifier}",
                    'paths' => ['/p/*', '/l/*'],
                ]],
            ],
        ]);
    }

    public function android(): JsonResponse
    {
        $package = trim((string) config('spoton.app_links.android_package'));
        $fingerprints = config('spoton.app_links.android_sha256_cert_fingerprints', []);

        abort_if($package === '' || ! is_array($fingerprints) || $fingerprints === [], 404);

        return response()->json([[
            'relation' => ['delegate_permission/common.handle_all_urls'],
            'target' => [
                'namespace' => 'android_app',
                'package_name' => $package,
                'sha256_cert_fingerprints' => array_values($fingerprints),
            ],
        ]]);
    }
}
