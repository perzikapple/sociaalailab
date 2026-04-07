<?php 
session_start();
require 'db.php';
require 'helpers.php';

// Get banners from settings
$banner1 = 'images/banner_website_01.jpg';
$banner2 = 'images/banner_website_02.jpg';

try {
    $banner1 = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'banner1'")->fetchColumn() ?: $banner1;
    $banner2 = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'banner2'")->fetchColumn() ?: $banner2;
} catch (Exception $e) {
}

// LinkedIn profile URL for SociaalAI Lab
$linkedinProfileUrl = 'https://www.linkedin.com/company/sociaal-ai-lab-rotterdam/';

// Optional LinkedIn API configuration via environment variables.
// LINKEDIN_ORG_ID example: 12345678
// LINKEDIN_ACCESS_TOKEN requires access to organization social content.
$linkedinOrgId = getenv('LINKEDIN_ORG_ID') ?: '';
$linkedinAccessToken = getenv('LINKEDIN_ACCESS_TOKEN') ?: '';
$isAdminViewer = !empty($_SESSION['can_access_admin']) || (isset($_SESSION['admin']) && (int) $_SESSION['admin'] === 1);

// Optional mock mode to test UI/flow without LinkedIn API credentials.
// Enable with LINKEDIN_MOCK_MODE=1 or with ?linkedin_mock=1 (admin only).
$isUsingLinkedInMock = (getenv('LINKEDIN_MOCK_MODE') === '1')
    || ($isAdminViewer && isset($_GET['linkedin_mock']) && $_GET['linkedin_mock'] === '1');
$currentPath = strtok($_SERVER['REQUEST_URI'] ?? '/nieuws.php', '?') ?: '/nieuws.php';
$enableMockUrl = $currentPath . '?linkedin_mock=1';
$disableMockUrl = $currentPath;

function getMockLatestLinkedInPost(string $profileUrl): array
{
    return [
        'url' => $profileUrl . 'posts/',
        'text' => 'Demo-modus: dit is een testpost om de weergave van de nieuwste LinkedIn update te controleren.',
        'urn' => 'urn:li:activity:mock',
        'embedUrl' => '',
        'isMock' => true,
    ];
}

/**
 * Fetch the most recent LinkedIn post for an organization.
 * Returns null when API credentials are missing or when the request fails.
 */
function fetchLatestLinkedInPost(string $orgId, string $accessToken, ?array &$debug = null): ?array
{
    if ($debug === null) {
        $debug = [];
    }

    $debug['credentialMissing'] = false;
    $debug['curlMissing'] = false;
    $debug['attempts'] = [];

    if ($orgId === '' || $accessToken === '') {
        $debug['credentialMissing'] = true;
        return null;
    }

    if (!function_exists('curl_init')) {
        $debug['curlMissing'] = true;
        return null;
    }

    $authorUrn = 'urn:li:organization:' . $orgId;
    $authorUrnList = rawurlencode('List(' . $authorUrn . ')');
    $endpoints = [
        'https://api.linkedin.com/v2/ugcPosts?q=authors&authors=' . $authorUrnList . '&sortBy=LAST_MODIFIED&count=1',
        'https://api.linkedin.com/v2/shares?q=owners&owners=' . $authorUrnList . '&sortBy=LAST_MODIFIED&count=1',
    ];

    foreach ($endpoints as $url) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'X-Restli-Protocol-Version: 2.0.0',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $attemptInfo = [
            'endpoint' => $url,
            'httpCode' => $httpCode,
            'curlError' => $curlError,
            'message' => '',
        ];

        if ($httpCode < 200 || $httpCode >= 300 || !$response) {
            if ($response) {
                $errorData = json_decode($response, true);
                if (is_array($errorData) && isset($errorData['message']) && is_string($errorData['message'])) {
                    $attemptInfo['message'] = $errorData['message'];
                }
            }
            $debug['attempts'][] = $attemptInfo;
            continue;
        }

        $data = json_decode($response, true);
        if (!is_array($data) || empty($data['elements'][0]) || !is_array($data['elements'][0])) {
            $attemptInfo['message'] = 'Geen post-elementen gevonden in response.';
            $debug['attempts'][] = $attemptInfo;
            continue;
        }

        $post = $data['elements'][0];
        $urn = $post['id'] ?? '';

        if ($urn === '' && isset($post['specificContent']['com.linkedin.ugc.ShareContent'])) {
            // UGC responses may not include a plain id in some app scopes.
            $urn = $post['entityUrn'] ?? '';
        }

        if (!is_string($urn) || $urn === '') {
            $attemptInfo['message'] = 'Post URN ontbreekt in response.';
            $debug['attempts'][] = $attemptInfo;
            continue;
        }

        $postText = '';
        if (isset($post['specificContent']['com.linkedin.ugc.ShareContent']['shareCommentary']['text']) && is_string($post['specificContent']['com.linkedin.ugc.ShareContent']['shareCommentary']['text'])) {
            $postText = $post['specificContent']['com.linkedin.ugc.ShareContent']['shareCommentary']['text'];
        } elseif (isset($post['text']['text']) && is_string($post['text']['text'])) {
            $postText = $post['text']['text'];
        }

        $postUrl = 'https://www.linkedin.com/feed/update/' . $urn . '/';
        $embedUrl = 'https://www.linkedin.com/embed/feed/update/' . $urn;

        return [
            'url' => $postUrl,
            'text' => trim($postText),
            'urn' => $urn,
            'embedUrl' => $embedUrl,
        ];
    }

    return null;
}

