<template>
  <select :required="required" name="server" class="form-control" :value="value" @change="select">
    <option value="" v-if="!required">None</option>
    <option v-for="server in servers" :value="server.id">{{server.name}}</option>
  </select>
</template>

<script>
  export default {
    props: { value: [String, Number], servers: Array, required: Boolean },
    methods: { select(evt) { this.$emit('input', evt.target.value); } }
  }
</script>
