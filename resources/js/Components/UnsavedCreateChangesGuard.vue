<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const page = usePage();
const isDirty = ref(false);
const dialogVisible = ref(false);
const pendingVisit = ref(null);
let allowNextVisit = false;
let removeBeforeListener;

const isCreatePage = computed(() => /Create$/i.test(page.component || ''));

function markDirty(event) {
  if (!isCreatePage.value || !event.target?.closest?.('form')) return;
  if (event.target.matches?.('button, input[type="submit"], input[type="button"]')) return;
  isDirty.value = true;
}

function handleBeforeVisit(event) {
  if (allowNextVisit) {
    allowNextVisit = false;
    return;
  }

  const visit = event.detail.visit;
  if (!isCreatePage.value || !isDirty.value || visit.method !== 'get') return;
  if (visit.url.href === new URL(page.url, window.location.origin).href) return;

  event.preventDefault();
  pendingVisit.value = visit;
  dialogVisible.value = true;
}

function handleBeforeUnload(event) {
  if (!isCreatePage.value || !isDirty.value) return;
  event.preventDefault();
  event.returnValue = '';
}

function keepEditing() {
  dialogVisible.value = false;
  pendingVisit.value = null;
}

function discardAndContinue() {
  const visit = pendingVisit.value;
  if (!visit) return keepEditing();

  const { url, completed, cancelled, interrupted, ...options } = visit;
  isDirty.value = false;
  pendingVisit.value = null;
  dialogVisible.value = false;
  allowNextVisit = true;
  router.visit(url.href, options);
}

watch(() => [page.component, page.url], () => {
  isDirty.value = false;
  pendingVisit.value = null;
  dialogVisible.value = false;
});

onMounted(() => {
  document.addEventListener('input', markDirty, true);
  document.addEventListener('change', markDirty, true);
  window.addEventListener('beforeunload', handleBeforeUnload);
  removeBeforeListener = router.on('before', handleBeforeVisit);
});

onBeforeUnmount(() => {
  document.removeEventListener('input', markDirty, true);
  document.removeEventListener('change', markDirty, true);
  window.removeEventListener('beforeunload', handleBeforeUnload);
  removeBeforeListener?.();
});
</script>

<template>
  <Dialog
    v-model:visible="dialogVisible"
    modal
    :closable="false"
    :draggable="false"
    header="Discard changes?"
    :style="{ width: 'min(26rem, calc(100vw - 2rem))' }"
  >
    <p class="text-sm leading-6 text-slate-600">
      You have unsaved changes on this page. Discard them and continue to the page you selected?
    </p>
    <template #footer>
      <div class="flex w-full justify-end gap-2">
        <Button label="Keep Editing" severity="secondary" outlined @click="keepEditing" />
        <Button label="Discard Changes" severity="danger" @click="discardAndContinue" />
      </div>
    </template>
  </Dialog>
</template>
