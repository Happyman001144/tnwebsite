<template>
  <div>
    <div class="page-header">
      <h1 class="title container">{{ 'forums'|lang }}</h1>
    </div>
    <div class="container">

      <breadcrumb></breadcrumb>
      <div class="row">
        <div class="col-12 col-md-9">
          <div class="card" v-for="fc in forum_categories">
            <div class="card-header">
              <h5 class="title my-0">{{ fc.name }}</h5>
            </div>
            <div class="card-body py-1">
              <div v-for="fb, index in fc.boards">
                <hr class="my-1" v-if="index != 0">
                <div class="d-flex align-items-center">
                  <div class="p-2" v-if="fb.icon">
                    <i :class="fb.icon+' fa-2x fa-fw board-icon'"></i>
                  </div>
                  <div class="p-2">
                    <router-link :to="'/forums/boards/'+fb.bid">
                      <h5 class="mb-0">{{ fb.name }}</h5>
                    </router-link>
                    {{ fb.total_threads }} <span class="font-weight-light">{{ (fb.total_threads != 1 ? 'threads': 'thread')|lang }}</span>
                    &middot;
                    {{ fb.total_posts }} <span class="font-weight-light">{{ (fb.total_posts != 1 ? 'posts' : 'post')|lang }}</span>
                  </div>
                  <div class="ml-auto p-2 text-right" v-if="fb.latest_thread">
                    <h6 class="mb-0">
                      <a :href="'/forums/posts/'+fb.latest_thread.latest_post.pid" @click.prevent="navigateToPost(fb.latest_thread.latest_post.pid)">
                        {{ fb.latest_thread.topic }}
                      </a>
                    </h6>
                    <span class="font-weight-light">
                      <span v-b-tooltip.hover.left :title="fb.latest_thread.latest_post.timestamp+' Z'">
                        <vue-timeago-js :datetime="fb.latest_thread.latest_post.timestamp+'Z'"></vue-timeago-js>
                      </span>
                      &middot;
                      <a :href="'profile/'+fb.latest_thread.latest_post.user.steamid" :style="fb.latest_thread.latest_post.user.group ? 'color: '+fb.latest_thread.latest_post.user.group.color: ''">{{ fb.latest_thread.latest_post.user.steam.personaname }}</a>
                    </span>
                  </div>
                  <div class="p-2" v-if="fb.latest_thread">
                    <a :href="'profile/'+fb.latest_thread.latest_post.user.steamid">
                      <img class="avimd" :src="fb.latest_thread.latest_post.user.steam.avatarfull"></img>
                    </a>
                  </div>
                  <div class="ml-auto p-2" v-else>
                    {{ 'no_recent_posts'|lang }}
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="card skeleton skeleton-animation-pulse" v-for="index, n in 2" v-if="!forum_categories">
            <div class="card-header">
              <div class="bone bone-type-text w-25"></div>
            </div>
            <div class="card-body py-1">
              <div class="d-flex align-items-center" v-for="n in Math.floor((Math.random() * 3) + 3)">
                <img class="bone bone-type-image bone-style-rounded"></img>
                <div class="p-2 w-100">
                  <h5 class="mb-0">
                    <div class="bone bone-type-text w-50"></div>
                  </h5>
                  <div class="bone bone-type-text w-25"></div>
                </div>
                <div class="ml-auto p-2 text-right w-50">
                  <h6 class="mb-0">
                    <div class="bone bone-type-text"></div>
                  </h6>
                  <div class="bone bone-type-text w-50 ml-auto"></div>
                </div>
                <div class="p-2">
                  <img class="avimd bone bone-type-image bone-style-round"></img>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-3">
          <div class="card">
            <div class="card-header">
              <h5 class="title my-0">{{ 'latest_posts'|lang }}</h5>
            </div>
            <div class="card-body px-0 py-2">
              <div v-if="latest_posts">
                <div v-for="lp, index in latest_posts">
                  <hr class="my-1" v-if="index != 0">
                  <div class="d-flex">
                    <div class="p-2">
                      <a :href="'/profile/'+lp.user.steamid">
                        <img class="avism align-self-center" :src="lp.user.steam.avatarfull"></img>
                      </a>
                    </div>
                    <div class="py-2">
                      <a :href="'/forums/posts/'+lp.pid" @click.prevent="navigateToPost(lp.pid)">
                        <h6 class="mb-0 {{lp.thread.read_timestamp and lp.thread.last_posted < lp.thread.read_timestamp ? 'font-weight-light': '']]">{{ lp.thread.topic }}</h6>
                      </a>
                      <span style="font-size: 11px;">
                        <a :href="'/profile/'+lp.user.steamid" :style="lp.user.group ? 'color: '+lp.user.group.color: ''">{{ lp.user.steam.personaname }}</a>
                        &middot;
                        <span v-b-tooltip.hover.right :title="lp.timestamp+' Z'">
                          <vue-timeago-js :datetime="lp.timestamp+'Z'"></vue-timeago-js>
                        </span>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="skeleton skeleton-animation-pulse" v-else>
                <div class="d-flex p-2 align-items-center" v-for="n in 5">
                  <img class="avism bone bone-type-image bone-style-round"></img>
                  <div class="w-100 ml-2">
                    <div class="bone bone-type-text"></div>
                    <div class="bone bone-type-text w-75"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-header">
              <h5 class="title my-0">{{ 'forum_statistics'|lang }}</h5>
            </div>
            <div class="card-body">
              <div v-if="forum_statistics">
                {{ forum_statistics.total_threads }} <span class="font-weight-light">thread{{ forum_statistics.total_threads != 1 ? 's': '' }}</span>
                <br>{{ forum_statistics.total_posts }} <span class="font-weight-light">post{{ forum_statistics.total_posts != 1 ? 's': '' }}</span>
                <br>{{ forum_statistics.total_users }} <span class="font-weight-light">member{{ forum_statistics.total_users != 1 ? 's': '' }}</span>
              </div>
              <div class="skeleton skeleton-animation-pulse" v-else>
                <div class="bone bone-type-multiline bone-style-paragraph"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'index',
  data: function() {
    return {
      auth: auth,
      isLoading: true,
      forum_categories: null,
      latest_posts: null,
      forum_statistics: null
    }
  },
  methods: {
    navigateToPost: function(pid) {
      var that = this;
      $.get('/forums/api/posts/'+pid)
        .done(function(res) {
          if (res.page !== 1) {
            that.$router.push({ name: 'thread_page', params: { tid: res.tid, page: res.page }, hash: '#post-'+pid })
          } else {
            that.$router.push({ name: 'thread', params: { tid: res.tid }, hash: '#post-'+pid })
          }
        },'json');
    }
  },
  mounted() {
    var that = this;
    $.get("/forums/api/index")
      .done(function(res) {
        that.isLoading = false;
        that.forum_categories = res.forum_categories;
        that.latest_posts = res.latest_posts;
        that.forum_statistics = res.forum_statistics;
      },'json');
  }
}
</script>
