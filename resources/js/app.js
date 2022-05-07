require('./scripts/bootstrap');

window.AOS = require('aos');
AOS.init();

window.Vue = require('vue');

const files = require.context('./', true, /\.vue$/i)
files.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], files(key).default))

Vue.use(require('vue-moment'));

import BootstrapVue from 'bootstrap-vue';
Vue.use(BootstrapVue)

var lang = {};
$.ajax({url: "/lang", cache: true, async: false}).done(function(res) { lang = res; });
Vue.filter('lang', function (value) {
  if (!value) return ''
  value = value.toString()
  var fallback = value.charAt(0).toUpperCase() + value.slice(1);
  if (typeof lang['override'] !== 'undefined' && typeof lang['override'][value] !== 'undefined' && lang['en'][value] !== '') {
      return lang['override'][value]
  } else if (typeof lang['en'] !== 'undefined' && typeof lang['en'][value] !== 'undefined' && lang['en'][value] !== '') {
    return lang['en'][value]
  } else {
      return fallback
  }
})

import draggable from 'vuedraggable'
window.draggable = draggable // used by adminvue.js

import Quill from 'quill'
import Mention from 'quill-mention'
Quill.register('modules/mention', Mention)
import VueQuillEditor from 'vue-quill-editor'
Vue.use(VueQuillEditor, {
  theme: 'snow',
  placeholder: '',
  modules: {
    toolbar: [
      [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
      ['bold', 'italic', 'underline'],
      [{ 'list': 'ordered'}, { 'list': 'bullet' }],
      [{ 'color': [] }, { 'background': [] }],
      [{ 'align': [] }],
      ['clean']
    ]
  }
})

const app = new Vue({
  el: '#app',
  components: {
    draggable
  }
});

require('./scripts/ui');
require('./scripts/pagination');
