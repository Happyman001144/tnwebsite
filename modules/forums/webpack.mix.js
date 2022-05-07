const mix = require('laravel-mix');

mix.webpackConfig({
    externals: {
        "vue": "Vue"
    }
});

mix.disableSuccessNotifications();

mix.setPublicPath('./public')
   .js('resources/js/forumsspa.js', 'public/js')
   .version();
