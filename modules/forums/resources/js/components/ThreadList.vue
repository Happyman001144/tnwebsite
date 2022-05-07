<template>
  <div>
    <div class="card" v-if="!isLoading">
      <div v-for="(thread, index, name) in threads">
        <hr class="my-1" v-if="index != 0"><div class="mt-1" v-else></div>
        <div class="d-flex">
          <div class="p-2">
            <a :href="'/profile/'+thread.user.steamid">
              <img class="avimd" :src="thread.user.steam.avatarfull"></img>
            </a>
          </div>
          <div class="p-2">
            <router-link :to="{ name: 'thread', params: { tid: thread.tid }}">
              <h5 class="mb-0" :class="thread.read_timestamp && thread.last_posted < thread.read_timestamp ? 'font-weight-light': ''">
                <i class="fas fa-thumbtack mr-1" v-if="thread.pinned"></i>
                <i class="fas fa-lock mr-1" v-if="thread.locked"></i>
                {{ thread.topic }}
              </h5>
            </router-link>
            <span class="font-weight-light">
              <a :href="'/profile/'+thread.user.steamid" :style="thread.user.group ? 'color: '+thread.user.group.color: ''">{{ thread.user.steam.personaname }}</a>
              &middot;
              <span v-b-tooltip.hover.right :title="thread.timestamp+' Z'">
                <vue-timeago-js :datetime="thread.timestamp+'Z'"></vue-timeago-js>
              </span>
            </span>
          </div>
          <div class="ml-auto"></div>
          <span class="font-weight-light" v-if="thread.postcount > 1">
            <div class="p-2 text-right">
              <a :href="'/profile/'+thread.latest_post.user.steamid" :style="thread.latest_post.user.group ? 'color: '+thread.latest_post.user.group.color: ''">{{ thread.latest_post.user.steam.personaname }}</a>
              <br>
              <span v-b-tooltip.hover.left :title="thread.latest_post.timestamp+' Z'">
                <vue-timeago-js :datetime="thread.latest_post.timestamp+'Z'"></vue-timeago-js>
              </span>
            </div>
          </span>
          <div class="p-2" v-if="thread.postcount > 1">
            <a :href="'/profile/'+thread.latest_post.user.steamid">
              <img class="avimd" :src="thread.latest_post.user.steam.avatarfull"></img>
            </a>
          </div>
          <div class="p-2 align-self-center">
            {{ thread.postcount-1 }} <span class="font-weight-light">{{ 'replies'|lang }}</span>
          </div>
        </div>
      </div>
    </div>
    <div class="card skeleton skeleton-animation-pulse" v-if="isLoading">
      <div v-for="(thread, index, name) in 25">
        <hr class="my-1" v-if="index != 0"><div class="mt-1" v-else></div>
        <div class="d-flex align-items-center">
          <div class="p-2">
            <img class="avimd bone bone-type-image bone-style-round"></img>
          </div>
          <div class="p-2 w-100">
            <div class="bone bone-type-text w-75"></div>
            <div class="bone bone-type-text w-50"></div>
          </div>
          <div class="font-weight-light w-50">
            <div class="bone bone-type-text w-50 ml-auto"></div>
            <div class="bone bone-type-text"></div>
          </div>
          <div class="p-2">
            <img class="avimd bone bone-type-image bone-style-round"></img>
          </div>
          <div class="p-2 align-self-center w-25">
            <div class="bone bone-type-text"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    threads: Array,
    isLoading: Boolean
  }
}
</script>
