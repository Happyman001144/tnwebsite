<template>
  <select name="gid" class="form-control" :value="value" @change="select">
    <option value="">None</option>
    <option v-for="group in groups" :value="group.gid">{{group.name}}</option>
  </select>
</template>

<script>
  export default {
    props: { value: [String, Number], groups: Array },
    methods: { select(evt) { this.$emit('input', evt.target.value); } }
  }
</script>
