<template>
  <div class="table-responsive">
    <b-input-group class="mb-2 mr-sm-2 mb-sm-0" v-if="!offenderSteamid">
      <b-input-group-prepend is-text>
        <i class='fab fa-steam' />
      </b-input-group-prepend>
      <b-input v-model="filterSteamID" placeholder="SteamID" class="border-custom" @change="getBans()" />
    </b-input-group>
    <br>
    <table id="bansTable" class="table table-bordered table-striped table-custom">
      <thead>
        <tr>
          <th scope="col">{{ 'date'|lang }}</th>
          <th scope="col">{{ 'server'|lang }}</th>
          <th scope="col">{{ 'offender'|lang }}</th>
          <th scope="col">{{ 'admin'|lang }}</th>
          <th scope="col">{{ 'length'|lang }}</th>
          <th scope="col">{{ 'reason'|lang }}</th>
          <th scope="col" v-if="canBan">{{ 'pardon'|lang }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="ban in bans" v-if="!isLoading">
          <td class="align-middle">{{ban.created | moment("MMMM Do YYYY")}}</td>
          <td class="align-middle">{{ban.server_name}}</td>
          <td class="align-middle" v-if="ban.offender_name">
            <profile-td :steamid="ban.offender_steamid" :name="ban.offender_name" :avatar="ban.offender_avatarfull" />
          </td>
          <td class="align-middle" v-if="!ban.offender_name">
            <i class="fas fa-user-secret mx-2"></i>{{ 'unknown'|lang }}
          </td>
          <td class="align-middle" v-if="!ban.admin_steamid">
            <i class="fas fa-terminal mr-2 ml-1"></i>{{ 'console'|lang }}
          </td>
          <td class="align-middle" v-if="ban.admin_steamid">
            <profile-td :steamid="ban.admin_steamid" :name="ban.admin_name" :avatar="ban.admin_avatarfull" />
          </td>
          <td class="align-middle" v-if="ban.expires">{{ban.expires | moment("from", ban.created, true)}}</td>
          <td class="align-middle" v-if="!ban.expires">{{ 'permanent'|lang }}</td>
          <td class="align-middle">{{ban.reason}}</td>
          <td class="align-middle" v-if="canBan">
            <div class='text-center' v-if="!ban.expired">
              <button @click="pardonBan(ban.id)" class='btn btn-outline-custom'><i class='far fa-handshake'></i></button>
            </div>
            <div class='text-center' v-if="ban.expired">
              <button @click="removeBan(ban.id)" class='btn btn-outline-custom'><i class='fas fa-trash'></i></button>
            </div>
          </td>
        </tr>
        <tr v-if="isLoading" class="skeleton skeleton-animation-pulse" v-for="n in !offenderSteamid? 10: 1">
          <td class="align-middle text-center" v-for="n in canBan? 7: 6">
            <div class="d-flex justify-content-center align-items-center" v-if="n == 3 || n === 4">
              <img class="bone bone-type-image bone-style-round" style="height: 32px; width: 32px"></img>
              <div class="bone bone-type-text ml-2"></div>
            </div>
            <div class="bone bone-type-text" v-else></div>
          </td>
        </tr>
        <tr v-if="!isLoading && totalRows === 0">
          <td class="align-middle text-center" colspan="7">
            <span v-if="!offenderSteamid">{{ 'no_records_found'|lang }}</span>
            <span v-else>{{ 'no_bans_on_record'|lang }}</span>
          </td>
        </tr>
      </tbody>
    </table>
    <br>
    <b-pagination :total-rows="totalRows" v-model="currentPage" :per-page="perPage" @change="getBans" />
  </div>
</template>

<script>
  export default {
    props: {
      canBan: Boolean,
      offenderSteamid: String
    },
    data() {
      return {
        bans: {},
        filterSteamID: null,
        currentPage: 1,
        perPage: 10,
        totalRows: null,
        isLoading: true
      }
    },
    methods: {
      getBans: function (targetPage = 1) {
        var that = this
        that.isLoading = true
        $.ajax({type: 'GET', url: "/api/bans", data: { offender_steamid: this.offenderSteamid, targetPage, perPage: this.perPage, filterSteamID: this.filterSteamID }, success: function(res) {
          that.bans = res.data
          that.currentPage = res.current_page
          that.totalRows = res.total
          that.isLoading = false
        }});
      },
      pardonBan: function (banid = null) {
        var that = this
        swal({
          title: 'Confirm pardon',
          text: 'Are you sure you want to unban the player?',
          type: 'question',
          showCancelButton: true,
          confirmButtonText: 'Yes, pardon them!'
        }).then((result) => {
          if (result.value) {
            $.ajax({type: 'POST', url: "/api/bans/pardon", data: { banid }, success: function(res) {
              swal({
                type: 'success',
                title: 'Player pardoned!'
              }).then(function() { that.getBans(that.currentPage); });
            }});
          }
        });
      },
      removeBan: function (banid = null) {
        var that = this
        swal({
          title: 'Confirm removal',
          text: 'Are you sure you want to remove the expired ban record?',
          type: 'question',
          showCancelButton: true,
          confirmButtonText: 'Yes, remove it!'
        }).then((result) => {
          if (result.value) {
            $.ajax({type: 'POST', url: "/api/bans/remove", data: { banid }, success: function(res) {
              swal({
                type: 'success',
                title: 'Ban record removed!'
              }).then(function() { that.getBans(that.currentPage); });
            }});
          }
        });
      }
    },
    beforeMount() {
      this.getBans();
    },
    mounted() {
      this.$root.$on('getBans', () => {
        this.getBans(this.currentPage);
      })
    }
  }
</script>
