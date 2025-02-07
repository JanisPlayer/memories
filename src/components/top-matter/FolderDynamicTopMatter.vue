<template>
  <FolderGrid v-if="folders.length" :items="folders" />
</template>

<script lang="ts">
import { defineComponent } from 'vue';

import axios from '@nextcloud/axios';

import FolderGrid from './FolderGrid.vue';
import UserMixin from '../../mixins/UserConfig';

import * as utils from '../../services/utils';
import { API } from '../../services/API';

import type { IFolder } from '../../types';

export default defineComponent({
  name: 'FolderDynamicTopMatter',

  data: () => ({
    folders: [] as IFolder[],
  }),

  components: {
    FolderGrid,
  },

  mixins: [UserMixin],

  methods: {
    async refresh(): Promise<boolean> {
      // Clear folders
      this.folders = [];

      // Get subfolders URL
      const folder = utils.getFolderRoutePath(this.config.folders_path);
      const url = API.Q(API.FOLDERS_SUB(), { folder });

      // Make API call to get subfolders
      try {
        this.folders = (await axios.get<IFolder[]>(url)).data;
      } catch (e) {
        console.error(e);
        return false;
      }

      // Filter out hidden folders
      if (!this.config.show_hidden_folders) {
        this.folders = this.folders.filter((f) => !f.name.startsWith('.') && f.previews?.length);
      }

      return this.folders.length > 0;
    },
  },
});
</script>
