const mix = require('laravel-mix');

mix.disableSuccessNotifications();

mix.setPublicPath('./public')
   .js('resources/js/app.js', 'public/js')
   .sass('resources/sass/app.scss', 'public/css')
   .extract()
   .version();
