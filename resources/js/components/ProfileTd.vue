<template>
  <a class="nounderline" :href="`/profile/${steamid}`">
    <img class="avism" :src="avatar">
    {{name}}
  </a>
</template>

<script>
  export default {
    props: { steamid: String, name: String, avatar: String }
  }
</script>
