<template>
  <div>
    <b-form class="mb-2">
      <b-form-row>
        <b-form-group class="col-6 col-md-2">
          <b-input class="border-custom" :placeholder="'name'|lang" v-model="query.name"></b-input>
        </b-form-group>
        <b-form-group class="col-6 col-md-3">
          <b-input-group>
            <b-input-group-prepend is-text><i class='fab fa-steam' /></b-input-group-prepend>
            <b-input class="border-custom" placeholder="SteamID" type="number" v-model="query.steamid"></b-input>
          </b-input-group>
        </b-form-group>
        <b-form-group class="col-4 col-md-2 ml-md-auto">
          <b-input-group>
            <b-input-group-prepend is-text><i class='fas fa-users' /></b-input-group-prepend>
            <b-form-select class="form-control border-custom" v-model="query.group">
              <option :value="null">{{ 'any'|lang }}</option>
              <option v-for="(v, k) in groups" :key="k" :value="v.gid">{{v.name}}</option>
            </b-form-select>
          </b-input-group>
        </b-form-group>
        <b-form-group class="col-4 col-md-2">
          <b-input-group>
            <b-input-group-prepend is-text>
              <i class='cursor-pointer fas fa-sort-amount-up' @click="query.sortOrder = 'DESC'" v-if="query.sortOrder == 'ASC'" />
              <i class='cursor-pointer fas fa-sort-amount-down' @click="query.sortOrder = 'ASC'" v-else />
            </b-input-group-prepend>
            <b-form-select class="form-control border-custom" v-model="query.orderBy" :options="{ 'created': 'Joined', 'last_played': 'Last played', 'last_online': 'Last seen', 'gid': 'Group' }"></b-form-select>
          </b-input-group>
        </b-form-group>
        <b-form-group>
          <button @click.prevent="getUsers()" class="btn btn-outline-custom"><i class="fas fa-search mr-1"></i>Search</button>
        </b-form-group>
      </b-form-row>
    </b-form>
    <div class="row">
      <div class="col-md-3 col-12" v-for="user in users" v-if="!isLoading && users != null">
        <div class="card hoverscale mb-3">
          <a class="nounderline nohover" :href="'/profile/'+user.steamid">
            <div class="card-body row">
              <div class="col-3">
                <div :class="'avatar avatar-48 avatar-'+user.status">
                  <img :src="user.steam.avatarmedium">
                </div>
              </div>
              <div class="col-9 my-auto text-nowrap overflow-hidden">
                <h5 class="mb-0">{{ user.steam.personaname }}</h5>
                <h6 v-if="user.group">
                  <span class="title" :style="'color:'+user.group.color">
                      <i :class="user.group.icon+' mr-1'" v-if="user.group.icon"></i>
                    {{ user.group.name }}
                  </span>
                </h6>
              </div>
            </div>
          </a>
        </div>
      </div>
      <p class="mx-auto" v-if="!isLoading && users.length < 1">
        {{ 'no_results'|lang }}
      </p>
      <div class="col-md-3 col-12" v-for="n in 20" v-if="isLoading">
        <div class="card hoverscale mb-3 skeleton skeleton-animation-pulse">
          <div class="card-body row">
            <div class="col-3">
              <img class="bone bone-type-image bone-style-round"></img>
            </div>
            <div class="col-9">
              <div class="mr-3">
                <div class="bone bone-type-text"></div>
              </div>
              <div class="mr-5">
                <div class="bone bone-type-text"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <b-pagination :total-rows="totalRows" v-model="currentPage" :per-page="perPage" @change="getUsers" />
  </div>
</template>

<script>
  export default {
    props: {
      groups: Array
    },
    data() {
      return {
        isLoading: false,
        users: {},
        currentPage: 1,
        perPage: 20,
        totalRows: null,
        query: {
          name: null,
          steamid: null,
          group: null,
          sortOrder: 'DESC',
          orderBy: 'created'
        }
      }
    },
    methods: {
      getUsers: function (targetPage = 1) {
        if (this.isLoading) { return; }
        var that = this;
        that.isLoading = true;
        $.ajax({type: 'GET', url: "/api/users", data: { targetPage, perPage: this.perPage, name: this.query.name, steamid: this.query.steamid, gid: this.query.group, sortOrder: this.query.sortOrder, orderBy: this.query.orderBy }, success: function(res) {
          that.isLoading = false;
          that.users = res.data,
          that.currentPage = res.current_page,
          that.totalRows = res.total
        }});
      }
    },
    beforeMount() {
      this.getUsers();
    }
  }
</script>
