<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

require_once 'vendor/autoload.php';

$before = microtime(true);

function implodeTypes(array $types): string
{
    if (array_key_exists('name', $types)) {
        return 'Type: '.$types['name'];
    }

    return 'Types: '.implode(', ', array_map(fn ($t) => $t['type']['name'], $types));
}

function genderedName(string $name): string
{
    return str_replace(['-m', '-f'], ['♂', '♀'], $name);
}

function getPokemon(int $limit = 151): array
{
    $output = [];
    $baseURL = 'https://pokeapi.co/api/v2/pokemon/';
    $client = new Client();

    $requests = function ($limit) use ($baseURL) {
        for ($i = 1; $i <= $limit; $i++) {
            yield new Request('GET', $baseURL.$i, [], '', '2.0');
        }
    };

    $pool = new Pool($client, $requests($limit), [
        'concurrency' => 5,
        'fulfilled' => function (Response $response) use (&$output) {
            $output[] = json_decode($response->getBody()->getContents(), true);
        },
        'rejected' => function (RequestException $exception, $index) {
            //
        },
    ]);

    $promise = $pool->promise();
    $promise->wait();

    return $output;
}

$allPokemon = getPokemon();
$HTML = '';
foreach ($allPokemon as $data) {
    $HTML .= "<div class='pokemon'>";
    $HTML .= '<h1>'.genderedName($data['name']).'</h1>';
    $HTML .= '<h2>'.implodeTypes($data['types']).'</h2>';
    $HTML .= "<div class='image'><img src='".$data['sprites']['front_default']."' class='the-image' width='80' height='80' /><img src='".$data['sprites']['front_shiny']."' class='shiny-image' width='80' height='80' /></div>";
    $HTML .= '</div>';
}

$after = microtime(true);
$timeTaken = number_format(($after - $before), 5);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Pokedex</title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-size: 16px;
        }

        .header {
            background: black;
            text-align: center;
            font-size: 1.75rem;
            margin-bottom: .5rem;
            color: white;
        }

        .body {
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            gap: 10px;
        }

        .pokemon {
            border: solid 1px black;
            padding: 5px;
            border-radius: 8px;
        }

        .pokemon h1 {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .pokemon h2 {
            font-size: 1rem;
            font-weight: bold;
        }

        .image .shiny-image {
            display: none;
        }

        .image:hover .shiny-image {
            display: block;
        }

        .image:hover .the-image {
            display: none;
        }
    </style>
</head>
<body>
<div class="header">
    <h1>Time taken: <?= $timeTaken; ?></h1>
</div>
<div class="body">
    <?= $HTML; ?>
</div>
</body>
</html>
