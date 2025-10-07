<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faHardHat } from '@fortawesome/free-solid-svg-icons';

defineOptions({
  inheritAttrs: false,
});

interface Props {
  className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
  <font-awesome-icon
    :icon="faHardHat"
    :class="className"
    v-bind="$attrs"
  />
</template>
