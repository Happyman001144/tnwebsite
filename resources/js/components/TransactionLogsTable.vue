<template>
  <div class="table-responsive">
    <b-input-group class="mb-2 mr-sm-2 mb-sm-0">
      <b-input-group-prepend is-text>
        <i class='fas fa-receipt' />
      </b-input-group-prepend>
      <b-input v-model="filter_txn_id" placeholder="Transaction ID" class="border-custom" @change="getData()" />
    </b-input-group>
    <br>
    <table class="table table-bordered table-striped table-custom">
      <thead>
        <tr>
          <th scope="col">Transaction ID</th>
          <th scope="col">Status</th>
          <th scope="col">Timestamp</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in data">
          <td class="align-middle">
            <a target="_blank" :href="'https://www.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id='+item.txn_id">{{item.txn_id}}</a>
          </td>
          <td class="align-middle">{{item.status}}</td>
          <td class="align-middle">{{item.timestamp | moment("MMMM Do YYYY")}}</td>
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
        filter_txn_id: null,
        currentPage: 1,
        perPage: 10,
        totalRows: null
      }
    },
    methods: {
      getData: function (targetPage = 1) {
        var that = this
        $.ajax({type: 'GET', url: "/api/store/transactionlogs", data: { targetPage, perPage: this.perPage, filter_txn_id: this.filter_txn_id }, success: function(res) {
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
