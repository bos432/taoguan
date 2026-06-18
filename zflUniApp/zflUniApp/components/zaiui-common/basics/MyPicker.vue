<template>
  <picker mode="selector" :range="labels" :value="selectedIndex" @change="onPickerChange" :disabled="disabled" >
    <view class="picker" :class="selectedIndex>-1?'text-black-ash':'text-gray'">
      {{ selectedLabel }}
    </view>
  </picker>
</template>
<script>
export default {
  name: "MyPicker",
  props: {
    value: {
      type: [Number, String], // 根据实际情况，可以是数字或字符串
      default: null
    },
    options: {
      type: Array,
      default() {
        return [];
      }
    },
    placeholder: {
      type: String,
      default: '请选择'
    },
    props: {
      type: Object,
      default() {
        return {
          value: 'value',
          label: 'label'
        };
      }
    },
    disabled:{
      type:Boolean,
      default() {
        return false;
      }
    }
  },
  data() {
    return {
      selectedIndex: -1
    };
  },
  computed: {
    labels() {
      const safeOptions = Array.isArray(this.options) ? this.options : [];
      return safeOptions.map(option => option[this.props.label]);
    },
    selectedLabel() {
      return this.selectedIndex > -1 ? this.labels[this.selectedIndex] : this.placeholder;
    }
  },
  watch: {
    value(newVal) {
      this.updateSelectedIndex(newVal);
    },
    options: {
      handler(newOptions) {
        this.updateSelectedIndex(this.value);
      },
      deep: true
    }
  },
  mounted() {
    this.updateSelectedIndex(this.value);
  },
  methods: {
    updateSelectedIndex(value) {
      const safeOptions = Array.isArray(this.options) ? this.options : [];
      let index = -1;
      for (let i = 0; i < safeOptions.length; i++) {
        if (safeOptions[i][this.props.value] == value) {
          index = i;
          break;
        }
      }
      this.selectedIndex = index;
    },
    onPickerChange(e) {
      const safeOptions = Array.isArray(this.options) ? this.options : [];
      this.selectedIndex = e.detail.value;
      if (!safeOptions[this.selectedIndex]) {
        this.$emit('input', null);
        this.$emit('change', null);
        return;
      }
      let value = safeOptions[this.selectedIndex][this.props.value];
      this.$emit('input', value);
      this.$emit('change', safeOptions[this.selectedIndex]);
    }
  }
}
</script>



<style scoped lang="scss">

</style>
