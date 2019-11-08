<?php

require __DIR__ . '/../plib/vendor/autoload.php';

use function KHerGe\Version\parse;

function bump_version()
{
    $metaFile = __DIR__ . '/../meta.xml';
    $push = true;

    $dom = new DOMDocument();
    $dom->load($metaFile);

    $root = $dom->documentElement;

    $versionElm = $root->getElementsByTagName('version')->item(0);
    $releaseElm = $root->getElementsByTagName('release')->item(0);

    $version = $versionElm->textContent;
    $release = $releaseElm->textContent;

    $versionSem = parse($version);

    $release++;

    exec('git log -1 --pretty=%B', $commit);

    array_shift($commit);

    $commitMsg = implode($commit,' ');

    print_line($commitMsg);

    if (strpos($commitMsg, '#major') !== false)
    {
        $version = $versionSem->incrementMajor();
        print_line('Increment major release');
    }
    else if (strpos($commitMsg, '#minor') !== false)
    {
        $version = $versionSem->incrementMinor();
        print_line('Increment minor release');
    }
    else if (strpos($commitMsg, '#patch') !== false)
    {
        $version = $versionSem->incrementPatch();
        print_line('Increment patch release');
    }
    else
    {
        $push = false;
        $release--;
    }

    $versionElm->textContent = $version;
    $releaseElm->textContent = $release;

    if ($dom->save($metaFile) === false)
    {
        throw new Exception("Could not update meta.xml");
    }

    print_line("::set-output name=version::v" . $version);
    print_line("::set-output name=ext_version::" . $version);
    print_line("::set-output name=ext_release::" . $release);
    print_line("::set-output name=push::" . ($push ? 'true' : 'false'));
}

function print_line($message)
{
    print_r($message . PHP_EOL);
}
