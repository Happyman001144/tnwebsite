<template>
  <div>
    <div class="page-header">
      <h1 class="title container">{{ 'create_new_thread'|lang }}</h1>
    </div>
    <div class="container">
      <breadcrumb></breadcrumb>
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <input maxlength="1000" id="thread-topic" type="text" class="form-control" :placeholder="'thread_topic'|lang" v-model="topic">
            </div>
            <div class="card-body">
              <quill-editor v-model="content" :options="editorOption"></quill-editor>
            </div>
            <div class="card-footer">
              <button @click="postThread()" class="btn post-btn btn-outline-custom float-right"><i class="fas fa-plus mr-1"></i>{{ 'post_thread'|lang }}</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'create',
  data: function() {
    return {
      bid: this.$route.params.bid,
      topic: '',
      content: '',
      posting: false,
      editorOption: {
        theme: 'snow',
        placeholder: '',
        modules: {
          toolbar: [
            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
            ['bold', 'italic'],
            ['link','blockquote','code','code-block'],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            ['clean']
          ]
        }
      }
    }
  },
  methods: {
    postThread: function () {
      var that = this;
      if (this.posting == true) { return } else { this.posting = true }
      if (this.topic == '' || this.content == '') {
        swal({
          type: 'error',
          title: 'Cannot submit thread',
          text: 'Topic or content missing.'
        })
      } else {
        $.post("/forums/create", { bid: this.bid, topic: this.topic, content: this.content })
          .done(function(response) {
            that.$router.replace({ name: 'thread', params: { tid: response.tid } })
          },'json');
      }
    }
  }
}
</script>
