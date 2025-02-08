<?php

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();

arch()->preset()->php();

arch()->preset()->security()->ignoring('assert');

arch()->preset()->laravel();