$linkedinApiDebug = [];
$latestLinkedInPost = fetchLatestLinkedInPost($linkedinOrgId, $linkedinAccessToken, $linkedinApiDebug);

if ($latestLinkedInPost === null && $isUsingLinkedInMock) {
    $latestLinkedInPost = getMockLatestLinkedInPost($linkedinProfileUrl);
    $linkedinApiDebug['mockMode'] = true;
}

$latestLinkedInPostUrl = $latestLinkedInPost['url'] ?? ($linkedinProfileUrl . 'posts/');
$latestLinkedInPostText = $latestLinkedInPost['text'] ?? 'Open direct de nieuwste update van SociaalAI Lab op LinkedIn.';
$latestLinkedInExcerpt = strlen($latestLinkedInPostText) > 220 ? substr($latestLinkedInPostText, 0, 220) . '...' : $latestLinkedInPostText;
$hasLinkedInApiData = $latestLinkedInPost !== null;
$latestLinkedInEmbedUrl = $latestLinkedInPost['embedUrl'] ?? '';
$isMockPost = !empty($latestLinkedInPost['isMock']);

?>

<!doctype html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="modulepreload" as="script" href="build/assets/app-CAiCLEjY.js"><link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__.'/style.css'); ?>"><script type="module" src="build/assets/app-CAiCLEjY.js"></script>    <title>Nieuws & Updates</title>
    <meta name="description" content="Blijf op de hoogte van het laatste nieuws, updates en activiteiten van SociaalAI Lab.">
    <link rel="icon" type="image/png" href="images/Pixels_icon.png">
    <link rel="stylesheet" href="ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body class="bg-gradient-to-br from-[#00811F] to-[#b9eb34]">

<div class="banner-wrapper">
    <div class="banner banner-1 active">
        <img class="" src="<?php echo htmlspecialchars($banner1); ?>">
    </div>
    <div class="banner banner-2">
        <img class="" src="<?php echo htmlspecialchars($banner2); ?>">
    </div>
</div>

<?php
$navPrefix = '';
include __DIR__ . '/navbar.php';
?>

<main>
    <!-- Nieuws Header -->
    <section class="bg-white shadow-lg p-8 max-w-6xl mx-auto my-12">
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-[#00811F] mb-2">
                <i class="fa-solid fa-newspaper mr-3"></i>Nieuws & Updates
            </h1>
            <p class="text-lg text-gray-600">
                Blijf op de hoogte van het laatste nieuws en activiteiten van SociaalAI Lab
            </p>
        </div>
    </section>

    <!-- LinkedIn Featured Post -->
    <section class="max-w-6xl mx-auto px-8 py-8">
        <div class="bg-white rounded-lg shadow-lg p-8 mb-12">
            <div class="flex items-center gap-4 mb-6 pb-6 border-b-2 border-gray-200">
                <i class="fa-brands fa-linkedin text-4xl text-[#0A66C2]"></i>
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Nieuwste LinkedIn Update</h2>
                    <p class="text-gray-600">Volg ons op LinkedIn voor dagelijkse updates</p>
                </div>
            </div>

            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-8 text-center">
                <p class="text-gray-700 mb-6 text-lg">
                    Ontdek de meest recente nieuws en activiteiten van SociaalAI Lab
                </p>
                
                <div class="mb-8">
                    <div class="bg-white rounded-lg p-6 shadow-md text-left max-w-3xl mx-auto">
                        <div class="flex items-start gap-4 mb-5">
                            <i class="fa-brands fa-linkedin text-3xl text-[#0A66C2] mt-1"></i>
                            <div>
                                <h3 class="text-xl font-bold text-gray-800 mb-2">Meest recente LinkedIn post</h3>
                                <p class="text-gray-600 mb-4">
                                    <?php echo htmlspecialchars($latestLinkedInExcerpt); ?>
                                </p>
                                <?php if ($isMockPost && $isAdminViewer): ?>
                                    <p class="text-xs text-amber-700 bg-amber-100 border border-amber-200 rounded px-2 py-1 inline-block mb-3">
                                        Testmodus actief (mock data)
                                    </p>
                                <?php endif; ?>
                                <a href="<?php echo htmlspecialchars($latestLinkedInPostUrl); ?>"
                                   target="_blank"
                                   rel="noopener"
                                   class="inline-flex items-center gap-2 bg-[#0A66C2] hover:bg-[#004099] text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                                    <i class="fa-solid fa-up-right-from-square"></i>
                                    Bekijk nieuwste post
                                </a>
                            </div>
                        </div>

                        <?php if ($hasLinkedInApiData && $latestLinkedInEmbedUrl !== ''): ?>
                            <iframe
                                src="<?php echo htmlspecialchars($latestLinkedInEmbedUrl); ?>"
                                height="420"
                                width="100%"
                                frameborder="0"
                                allowfullscreen=""
                                title="Laatste LinkedIn post"
                                class="rounded-lg border border-gray-200">
                            </iframe>
                        <?php endif; ?>
                    </div>
                </div>

                <a href="<?php echo htmlspecialchars($linkedinProfileUrl); ?>" 
                   target="_blank" 
                   rel="noopener"
                   class="inline-flex items-center gap-2 bg-[#0A66C2] hover:bg-[#004099] text-white font-bold py-3 px-6 rounded-lg transition-colors">
                    <i class="fa-brands fa-linkedin"></i>
                    Volg ons op LinkedIn
                </a>
            </div>

            <div class="mt-8 p-5 bg-white rounded-lg border border-blue-200 shadow-sm">
                <h3 class="text-base font-bold text-[#0A66C2] mb-2">Mock-mode</h3>
                <p class="text-sm text-gray-700">
        
                <?php if (!$hasLinkedInApiData && !$isUsingLinkedInMock && $isAdminViewer): ?>
                    <p class="text-xs text-gray-500 mt-3">
                        API-koppeling niet actief. Stel <strong>LINKEDIN_ORG_ID</strong> en <strong>LINKEDIN_ACCESS_TOKEN</strong> in op de server om automatisch de nieuwste post op te halen.
                    </p>
                    <div class="mt-3 text-xs text-gray-600 bg-gray-50 border border-gray-200 rounded-md p-3">
                        <p class="font-semibold mb-1">Admin debug</p>
                        <p>
                            credentials: <?php echo !empty($linkedinApiDebug['credentialMissing']) ? 'missing' : 'ok'; ?>,
                            curl: <?php echo !empty($linkedinApiDebug['curlMissing']) ? 'missing' : 'ok'; ?>
                        </p>
                        <?php if (!empty($linkedinApiDebug['attempts']) && is_array($linkedinApiDebug['attempts'])): ?>
                            <?php foreach ($linkedinApiDebug['attempts'] as $attempt): ?>
                                <p class="mt-1">
                                    status <?php echo htmlspecialchars((string) ($attempt['httpCode'] ?? 0)); ?>
                                    <?php if (!empty($attempt['message'])): ?>
                                        - <?php echo htmlspecialchars((string) $attempt['message']); ?>
                                    <?php endif; ?>
                                    <?php if (!empty($attempt['curlError'])): ?>
                                        - curl: <?php echo htmlspecialchars((string) $attempt['curlError']); ?>
                                    <?php endif; ?>
                                </p>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="mt-1">Geen endpoint-response ontvangen.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($isUsingLinkedInMock && $isAdminViewer): ?>
                    <p class="text-xs text-gray-500 mt-3">
                        Mock-modus staat aan. Schakel uit door <strong>LINKEDIN_MOCK_MODE</strong> te verwijderen of <strong>?linkedin_mock=1</strong> weg te halen.
                    </p>
                <?php endif; ?>

                <?php if ($isAdminViewer): ?>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="<?php echo htmlspecialchars($enableMockUrl); ?>"
                           class="inline-flex items-center gap-2 text-xs bg-amber-100 hover:bg-amber-200 text-amber-900 border border-amber-300 px-3 py-1 rounded">
                            <i class="fa-solid fa-vial"></i>
                            Mock aan
                        </a>
                        <a href="<?php echo htmlspecialchars($disableMockUrl); ?>"
                           class="inline-flex items-center gap-2 text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-300 px-3 py-1 rounded">
                            <i class="fa-solid fa-rotate-left"></i>
                            Mock uit
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

<?php include __DIR__ . '/footer.php'; ?>

</body>
</html>
