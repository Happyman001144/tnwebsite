<template>
  <div>
    <div class="page-header">
      <h1 class="title container">{{ 'search'|lang }}</h1>
    </div>
    <div class="container">
      <breadcrumb :search="true"></breadcrumb>
      <div class="">
        <div class="card">
          <div class="card-header">
            <h5 class="title">{{ 'search_options'|lang }}</h5>
          </div>
          <div class="card-body row">
            <div class="col-md-10 col-12 mb-md-0 mb-2">
              <input type="text" class="form-control" v-model="searchParams.keyword" :placeholder="'forum_search_placeholder'|lang" @keyup.enter="getThreads()">
            </div>
            <div class="col-md-2 col-12 text-center">
              <button class="btn btn-outline-custom" @click="getThreads()">
                <i class="fas fa-search mr-1"></i>{{ 'search'|lang }}
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-12">
          <thread-list :threads="threads" :is-loading="isLoading" v-if="threads.length > 0 || isLoading"></thread-list>
          <div class="card pt-3 text-center" v-else>
            <p>{{ 'no_results'|lang }}</p>
          </div>
        </div>
      </div>
      <div class="d-flex" v-if="threads.length > 0 && pagination">
        <b-pagination class="ml-auto" v-model="pagination.current" :total-rows="pagination.total" :per-page="1"></b-pagination>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'search',
  data: function() {
    return {
      auth: auth,
      searchParams: {
        keyword: ''
      },
      isLoading: false,
      threads: new Array,
      pagination: {
        current: this.$route.params.page || 1,
        total: 9999
      }
    }
  },
  methods: {
    getThreads: function() {
      if (this.searchParams.keyword == '') { return; }
      var that = this;
      that.isLoading = true;
      $.get("/forums/api/search?topic="+this.searchParams.topic+"&page="+this.pagination.current+"&keyword="+this.searchParams.keyword)
        .done(function(res) {
          that.isLoading = false;
          that.threads = res.threads.data;
          that.pagination = {
            current: res.threads.current_page,
            total: res.threads.last_page
          };
        },'json');
    }
  },
  watch: {
    'pagination.current': function (val) {
      this.getThreads();
    }
  }
}
</script>
