<template>
  <div>
    <div class="page-header">
      <h1 class="title container" v-if="thread.topic"></i>{{ thread.topic }}</h1>
      <h1 class="title container" v-else><i class="fas fa-sync-alt fa-spin"></i></h1>
    </div>
    <div class="container">
      <breadcrumb :board="board" :thread="thread"></breadcrumb>
      <div class="d-flex">
        <b-pagination class="ml-auto" v-model="page" :key="page" :total-rows="totalPages" :per-page="1"></b-pagination>
      </div>
      <div class="card skeleton skeleton-animation-pulse" v-for="n in 3" v-if="isLoading || !posts">
        <div class="d-flex">
          <div class="post-profile p2 d-none d-md-block">
            <div class="justify-content-center text-center mb-2">
              <img class="profilecircle bone bone-type-image bone-style-round mt-3"></img>
            </div>
            <div class="mx-3">
              <div class="bone bone-type-text mt-3"></div>
            </div>
            <div class="mx-5">
              <div class="bone bone-type-text"></div>
            </div>
          </div>
          <div class="flex-grow-1 d-flex flex-column">
            <div class="card-header post-header">
              <div class="d-md-none text-center d-flex justify-content-center align-items-center">
                <img class="avimd bone bone-type-image bone-style-round"></img>
                <div class="bone bone-type-text width-quarter ml-2"></div>
              </div>
              <div class="d-flex">
                <div class="p-2">
                  <div class="bone bone-type-text">timestamp</div>
                </div>
                <div class="ml-auto p-2">
                   <div class="bone bone-type-text">#x</div>
                </div>
              </div>
            </div>
            <div class="card-body">
              <div class="bone bone-type-multiline bone-style-paragraph"></div>
            </div>
            <div class="card-footer post-footer">
              reactions
            </div>
          </div>
        </div>
      </div>
      <div class="card" :id="'post-'+post.pid" v-for="(post, key, index) in posts" :key="post.pid" v-if="!isLoading">
        <div class="d-flex">
          <div class="post-profile p2 d-none d-md-block">
            <div class="justify-content-center text-center mb-2">
              <a class="nounderline nohover" :href="'/profile/'+post.user.steamid">
                <div :class="'avatar avatar-bordered avatar-lg avatar-'+post.user.status+' mt-2 mb-1'">
									<img :src="post.user.steam.avatarfull">
								</div>
                <h5 class="title my-0">{{ post.user.steam.personaname }}</h5>
                <h6 class="title mb-0" :style="'color: '+post.user.group.color" v-if="post.user.group">
                  <i :class="post.user.group.icon+' mr-1'"></i>
                  {{ post.user.group.name }}
                </h6>
                <small class="text-muted title">
                  <span class="mr-1">{{ post.user.post_count|number_format }}</span>
                  <span v-if="post.user.post_count != 1">{{ 'posts'|lang }}</span>
                  <span v-else>{{ 'post'|lang }}</span>
                </small>
              </a>
            </div>
          </div>
          <div class="flex-grow-1 d-flex flex-column">
            <div class="card-header post-header">
              <div class="d-md-none text-center">
                <a class="nounderline nohover" target="_blank" :href="'/profile/'+post.user.steamid">
                  <div :class="'avatar avatar-md avatar-'+post.user.status">
  									<img :src="post.user.steam.avatarfull">
  								</div>
                  <span class="title">{{ post.user.steam.personaname }}</span>
                  <span v-if="post.user.group">
                     &middot; <span class="title" :style="'color: '+post.user.group.color"><i :class="post.user.group.icon+' mr-1'"></i>{{ post.user.group.name }}</span>
                  </span>
                </a>
                <hr class="my-2">
              </div>
              <div class="d-flex">
                <div class="p-2">
                  <span v-if="thread.steamid == post.user.steamid">
                    <span class="badge badge-primary">OP</span> &middot;
                  </span>
                  <span v-b-tooltip.hover.right :title="post.timestamp+' Z'">
                    <vue-timeago-js :datetime="post.timestamp+'Z'"></vue-timeago-js>
                  </span>
                  <span v-b-tooltip.hover.right :title="post.last_edit+' Z'" v-if="post.last_edit"><!--|date('j F Y, G:i')-->
                    <span class="text-muted font-weight-light">&middot; last modified <vue-timeago-js :datetime="post.last_edit+'Z'"></vue-timeago-js></span>
                  </span>
                </div>
                <div class="ml-auto p-2">
                   #{{ post.pid }}
                </div>
              </div>
            </div>
            <div class="card-body">
              <blockquote v-if="post.quoted_post">
                <p class="d-flex">
                  <span><profile-td :style="post.quoted_post.user.group ? 'color: '+post.quoted_post.user.group.color: ''" :steamid="post.quoted_post.user.steamid" :name="post.quoted_post.user.steam.personaname" :avatar="post.quoted_post.user.steam.avatarfull"></profile-td> posted:</span>
                  <span class="ml-auto" v-b-toggle="'quote-'+post.quoted_post.pid" style="cursor: pointer">
                    <i class="fas fa-chevron-up when-opened"></i>
                    <i class="fas fa-chevron-down when-closed"></i>
                  </span>
                </p>
                <b-collapse visible :id="'quote-'+post.quoted_post.pid">
                  <vue-markdown class="mt-2 quoted-post-content" :source="post.quoted_post.content"></vue-markdown>
                </b-collapse>
              </blockquote>
              <span class="post-content">
                <vue-markdown :source="post.content"></vue-markdown>
              </span>
            </div>
            <div class="card-footer post-footer">
              <div class="d-flex flex-wrap">
                <div class="d-flex align-self-center m-auto m-lg-0 mr-lg-2" v-if="reactions_enabled">
                  <span v-for="(rEmoji, rName) in reactions" :key="rName">
                    <div
                      @click="reactToPost(rName,post)"
                      :class="(post.reactions && post.reactions[rName] && post.reactions[rName].length > 0 ? 'reaction-active ' : '') +'reaction mr-2'"
                      v-b-tooltip.hover.bottom :title="rName|capitalize"
                    >
                      {{ rEmoji }}<span :id="'reaction-'+rName+'-'+post.pid">
                        {{ post.reactions && post.reactions[rName] && post.reactions[rName].length > 0 ? post.reactions[rName].length : null }}
                      </span>
                    </div>
                  </span>
                </div>
                <div class="d-flex m-auto m-lg-0 ml-lg-auto" v-if="auth.user != null">
                  <div class="mr-2" v-if="!thread.locked">
                    <button @click="quotePost(post)" class="btn btn-outline-custom float-right"><i class="fas fa-comment-alt mr-1"></i>{{ 'quote'|lang }}</button>
                  </div>
                  <div class="d-flex" v-if="post.user.steamid == auth.user.steamid || auth.canModerateForums">
                    <div class="mr-2" v-if="!thread.locked && post.user.steamid == auth.user.steamid">
                      <button @click="editPost(post)" class="btn btn-outline-custom float-right"><i class="fas fa-edit mr-1"></i>{{ 'edit'|lang }}</button>
                    </div>
                    <div class="mr-2" v-if="auth.canModerateForums && index === 0 && page == 1">
                      <button @click="moveThread()" class="btn btn-outline-custom float-right"><i class="fas fa-people-carry mr-1"></i>{{ 'move_thread'|lang }}</button>
                    </div>
                    <button @click="deletePost(post,index)" class="btn btn-outline-custom float-right"><i class="fas fa-times mr-1"></i>{{ 'delete'|lang }} <span v-if="index === 0 && page == 1">{{ 'thread'|lang }}</span></button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="d-flex">
        <b-pagination class="ml-auto" v-model="page" :key="page" :total-rows="totalPages" :per-page="1"></b-pagination>
      </div>
      <div v-if="!isLoading && posts">
        <div v-if="!thread.locked">
          <div v-if="auth.user">
            <div class="card" id="reply-card" v-if="board.permissions_extended[auth.user.gid||0].cannot_post_reply != 1">
              <div class="card-body">
                <span v-if="reply_to_post && !editing_post">
                  <div class="d-flex">
                    <p class="title">Quoting post #{{ reply_to_post.pid }}</p>
                    <button @click="quotePost(null)" class="btn btn-outline-custom float-right ml-auto"><i class="fas fa-times mr-1"></i>{{ 'cancel'|lang }}</button>
                  </div>
                  <blockquote>
                    <p class="d-flex">
                      <span><profile-td :style="reply_to_post.user.group ? 'color: '+reply_to_post.user.group.color: ''" :steamid="reply_to_post.user.steamid" :name="reply_to_post.user.steam.personaname" :avatar="reply_to_post.user.steam.avatarfull"></profile-td> posted:</span>
                      <span class="ml-auto" v-b-toggle="'quote-'+reply_to_post.pid" style="cursor: pointer">
                        <i class="fas fa-chevron-up when-opened"></i>
                        <i class="fas fa-chevron-down when-closed"></i>
                      </span>
                    </p>
                    <b-collapse visible :id="'quote-'+reply_to_post.pid">
                      <vue-markdown class="mt-2 quoted-post-content" :source="reply_to_post.content"></vue-markdown>
                    </b-collapse>
                  </blockquote>
                </span>
                <span v-if="editing_post">
                  <p class="title">Editing post #{{ editing_post }}</p>
                </span>
                <quill-editor v-model="content" :options="editorOption" v-if="!editing_post"></quill-editor>
                <quill-editor v-model="edit_content" :options="editorOption" v-if="editing_post"></quill-editor>
              </div>
              <div class="card-footer d-flex">
                <div v-if="auth.canModerateForums">
                  <button @click="toggleThreadState('1','locked')" class="btn btn-outline-custom mr-2">
                    <i class="fas fa-lock mr-1"></i>{{ 'lock_thread'|lang }}
                  </button>
                  <button @click="toggleThreadState('1','pinned')" class="btn btn-outline-custom" v-if="!thread.pinned">
                    <i class="fas fa-thumbtack mr-1"></i>{{ 'pin_thread'|lang }}
                  </button>
                  <button @click="toggleThreadState('0','pinned')" class="btn btn-outline-custom" v-else>
                    <i class="fas fa-thumbtack mr-1"></i>{{ 'unpin_thread'|lang }}
                  </button>
                </div>
                <span id="reply-buttons" class="ml-auto" v-if="!editing_post">
                  <button @click="postReply()" class="btn btn-outline-custom"><i class="fas fa-plus mr-1"></i>{{ 'post_reply'|lang }}</button>
                </span>
                <span id="edit-buttons" class="ml-auto" v-if="editing_post">
                  <div class="d-flex">
                    <div class="mr-2">
                      <button @click="postEdits()" class="btn btn-outline-custom"><i class="fas fa-edit mr-1"></i>{{ 'post_changes'|lang }}</button>
                    </div>
                    <button @click="editPost(null)" class="btn btn-outline-custom"><i class="fas fa-times mr-1"></i>{{ 'cancel_editing'|lang }}</button>
                  </div>
                </span>
              </div>
            </div>
          </div>
          <div v-else>
            <div class="card">
              <div class="card-body d-flex justify-content-center">
                <i class="fas fa-sign-in-alt fa-5x align-self-center mr-2"></i>
                <h1 class="align-self-center title" style="color: inherit !important;">{{ 'sign_in_to_reply'|lang }}</h1>
              </div>
            </div>
          </div>
        </div>
        <div v-else>
          <div class="card">
            <div class="card-body d-flex justify-content-center">
              <i class="fas fa-lock fa-5x align-self-center mr-2"></i>
              <h1 class="align-self-center title" style="color: inherit !important;">{{ 'thread_has_been_locked'|lang }}</h1>
            </div>
            <div class="card-footer d-flex" v-if="auth.canModerateForums">
              <span id="reply-buttons" class="ml-auto">
                <button @click="toggleThreadState('0','locked')" class="btn btn-outline-custom mr-2">
                  <i class="fas fa-lock-open mr-1"></i>{{ 'unlock_thread'|lang }}
                </button>
                <button @click="toggleThreadState('1','pinned')" class="btn btn-outline-custom" v-if="!thread.pinned">
                  <i class="fas fa-thumbtack mr-1"></i>{{ 'pin_thread'|lang }}
                </button>
                <button @click="toggleThreadState('0','pinned')" class="btn btn-outline-custom" v-else>
                  <i class="fas fa-thumbtack mr-1"></i>{{ 'unpin_thread'|lang }}
                </button>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
