<?php

declare(strict_types=1);

namespace AbeTwoThree\LaravelIconifyApi\Components;

use AbeTwoThree\LaravelIconifyApi\Icons\IconSvgRenderer;
use Closure;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

class Icon extends Component
{
    public function __construct(
        public IconSvgRenderer $renderer,
        public string $name,
    ) {}

    public function render(): Closure
    {
        return function (array $data): string {
            /** @var ComponentAttributeBag $attributes */
            $attributes = $data['attributes'];
            /** @var array<string, mixed> $componentAttributes */
            $componentAttributes = $attributes->getAttributes();

            return $this->renderer->render($this->name, $componentAttributes);
        };
    }
}
