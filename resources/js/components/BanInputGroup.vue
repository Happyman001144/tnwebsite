<template>
  <div class="input-group input-group-lg">
    <div class="input-group-prepend">
      <button @click="createBan" class="btn btn-outline-custom" onclick=""><i class="fas fa-gavel mr-1"></i>{{'ban'|lang}}</button>
    </div>
    <input v-model="steamid" maxlength="50" type="number" class="form-control border-custom" name="steamid" placeholder="SteamID64">
    <input v-model="length" maxlength="50" type="number" class="form-control border-custom" name="length" :placeholder="'ban_length_placeholder'|lang">
    <input v-model="reason" maxlength="500" type="text" class="form-control border-custom" name="reason" :placeholder="'reason_lowercase'|lang">
  </div>
</template>

<script>
  export default {
    data() {
      return {
        steamid: '',
        length: '',
        reason: ''
      }
    },
    methods: {
      createBan: function (e) {
        var that = this
        if (this.steamid == '' || this.length == '') {
          swal({
            type: 'error',
            title: 'Cannot ban user',
            text: 'SteamID or ban length missing.'
          })
        } else {
          swal({
            title: 'Confirm ban',
            text: 'Are you sure you want to ban the player?',
            type: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, ban them!'
          }).then((result) => {
            if (result.value) {
              $.ajax({type: 'POST', url: "/api/bans/new", data: { steamid: this.steamid, length: this.length, reason: this.reason }, success: function(res) {
                swal({
                  type: 'success',
                  title: 'Player banned!'
                }).then(function() { that.$root.$emit('getBans') });
              }});
            }
          });
        }
      }
    }
  }
</script>
