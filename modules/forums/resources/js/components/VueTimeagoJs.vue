<template>
  <span>{{ formattedTime }}</span>
</template>

<script>
import { format, render, cancel, register } from 'timeago.js';
export default {
  data: function() {
    return {
      timer: null,
      formattedTime: null
    }
  },
  props: {
    datetime: String
  },
  methods: {
    formatTime: function() {
      this.formattedTime = format(this.datetime, 'en_US');
    }
  },
  mounted() {
    this.formatTime();
    this.timer = setInterval(function() { this.formatTime() }.bind(this), 1000)
  },
  beforeDestroy() {
    clearInterval(this.timer)
  }
}
</script>
