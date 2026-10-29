<?php

declare(strict_types=1);

use AbeTwoThree\LaravelIconifyApi\Icons\IconBody;
use AbeTwoThree\LaravelIconifyApi\Icons\IconFinder;

it('rejects a body that can run script', function (string $body) {
    expect(IconBody::isSafe($body))->toBeFalse();
})->with([
    // Issue #60, rule 1: a <script> element.
    'script element' => ['<script>alert(1)</script>'],
    'uppercase script element' => ['<g><SCRIPT>alert(1)</SCRIPT></g>'],
    // Rule 2: an on* event attribute.
    'event attribute' => ['<path d="M0 0" onload="alert(1)"/>'],
    'event attribute after a closing quote' => ['<path d="M0"onload="alert(1)"/>'],
    'event attribute after a single quote' => ["<path d='M0'onclick='alert(1)'/>"],
    'event attribute after a slash' => ['<path/onload=alert(1)>'],
    'uppercase event attribute' => ['<path ONLOAD="alert(1)"/>'],
    'spaces around the equals sign' => ['<path onload = "alert(1)"/>'],
    'smil event attribute' => ['<animate onbegin="alert(1)"/>'],
    // Rule 3: an href that is neither #id nor data:image/.
    'javascript href' => ['<a href="javascript:alert(1)"><path d="M0 0"/></a>'],
    'javascript xlink:href' => ['<a xlink:href="javascript:alert(1)"><path d="M0 0"/></a>'],
    'decimal entity' => ['<a href="&#106;avascript:alert(1)"/>'],
    'decimal entity without a semicolon' => ['<a href="&#106avascript:alert(1)"/>'],
    'hex entity tab' => ['<a href="java&#x09;script:alert(1)"/>'],
    'named colon entity' => ['<a href="javascript&colon;alert(1)"/>'],
    'named tab entity' => ['<a href="java&Tab;script:alert(1)"/>'],
    'raw tab inside the scheme' => ["<a href=\"java\tscript:alert(1)\"/>"],
    'leading spaces' => ['<a href="  javascript:alert(1)"/>'],
    'single-quoted value' => ["<a href='javascript:alert(1)'/>"],
    'unquoted value' => ['<a href=javascript:alert(1)>'],
    'uppercase attribute name' => ['<a HREF="javascript:alert(1)"/>'],
    'other namespace prefix' => ['<a x:href="javascript:alert(1)"/>'],
    'https url' => ['<image href="https://evil.test/track.png"/>'],
    'non-image data url' => ['<a href="data:text/html,&lt;script&gt;alert(1)&lt;/script&gt;"/>'],
    'empty href' => ['<a href=""/>'],
    // Approved beyond the issue: elements that embed HTML or another document.
    'foreignObject' => ['<foreignObject><iframe srcdoc="&lt;script&gt;alert(1)&lt;/script&gt;"></iframe></foreignObject>'],
    'iframe' => ['<iframe src="javascript:alert(1)"></iframe>'],
    'embed' => ['<embed src="javascript:alert(1)"/>'],
    'object' => ['<object data="javascript:alert(1)"></object>'],
    // ...and SMIL that rewrites an href at runtime.
    'set attributeName href' => ['<a><set attributeName="href" to="javascript:alert(1)"/></a>'],
    'animate attributeName xlink:href' => ['<a><animate attributeName="xlink:href" values="#a;javascript:alert(1)"/></a>'],
    'entity-encoded attributeName' => ['<a><set attributeName="&#104;ref" to="javascript:alert(1)"/></a>'],
    'entity-encoded attributeName without a semicolon' => ['<a><set attributeName="&#104ref" to="javascript:alert(1)"/></a>'],
    // The parser must read markup as a browser does, not as a pattern would.
    'href hidden behind a double-quoted decoy attribute' => ['<a title="href=#x"href="javascript:alert(1)"><path d="M0"/></a>'],
    'href hidden behind a single-quoted decoy attribute' => ["<a title='href=#x'href='javascript:alert(1)'><path d=\"M0\"/></a>"],
    'href hidden behind a data attribute' => ['<a data-x="href=#"href="javascript:alert(1)"><path d="M0"/></a>'],
    'attributeName hidden behind a decoy attribute' => ['<a><set title="attributeName=x"attributeName="href" to="javascript:alert(1)"/></a>'],
    'entity with leading zeros in attributeName' => ['<a><set attributeName="&#0000000104;ref" to="javascript:alert(1)"/></a>'],
    'element outside the svg' => ['</svg><img src=x onerror=alert(1)>'],
    'noscript parser differential' => ['</svg><noscript><p title="</noscript><img src=x onerror=alert(1)>">'],
    'noscript element' => ['<noscript></noscript>'],
    // Encoding sniffing: a <meta charset> must not change how the rest is decoded.
    'meta charset utf-16le before a forbidden element' => ['<meta charset="utf-16le"><noscript></noscript>'],
    'meta charset iso-8859-1 before a forbidden element' => ['<meta charset="iso-8859-1"><noscript></noscript>'],
    'meta element' => ['<meta name="x">'],
    // <template> content is not in the element walk, and a declarative shadow root is live.
    'template outside the svg' => ['</svg><template><noscript></noscript></template>'],
    'declarative shadow root' => ['</svg><template shadowrootmode="open"><b><noscript></noscript></b></template>'],
]);

it('accepts a body that cannot run script', function (string $body) {
    expect(IconBody::isSafe($body))->toBeTrue();
})->with([
    'plain path' => ['<path fill="currentColor" d="M12 2L2 22h20z"/>'],
    'gradient and mask referencing ids' => ['<defs><linearGradient id="a"><stop offset="0" stop-color="#000"/></linearGradient><linearGradient id="b" href="#a"/><mask id="m"><use xlink:href="#p" fill="#fff"/></mask></defs><path id="p" fill="url(#b)" mask="url(#m)" d="M0 0h24v24H0z"/>'],
    'leading space before an id reference' => ['<use href=" #p"/>'],
    'data image href' => ['<image href="data:image/png;base64,iVBORw0KGgo=" width="24" height="24"/>'],
    'uppercase data image href' => ['<image href="DATA:IMAGE/PNG;base64,iVBORw0KGgo="/>'],
    'smil animation of another attribute' => ['<circle cx="12" cy="12" r="3"><animate attributeName="r" values="3;9;3" dur="1s" repeatCount="indefinite"/></circle>'],
    '"on" inside a value or another attribute name' => ['<path class="icon on" stroke-dashoffset="1" d="M0 0"/>'],
]);

it('accepts real icon bodies that use gradients, masks, ids and SMIL', function (string $prefix, string $name) {
    $body = resolve(IconFinder::class)->find($prefix, [$name])[$name]['icons'][$name]['body'] ?? null;

    expect($body)->toBeString()
        ->and(IconBody::isSafe($body))->toBeTrue();
})->with([
    'url(#) fill with an href reference' => ['akar-icons', 'pinterest-fill'],
    'href reference' => ['arcticons', '0h-h1'],
    'mask, gradient and href reference' => ['logos', 'moon'],
    'SMIL animation' => ['svg-spinners', 'ring-resize'],
]);
