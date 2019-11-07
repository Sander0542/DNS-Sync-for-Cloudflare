<?php

function bump_version($version)
{
    $metaFile = __DIR__ . '/../meta.xml';

    if (substr($version,0,1) == 'v') {
        $version = substr($version, 1, strlen($version) - 1);
    }

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
