<template><div><dt class="text-[11px] font-bold uppercase tracking-wide text-slate-400">{{ label }}</dt><dd class="mt-1 font-medium text-slate-800">{{ value }}</dd></div></template>
<script setup lang="ts">defineProps<{label:string,value:any}>()</script>
