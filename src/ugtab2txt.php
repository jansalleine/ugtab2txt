#!/usr/bin/env php
<?php

define('EXIT_SUCCESS', 0);
define('EXIT_FAILURE', 1);
define('MARK_ERROR', '[ERROR!] ');
define('MARK_WARN','[WARNING!] ');

function errorFetchContent(int $resultCode) : void
{
    echo MARK_ERROR . "Couldn't retrieve content." . PHP_EOL;
    echo "Result code: $resultCode" . PHP_EOL;
    return;
}

function errorNoUrl() : void
{
    echo MARK_ERROR . "URL missing." . PHP_EOL;
    echo "You must specify an tabs.ultimate-guitar.com URL as parameter."
        . PHP_EOL;
    return;
}

function errorPropertyMismatch() : void
{
    echo MARK_ERROR . "Property mismatch." . PHP_EOL;
    echo "Couldn't retrieve data. Probably the "
        . "JSON structure has been changed."
        . PHP_EOL;
    return;
}

function errorUrl(string $input) : void
{
    echo MARK_ERROR . "Invalid URL." . PHP_EOL;
    echo "$input" . PHP_EOL;
    echo "must be an tabs.ultimate-guitar.com URL." . PHP_EOL;
    return;
}

function errorWriteFIle(string $fileName) : void
{
    echo MARK_ERROR . "Couldn't write file." . PHP_EOL;
    echo "Filename: $filename";
    return;
}

function getDataContent(string $url) : string
{
    $dataContent = '';
    $command = sprintf(
        'curl "%s" | grep \'data-content\' | cut -d\'"\' -f 4',
        $url
    );
    $output = [];
    $resultCode = 0;

    /*
         exec(
            string $command,
            array &$output = null,
            int &$result_code = null
         ): string|false
    */
    @exec($command, $output, $resultCode);

    if (!count($output))
    {
        errorFetchContent($resultCode);
    }
    else
    {
        $dataContent = $output[0];
    }

    return $dataContent;
}

function hasAllProperties(object $json) : bool
{
    $conditions = [
        $json->store->page->data->tab->artist_name ?? false,
        $json->store->page->data->tab->song_name ?? false,
        $json->store->page->data->tab_view->wiki_tab->content ?? false
    ];

    /*
         in_array(mixed $needle, array $haystack, bool $strict = false): bool
    */
    return !in_array(false, $conditions);
}

function printInfo() : void
{
    echo "=================================" . PHP_EOL;
    echo "ugtab2txt - (c) by Jan Wassermann" . PHP_EOL;
    echo "=================================" . PHP_EOL;
    echo "Saves the text content of an ultimate-guitar.com page as TXT file."
        . PHP_EOL . PHP_EOL;
    echo "Usage example:" . PHP_EOL;
    echo "==============" . PHP_EOL;
    echo "    ugtab2txt https://tabs.ultimate-guitar.com/tab/ramones/"
        . "blitzkrieg-bop-chords-806793" . PHP_EOL;
    return;
}

function ugMarkupToPlainText(string $content) : string
{
    return html_entity_decode(
        str_replace(
            ['[tab]', '[/tab]', '[ch]', '[/ch]'],
            ['', '', '', ''],
            $content
        ),
        ENT_NOQUOTES
    );
}

(function(int &$argc, array &$argv) : int
{
    if ($argc < 2)
    {
        errorNoUrl();
        printInfo();
        return EXIT_FAILURE;
    }

    if ($argv[1] === '--help' || $argv[1] === '-h' || $argv[1] === '-?')
    {
        printInfo();
        return EXIT_SUCCESS;
    }

    /*
         str_contains(string $haystack, string $needle): bool
    */
    if (!str_contains($argv[1], 'tabs.ultimate-guitar.com'))
    {
        errorUrl($argv[1]);
        return EXIT_FAILURE;
    }

    $dataContent = getDataContent($argv[1]);

    if (empty($dataContent)) return EXIT_FAILURE;

    /*
        json_decode(
            string $json,
            ?bool $associative = null,
            int $depth = 512,
            int $flags = 0
        ): mixed

        htmlspecialchars_decode(
            string $string,
            int $flags = ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401
        ): string
    */
    $decodedContent = json_decode(
        htmlspecialchars_decode($dataContent, ENT_QUOTES)
    );

    if (!hasAllProperties($decodedContent))
    {
        errorPropertyMismatch();
        return EXIT_FAILURE;
    }

    /*
        html_entity_decode(
            string $string,
            int $flags = ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401,
            ?string $encoding = null
        ): string
    */
    $artistName = html_entity_decode(
        $decodedContent->store->page->data->tab->artist_name
    );
    $songName = html_entity_decode(
        $decodedContent->store->page->data->tab->song_name
    );

    /*
        preg_replace(
            string|array $pattern,
            string|array $replacement,
            string|array $subject,
            int $limit = -1,
            int &$count = null
        ): string|array|null
    */
    $fileName = preg_replace(
        '/[[:^print:]]/',
        '',
        $artistName . ' - ' . $songName . '.txt'
    );

    $content = ugMarkupToPlainText(
        $decodedContent->store->page->data->tab_view->wiki_tab->content
    );

    /*
        file_put_contents(
            string $filename,
            mixed $data,
            int $flags = 0,
            ?resource $context = null
        ): int|false
    */
    $writeFile = file_put_contents(
        $fileName,
        sprintf(
            '%s%s' . PHP_EOL,
            sprintf(
                '%s - %s' . PHP_EOL . PHP_EOL,
                $artistName,
                $songName
            ),
            $content
        )
    );

    if ($writeFile === false)
    {
        errorWriteFile($fileName);
        return EXIT_FAILURE;
    }

    return EXIT_SUCCESS;
})($argc, $argv);
