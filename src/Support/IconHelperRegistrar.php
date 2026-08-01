<?php

namespace AbeTwoThree\LaravelIconifyApi\Support;

use AbeTwoThree\LaravelIconifyApi\Icons\IconSvgRenderer;

class IconHelperRegistrar
{
    public static function register(string $name): void
    {
        if (function_exists($name)) {
            return;
        }

        $rendererClass = IconSvgRenderer::class;
        $helperName = preg_replace('/[^A-Za-z0-9_]/', '_', $name) ?? 'icon';
        $helperFile = sys_get_temp_dir().'/iconify-api-helper-'.$helperName.'.php';

        if (! file_exists($helperFile)) {
            $content = <<<PHP
                <?php

                if (! function_exists('{$name}')) {
                    function {$name}(string \$name, array \$options = []): string
                    {
                        return app('{$rendererClass}')->render(\$name, \$options);
                    }
                }
                PHP;

            file_put_contents($helperFile, $content);
        }

        require_once $helperFile;
    }
}