var mentionAutoCompleteCache = {};
var mdConverter = new showdown.Converter();

export default {
  name: 'thread',
  data: function() {
    return {
      auth: auth,
      isLoading: false,
      page: this.$route.params.page || 1,
      totalPages: 9999,
      tid: null,
      bid: null,
      posts: null,
      board: {},
      thread: {},
      content: '',
      posting: false,
      editing_post: false,
      edit_content: null,
      reply_to_post: null,
      reactions: {
          'agree': '✔️',
          'disagree': '❌',
          'funny': '😂',
          'winner': '🥇',
          'zing': '⚡',
          'friendly': '❤️',
          'useful': '🔧',
          'optimistic': '🌈',
          'artistic': '🎨',
          'late': '🕗',
          'dumb': '📦'
      },
      reactions_enabled: false,
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
          ],
          mention: {
            allowedChars: /^[A-Za-z\sÅÄÖåäö]*$/,
            mentionDenotationChars: ['@'],
            showDenotationChar: false,
            renderItem: (item, searchTerm) => {
              return `
                <div class="cql-list-item-inner">
                  <img class="avism" src="${item.avatarfull}">
                  ${item.name}
                </div>`;
            },
            onSelect: (item, insertItem) => {
              insertItem(item);
            },
            source: async function (searchTerm, renderList, mentionChar) {
              let values;
              if (searchTerm.length === 0) {
                renderList(values, searchTerm);
              } else {
                if(mentionAutoCompleteCache[searchTerm] == null) {
                  var returnArr = new Array();
                  $.ajax({url: "/forums/mentionAutoComplete", method: 'POST', async: false, data: { matchStr: searchTerm }})
                    .done(function(response) {
                      $(response.results).each(function (i) {
                        var user = response.results[i];
                        returnArr.push({
                          id: user.steamid,
                          value: user.name,
                          link: '/profile/'+user.steamid,
                          avatarfull : user.steam.avatarfull,
                          name: user.name
                        });
                      });
                    },'json');
                  mentionAutoCompleteCache[searchTerm] = returnArr;
                }
                renderList(mentionAutoCompleteCache[searchTerm], searchTerm);
              }
            },
          },
        }
      }
    };
  },
  filters: {
    capitalize: function (value) {
      if (!value) return ''
      value = value.toString()
      return value.charAt(0).toUpperCase() + value.slice(1)
    },
    number_format: function (value) {
      return (value).toFixed(0).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }
  },
  methods: {
    getPosts: function () {
      if (this.isLoading) { return; }
      var that = this;
      that.isLoading = true;
      $.get("/forums/api/threads/"+this.tid+"/page/"+this.page)
        .done(function(res) {
          that.isLoading = false;
          var posts = res.posts;
          for (var k in posts) {
            posts[k].content = posts[k].content.replace(/</g, '&#60;').replace(/&#60;mention+/g, '<mention').replace(/&#60;\/mention+/g, '</mention');
            if (posts[k].quoted_post) {
              posts[k].quoted_post.content = posts[k].quoted_post.content.replace(/</g, '&#60;');
            }
          }
          that.posts = posts;
          that.totalPages = res.pagination.total;
          that.board = res.board;
          that.thread = res.thread;
          that.reactions_enabled = res.reactions_enabled;

          var method = 'push';
          if(window.location.hash) {
            method = 'replace';
            var hash = window.location.hash
            that.$nextTick(() => {
              $([document.documentElement, document.body]).animate({
                scrollTop: $(hash).offset().top-70
              }, 500);
            })
          }

          if (that.page != 1) {
            that.$router[method]({ name: 'thread_page', params: { tid: that.tid, page: that.page } })
          } else {
            that.$router[method]({ name: 'thread', params: { tid: that.tid } })
          }

          document.title = that.thread.topic + titleSuffix
        },'json');
    },
    postReply: function (postBtn) {
      var that = this;
      if (this.posting == true) { return } else { this.posting = true }
      if (this.content == '') {
        swal({
          type: 'error',
          title: 'Cannot submit reply',
          text: 'Content missing.'
        })
      } else {
        $(postBtn).prop('disabled', true);
        var reply_to_pid = null;
        if (this.reply_to_post) {
          reply_to_pid= this.reply_to_post.pid;
        }
        $.post("/forums/reply", { tid: this.tid, content: this.content, reply_to_pid: reply_to_pid })
          .done(function(res) {
            that.content = null;
            that.reply_to_post = null;
            that.posting = false;
            if (that.page != res.page) {
              if (that.totalPages < res.page) {
                that.totalPages = res.page;
              }
              that.page = res.page;
            } else {
              that.getPosts();
            }
          },'json');
      }
    },
    deletePost: function (post, index) {
      var that = this;

      var thing = 'post';
      if (this.page == 1 && index === 0) {
        thing = 'thread';
      }

      swal({
        title: 'Confirm deleting '+thing,
        text: 'Are you sure you want to delete this '+thing+'?',
        type: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.value) {
          $.ajax({ url: "/forums/delete", type: 'DELETE', data: { tid: that.tid, pid: post.pid }, success: function(res) {
            if (res.status === 'success') {
              if (thing === 'thread') {
                that.$router.replace({ name: 'board', params: { bid: that.board.bid } })
              } else {
                that.getPosts();
              }
            }
          }});
        }
      });
    },
    quotePost: function (post) {
      this.reply_to_post = post;
      $([document.documentElement, document.body]).animate({
        scrollTop: $("#reply-card").offset().top
      }, 500);
    },
    reactToPost: function (rName, post) {
      if (auth.user.steamid === post.user.steamid) {
        return swal({
          type: 'error',
          title: 'Failed to add reaction',
          text: 'You cannot add reactions to your own posts.',
        })
      }
      var that = this;
      $.post("/forums/react", { rname: rName, pid: post.pid })
        .done(function(res) {
          if (res.status === 'success') {
            var newVal = null;
            if (res.reactions[rName] != null) {
              newVal = [res.reactions[rName]]
            }
            Vue.set(that.posts[post.pid].reactions, rName, newVal)
          } else if (res.status === 'max_reactions_reached') {
            swal({
              type: 'error',
              title: 'Reaction limit reached',
              text: 'You cannot add more reactions to that post.',
            })
          }
        },'json');
    },
    toggleThreadState: function (val,state) {
      var that = this;
      $.post( "/forums/toggleThreadState", { tid: this.tid, val: val, state: state })
        .done(function(res) {
          if (val == 0) { val = null }
          that.thread[state] = val;
        },'json');
    },
    moveThread: async function () {
      const {value: newBid} = await swal({
        title: 'Select a board',
        input: 'select',
        inputOptions: boardOptions,
        showCancelButton: true
      })

      if (newBid) {
        var that = this;
        $.post( "/forums/moveThread", { tid: this.tid, bid: newBid })
          .done(function(res) {
            that.getPosts();
          },'json');
      }
    },
    editPost: function (post) {
      if (post != null) {
        this.editing_post = post.pid;
        this.edit_content = mdConverter.makeHtml(post.content);
      } else {
        this.editing_post = false;
      }
      $([document.documentElement, document.body]).animate({
        scrollTop: $("#reply-card").offset().top
      }, 500);
    },
    postEdits: function () {
      var that = this;
      if (this.edit_content == '') {
        swal({
          type: 'error',
          title: 'Cannot submit changes',
          text: 'Content missing.'
        })
      } else {
        $.post("/forums/edit", { pid: this.editing_post, content: this.edit_content })
          .done(function(res) {
            that.editPost(null)
            that.getPosts()
          },'json');
      }
    }
  },
  mounted() {
    this.tid = this.$route.params.tid;
    this.getPosts();
  },
  watch: {
    page: function (val) {
      this.getPosts();
    }
  }
}
</script>
