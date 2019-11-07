<?php

function bump_version($version)
{
    $metaFile = __DIR__ . '/../meta.xml';

    $dom = new DOMDocument();
    $dom->load($metaFile);

    $root = $dom->documentElement;

    $versionElm = $root->getElementsByTagName('version')->item(0);
    $releaseElm = $root->getElementsByTagName('release')->item(0);

    $versionElm->textContent = $version;

    $release = $releaseElm->textContent;
    $release++;
    $releaseElm->textContent = $release;

    if ($dom->save($metaFile) === false) {
        throw new Exception("Could not update meta.xml");
    }

    echo "Version set to '{$version}' and release set to '{$release}'";
}
