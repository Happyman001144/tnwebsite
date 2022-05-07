<!--

This file incorporates work covered by the following copyright and permission notice:

  Copyright (c) 2019 by Lay (https://codepen.io/Brownsugar/pen/NaGPKy)

  Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

  The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

-->

<template>
  <div class="input-group color-picker" ref="colorpicker">
  <input type="text" class="form-control border-custom" v-model="colorValue" @focus="showPicker()" @input="updateFromInput" />
  	<div class="input-group-append color-picker-container">
      <span class="input-group-text">
        <i class="fas fa-palette mr-1" :style="`cursor: pointer; color: `+colorValue" @click="togglePicker()"></i>
    		<chrome-picker :value="colors" @input="updateFromPicker" v-if="displayPicker" />
      </span>
  	</div>
  </div>
</template>

<script>
  import { Chrome } from 'vue-color'
  export default {
  	props: ['color'],
    components: {
      'chrome-picker': Chrome
    },
  	data() {
  		return {
  			colors: {
  				hex: '#007BFF',
  			},
  			colorValue: '',
  			displayPicker: false,
  		}
  	},
  	mounted() {
  		this.setColor(this.color || '#007BFF');
  	},
  	methods: {
  		setColor(color) {
  			this.updateColors(color);
  			this.colorValue = color;
  		},
  		updateColors(color) {
  			this.colors = {
  				hex: color
  			};
  		},
  		showPicker() {
  			document.addEventListener('click', this.documentClick);
  			this.displayPicker = true;
  		},
  		hidePicker() {
  			document.removeEventListener('click', this.documentClick);
  			this.displayPicker = false;
  		},
  		togglePicker() {
  			this.displayPicker ? this.hidePicker() : this.showPicker();
  		},
  		updateFromInput() {
  			this.updateColors(this.colorValue);
  		},
  		updateFromPicker(color) {
				this.colorValue = color.hex;
  		},
  		documentClick(e) {
  			var el = this.$refs.colorpicker,
  				target = e.target;
  			if(el !== target && !el.contains(target)) {
  				this.hidePicker()
  			}
  		}
  	},
  	watch: {
  		colorValue(val) {
  			if(val) {
  				this.updateColors(val);
  				this.$emit('input', val);
  			}
  		}
  	}
  }
</script>

<style scoped>
  .vc-chrome {
  	position: absolute;
  	top: 35px;
  	right: 0;
  	z-index: 9;
  }
</style>
