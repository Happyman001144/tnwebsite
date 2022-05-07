<template>
  <div class="col-auto" data-aos="fade-up">
    <div class="card">
      <div class="card-header">
        <h5 class="my-0">{{ 'administration'|lang }}</h5>
      </div>
      <div class="card-body">
        <div class="form-row">
          <div class="form-group col-12 col-md-2">
            <label for="group"><i class="fas fa-users mr-1"></i>Group</label>
            <select id="group" class="form-control" v-model="user.gid" @change="changeGroup">
              <option :value="null">{{ 'none'|lang }}</option>
              <option v-for="(v, k) in groups" :key="k" :value="v.gid">{{v.name}}</option>
            </select>
          </div>
          <div class="form-group col-6 col-md-2">
            <label for="credits"><i class="fas fa-coins mr-1"></i>{{ 'credits'|lang }}</label>
            <input class="form-control" type="number" id="credits" placeholder="0" v-model="credits" @change="updateCredits">
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  export default {
    props: {
      user: Object,
      initialCredits: Number,
      groups: Array
    },
    data() {
      return {
        credits: this.initialCredits
      }
    },
    methods: {
      changeGroup: function (steamid = null) {
        $.ajax({type: 'POST', url: "/api/users/changegroup", data: { steamid: this.user.steamid, gid: this.user.gid }, success: function(res) {
          swal({
            type: 'success',
            title: 'Group changed',
            showConfirmButton: false,
            timer: 1000
          })
          location.reload()
        }})
      },
      updateCredits: function () {
        if (this.credits == '') { this.credits = 0 }
        $.ajax({type: 'POST', url: "/api/users/updatecredits", data: { steamid: this.user.steamid, amount: this.credits }, success: function(res) {
          swal({
            type: 'success',
            title: 'Credits updated',
            showConfirmButton: false,
            timer: 1000
          })
        }})
      }
    }
  }
</script>
