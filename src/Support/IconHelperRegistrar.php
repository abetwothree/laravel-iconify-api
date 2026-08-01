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
        $helperFile = self::createHelperFilePath();

        if ($helperFile === null) {
            return;
        }

        $content = <<<PHP
            <?php

            if (! function_exists('{$name}')) {
                function {$name}(string \$name, array \$options = []): string
                {
                    return app('{$rendererClass}')->render(\$name, \$options);
                }
            }
            PHP;

        if (file_put_contents($helperFile, $content, LOCK_EX) === false) {
            @unlink($helperFile);

            return;
        }

        require $helperFile;

        @unlink($helperFile);
    }

    protected static function createHelperFilePath(): ?string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $suffix = bin2hex(random_bytes(16));
            $path = sys_get_temp_dir().'/iconify-api-helper-'.$suffix.'.php';

            $handle = @fopen($path, 'x');

            if ($handle === false) {
                continue;
            }

            fclose($handle);

            return $path;
        }

        return null;
    }
}
