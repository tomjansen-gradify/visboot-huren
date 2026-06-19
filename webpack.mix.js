const mix = require('laravel-mix');

mix.setPublicPath('public');

mix.sass('resources/sass/index.scss', 'css')
   .js('resources/js/index.js', 'js')
   .minify('public/js/index.js')
   .options({
       processCssUrls: false,
   });

mix.copyDirectory('resources/fonts', 'public/fonts');
mix.copyDirectory('resources/images', 'public/images');

mix.version();
