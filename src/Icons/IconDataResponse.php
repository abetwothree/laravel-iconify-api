<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons;

class IconDataResponse
{
    /**
     * @param  string[]  $icons
     */
    public function __construct(
        protected string $set,
        protected array $icons,
    ) {}

    /**
     * Summary of response
     *
     * @return array{
        prefix: string,
        lastModified: int,
        aliases: array<string, string>,
        width: int,
        height: int,
        icons: array<string, array<string, string>>
     }
     */
    public function response(): array
    {
        return [
            'prefix' => $this->set,
            'lastModified' => time(),
            'aliases' => [],
            'width' => 24,
            'height' => 24,
            'icons' => [
                'home' => [
                    'body' => '<path fill="currentColor" d="m16 8.41l-4.5-4.5L4.41 11H6v8h3v-6h5v6h3v-8h1.59L17 9.41V6h-1zM2 12l9.5-9.5L15 6V5h3v4l3 3h-3v8h-5v-6h-3v6H5v-8z"/>',
                ],
                'account-arrow-up-outline' => [
                    'body' => '<path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12c0 5.52 4.48 10 10 10 5.52 0 10-4.48 10-10 0-5.52-4.48-10-10-10zm0 18c-4.41 0-8-3.59-8-8 0-3.07 1.74-5.74 4.29-7.07L12 7.83l3.71 5.1C18.26 8.26 20 10.93 20 14c0 4.41-3.59 8-8 8z"/>',
                ],
            ],
        ];
    }
}
