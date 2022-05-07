<template>
  <div>
    <div class="page-header">
      <h1 class="title container" v-if="board"><i :class="'mr-2 '+board.icon" v-if="board.icon"></i>{{ board.name }}</h1>
      <h1 class="title container" v-else><i class="fas fa-sync-alt fa-spin"></i></h1>
    </div>
    <div class="container">
      <div class="d-flex mb-4" v-if="board">
        <router-link class="ml-auto" :to="'/forums/create/'+board.bid" v-if="auth.user && board.permissions_extended[auth.user.gid||0].cannot_post_thread != 1">
          <button class="btn btn-outline-custom">
            <i class="fas fa-plus mr-1"></i>{{ 'create_new_thread'|lang }}
          </button>
        </router-link>
      </div>
      <breadcrumb :board="board"></breadcrumb>
      <div class="d-flex" v-if="pagination">
        <b-pagination class="ml-auto" v-model="pagination.current" :total-rows="pagination.total" :per-page="1"></b-pagination>
      </div>
      <div class="row">
        <div class="col-12">
          <thread-list :threads="threads" :is-loading="isLoading"></thread-list>
        </div>
      </div>
      <div class="d-flex" v-if="pagination">
        <b-pagination class="ml-auto" v-model="pagination.current" :total-rows="pagination.total" :per-page="1"></b-pagination>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'board',
  data: function() {
    return {
      auth: auth,
      isLoading: true,
      board: null,
      threads: null,
      pagination: {
        current: this.$route.params.page || 1,
        total: 9999
      }
    }
  },
  methods: {
    getThreads: function() {
      var that = this;
      that.isLoading = true;
      $.get("/forums/api/boards/"+this.$route.params.bid+"/page/"+this.pagination.current)
        .done(function(res) {
          that.isLoading = false;
          that.board = res.board;
          that.threads = res.threads.data;
          that.pagination = {
            current: res.threads.current_page,
            total: res.threads.last_page
          };
          if (that.pagination.current != 1) {
            that.$router.push({ name: 'board_page', params: { bid: that.board.bid, page: that.pagination.current } })
          } else {
            that.$router.push({ name: 'board', params: { bid: that.board.bid } })
          }
          document.title = that.board.name + titleSuffix
        },'json');
    }
  },
  mounted() {
    if (this.pagination.current == 1) {
      this.getThreads();
    }
  },
  watch: {
    'pagination.current': function (val) {
      this.getThreads();
    }
  }
}
</script>
