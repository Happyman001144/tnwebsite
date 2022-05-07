<template>
  <div class="table-responsive">
    <b-input-group class="mb-2 mr-sm-2 mb-sm-0">
      <b-input-group-prepend is-text>
        <i class='fab fa-steam' />
      </b-input-group-prepend>
      <b-input v-model="filterSteamID" placeholder="SteamID" class="border-custom" @change="getData()" />
    </b-input-group>
    <br>
    <table class="table table-bordered table-striped table-custom">
      <thead>
        <tr>
          <th scope="col">Purchase ID</th>
          <th scope="col">Buyer</th>
          <th scope="col">Package ID</th>
          <th scope="col">Package name</th>
          <th scope="col">Redeemed</th>
          <th scope="col">Timestamp</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in data">
          <td class="align-middle">{{item.id}}</td>
          <td class="align-middle">
            <profile-td :steamid="item.user.steamid" :name="item.user.name" :avatar="item.user.steam.avatarfull" />
          </td>
          <td class="align-middle">{{item.storePackage.id}}</td>
          <td class="align-middle">{{item.storePackage.name}}</td>
          <td class="align-middle">{{item.redeemed ? 'Yes': 'No'}}</td>
          <td class="align-middle">{{item.purchase_timestamp | moment("MMMM Do YYYY")}}</td>
        </tr>
      </tbody>
    </table>
    <br>
    <b-pagination :total-rows="totalRows" v-model="currentPage" :per-page="perPage" @change="getData" />
  </div>
</template>

<script>
  export default {
    data() {
      return {
        data: {},
        filterSteamID: null,
        currentPage: 1,
        perPage: 10,
        totalRows: null
      }
    },
    methods: {
      getData: function (targetPage = 1) {
        var that = this
        $.ajax({type: 'GET', url: "/api/store/packagepurchases", data: { targetPage, perPage: this.perPage, filterSteamID: this.filterSteamID }, success: function(res) {
          that.data = res.data,
          that.currentPage = res.current_page,
          that.totalRows = res.total
        }});
      }
    },
    beforeMount() {
      this.getData();
    }
  }
</script>
