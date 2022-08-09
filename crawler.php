<?php
require "vendor/autoload.php";
$movements = [];


foreach (range(1, 40) as $page) {
    $crawler = new \Symfony\Component\DomCrawler\Crawler(
        file_get_contents("https://fitamin.ir/mag/movement/page/$page")
    );

    $crawler
        ->filter(".row>div.col-md-6")
        ->reduce(fn($c) => $c->children()->nodeName() === "a")
        ->each(function ($card) use (&$movements) {
            $name = $card->filter("a>div:nth-child(2)>p")->text();
            $url = $card->filter("a")->attr("href");
            $contentCrawler = new \Symfony\Component\DomCrawler\Crawler(
                file_get_contents($url)
            );

            saveThumbnail($card, $name);
            saveFullImage($contentCrawler, $name);
            saveVideo($contentCrawler, $name);

            $movements[] = [
                "name" => $name,
                "thumbnail" => "asset/thumbnails/$name-300x300.jpg",
                "image" => "asset/image/$name.jpg",
                "how" => getHows($contentCrawler),
                "video" => "asset/video/$name.mp4",
            ];
            echo $name . PHP_EOL;
        });
}

file_put_contents("data.json", json_encode($movements));
echo "DONE";


function saveVideo(\Symfony\Component\DomCrawler\Crawler $contentCrawler, mixed $name): void
{
    $video = $contentCrawler
        ->filter("article > div.text-center.mb-3 > video > source")
        ->attr("src");
    file_put_contents(
        __DIR__ . "/asset/video/$name.mp4",
        file_get_contents($video)
    );
}

function saveFullImage(\Symfony\Component\DomCrawler\Crawler $contentCrawler, mixed $name): void
{
    $full_image_url = $contentCrawler
        ->filter("img.wp-post-image")
        ->attr("src");
    file_put_contents(
        __DIR__ . "/asset/image/$name.jpg",
        file_get_contents($full_image_url)
    );
}

function saveThumbnail($card, mixed $name): void
{
    $image_url = $card
        ->filter("a>div:nth-child(1)>img")
        ->attr("data-src");

    file_put_contents(
        __DIR__ . "/asset/thumbnails/$name-300x300.jpg",
        file_get_contents($image_url)
    );
}

function getHows(\Symfony\Component\DomCrawler\Crawler $contentCrawler): array
{
    $hows = [];
    $contentCrawler
        ->filter("article > ul>li")
        ->each(function ($li) use (&$hows) {
            return array_push($hows, $li->text());
        });
    return $hows;
}