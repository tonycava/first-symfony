<?php

const PHP_INI_LIST_DIRECTORY = 'php-ini-list';

function extractRequireAndPHPVersion($jsonFilePath): string|null
{
    if (!file_exists($jsonFilePath))
        die("File not found: $jsonFilePath\n");

    $jsonData = file_get_contents($jsonFilePath);

    $jsonArray = json_decode($jsonData, true);

    if (isset($jsonArray['require']['php']))
        return $jsonArray['require']['php'];

    return null;
}

function sortPhpVersions($versions)
{
    $compareVersions = function ($a, $b) {
        return version_compare($a, $b);
    };

    usort($versions, $compareVersions);

    return $versions;
}

function extract_operator(string $version): ?string
{
    $pattern = '/^([><]=?)\s*(.+)/';

    if (preg_match($pattern, $version, $matches)) {
        return $matches[1];
    }
    return null;
}

function is_exact_php_version($phpVersion): bool|int
{
    return preg_match('/^[0-9]+\.[0-9]+\.[0-9]+$/', $phpVersion);
}


if (!isset($argv[1]))
    die("Usage: php extract_json.php <json_file_path>\n");

$jsonFilePath = $argv[1];
$phpVersion = extractRequireAndPHPVersion($jsonFilePath);

if (!$phpVersion)
    die("PHP version not found\n");


$operator = is_exact_php_version($phpVersion) ? "==" : extract_operator($phpVersion);

$directories = array_filter(scandir(PHP_INI_LIST_DIRECTORY), function ($item) {
    return is_dir(PHP_INI_LIST_DIRECTORY . '/' . $item) && $item !== '.' && $item !== '..' && $item !== '.git';
});

$sortedVersions = sortPhpVersions($directories);


foreach ($sortedVersions as $version) {
    if (version_compare($version, $phpVersion, $operator)) {

        exec(sprintf("cp %s/$version/php.ini .", PHP_INI_LIST_DIRECTORY));
        var_dump("find version $version");
        break;
    }
}
