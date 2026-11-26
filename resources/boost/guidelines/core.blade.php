@verbatim
# Laravel Iconify API

This app renders Iconify icons with `abetwothree/laravel-iconify-api`. Icon names are `prefix:name`, and every
prefix needs its icon set installed from npm into `dependencies`: `npm install @iconify-json/{prefix}`.

- In Blade, render an icon as inline SVG with the `<x-icon>` component. It needs no JavaScript.

<code-snippet name="Render an Iconify icon in Blade" lang="blade">
<x-icon name="heroicons:clock" class="size-4" />
</code-snippet>

- In Vue, React or web components, use Iconify's own component, and keep `@iconify` before `@vite` in the root
  Blade layout, so icons load from this app's `/iconify/api` instead of `api.iconify.design`.
- Before adding or debugging icons, read the `rendering-iconify-icons` skill. It covers blank or stale icons,
  the "Could not find the icons file for the set" error, icon options, CSP nonces and production deploys.
@endverbatim
