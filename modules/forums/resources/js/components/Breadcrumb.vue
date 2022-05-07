<template>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <router-link :to="'/forums'">{{ 'forums'|lang }}</router-link>
      </li>
      <li class="breadcrumb-item" v-if="search">
        <router-link :to="'/forums/search'">{{ 'search'|lang }}</router-link>
      </li>
      <li class="breadcrumb-item" v-if="board">
        <router-link :to="'/forums/boards/' + board.bid">{{ board.name }}</router-link>
      </li>
      <li class="breadcrumb-item" v-if="thread">
        <router-link :to="'/forums/threads/'+thread.tid">{{ thread.topic }}</router-link>
      </li>
      <li class="breadcrumb-item" v-if="this.$route == 'create'">
        {{ 'create_new_thread'|lang }}
      </li>
      <li class="ml-auto">
        <router-link :to="'/forums/search'"><i class='fas fa-search' v-b-tooltip.hover :title="'search'|lang" /></router-link>
      </li>
    </ol>
  </nav>
</template>

<script>
export default {
  name: 'breadcrumb',
  props: {
    board: Object,
    thread: Object,
    search: Boolean
  }
}
</script>
