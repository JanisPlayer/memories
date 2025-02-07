<template>
  <Modal @close="close" size="large" v-if="show">
    <template #title>
      {{ t('memories', 'Move selected photos to person') }}
    </template>

    <div class="outer">
      <FaceList :plus="true" @select="clickFace" />
    </div>

    <template #buttons>
      <NcButton @click="close" class="button" type="error">
        {{ t('memories', 'Cancel') }}
      </NcButton>
    </template>
  </Modal>
</template>

<script lang="ts">
import { defineComponent } from 'vue';

import NcButton from '@nextcloud/vue/dist/Components/NcButton';
const NcTextField = () => import('@nextcloud/vue/dist/Components/NcTextField');

import { showError } from '@nextcloud/dialogs';
import { IPhoto, IFace } from '../../types';
import Cluster from '../frame/Cluster.vue';
import FaceList from './FaceList.vue';

import Modal from './Modal.vue';
import * as dav from '../../services/dav';
import * as utils from '../../services/utils';

export default defineComponent({
  name: 'FaceMoveModal',
  components: {
    NcButton,
    NcTextField,
    Modal,
    Cluster,
    FaceList,
  },

  props: {
    updateLoading: {
      type: Function,
      required: true,
    },
  },

  data: () => ({
    show: false,
    photos: [] as IPhoto[],
  }),

  methods: {
    open(photos: IPhoto[]) {
      if (this.photos.length) {
        // is processing
        return;
      }

      // check ownership
      const user = this.$route.params.user || '';
      if (this.$route.params.user !== utils.uid) {
        showError(
          this.t('memories', 'Only user "{user}" can update this person', {
            user,
          })
        );
        return;
      }

      this.show = true;
      this.photos = photos;
    },

    close() {
      this.photos = [];
      this.show = false;
      this.$emit('close');
    },

    moved(list: IPhoto[]) {
      this.$emit('moved', list);
    },

    async clickFace(face: IFace) {
      const user = this.$route.params.user || '';
      const name = this.$route.params.name || '';
      const target = String(face.name || face.cluster_id);

      if (
        !(await utils.confirmDestructive({
          title: this.t('memories', 'Move to person'),
          message: this.t('memories', 'Move the selected photos to {target}?', {
            target: utils.isNumber(target) ? this.t('memories', 'unnamed person') : target,
          }),
          confirm: this.t('memories', 'Move'),
          confirmClasses: 'primary',
          cancel: this.t('memories', 'Cancel'),
        }))
      ) {
        return;
      }

      try {
        this.show = false;
        this.updateLoading(1);

        // Create map to return IPhoto later
        const map = new Map<number, IPhoto>();
        for (const photo of this.photos.filter((p) => p.faceid)) {
          map.set(photo.faceid!, photo);
        }

        // Run WebDAV query
        const photos = Array.from(map.values());
        for await (let delIds of dav.recognizeMoveFaceImages(user, name, target, photos)) {
          this.moved(delIds.filter((id) => id).map((id) => map.get(id)!));
        }
      } catch (error) {
        console.error(error);
        showError(this.t('photos', 'An error occurred while moving photos from {name}.', { name }));
      } finally {
        this.updateLoading(-1);
        this.close();
      }
    },
  },
});
</script>

<style lang="scss" scoped>
.outer {
  margin-top: 15px;
}
</style>
