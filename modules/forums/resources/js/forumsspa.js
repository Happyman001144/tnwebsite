import Vue from 'vue'
import VueRouter from 'vue-router'
Vue.use(VueRouter)

const files = require.context('./', true, /\.vue$/i)
files.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], files(key).default))

import Index from './components/Index'
import Search from './components/Search'
import Board from './components/Board'
import Create from './components/Create'
import Thread from './components/Thread'

const routes = [
  { path: '/forums', component: Index, meta: {title: 'Forums'} },
  { path: '/forums/search', name: 'search', component: Search, meta: {title: 'Search'} },
  { path: '/forums/boards/:bid', name: 'board', component: Board, meta: {title: 'Board'} },
  { path: '/forums/boards/:bid/page/:page', name: 'board_page', component: Board, meta: {title: 'Board'} },
  { path: '/forums/create/:bid', component: Create, meta: {title: 'Create'} },
  { path: '/forums/threads/:tid', name: 'thread', component: Thread, meta: {title: 'Thread'} },
  { path: '/forums/threads/:tid/page/:page', name: 'thread_page', component: Thread , meta: {title: 'Thread'}}
]

const router = new VueRouter({
  routes,
  mode: 'history'
})

router.beforeEach((to, from, next) => {
  document.title = to.meta.title + titleSuffix
  next()
})

import VueMarkdown from 'vue-markdown'
Vue.component('vue-markdown', VueMarkdown)

new Vue({
  el: '#forumsspa',
  router
})
