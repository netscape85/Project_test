<template>
  <v-footer app color="grey-lighten-3" class="text-center">
    <v-col cols="12">
      <span class="text-caption">© {{ currentYear }} TCG Engineering Hub. All rights reserved.</span>
    </v-col>
  </v-footer>
</template>

<script setup>
import { computed } from 'vue';

const currentYear = computed(() => new Date().getFullYear());
</script>
